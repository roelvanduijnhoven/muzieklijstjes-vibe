<?php

namespace App\Command;

use App\Entity\Album;
use App\Repository\AlbumRepository;
use Aws\S3\S3Client;
use Doctrine\DBAL\Logging\Middleware;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:sync-covers-from-bucket',
    description: 'Syncs album covers from S3 bucket based on legacy naming convention',
)]
class SyncCoversFromBucketCommand extends Command
{
    private const BUCKET_PREFIX = 'covers/';

    public function __construct(
        private AlbumRepository $albumRepository,
        private EntityManagerInterface $entityManager,
        // Now that we updated .env, we can inject the service-configured S3Client if we want,
        // or just use the env vars to build it manually if the service config is different.
        // The service config in services.yaml uses the same env vars we just updated.
        // So we can use the main S3Client service!
        private S3Client $s3Client,
        #[Autowire('%env(DO_SPACES_BUCKET)%')]
        private string $bucketName,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not update database, just show what would be done');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 1. Disable SQL Logging to save memory
        $this->entityManager->getConnection()->getConfiguration()->setMiddlewares([]);
        
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        $io->title('Syncing covers from S3 bucket');
        $io->text('Bucket: ' . $this->bucketName);

        // 2. Fetch all existing entries from S3
        $io->section('Fetching existing entries from S3...');
        $existingEntries = $this->getExistingEntries($this->s3Client, $this->bucketName, $io);
        $io->success(sprintf('Found %d files in bucket.', count($existingEntries)));

        // 3. Iterate over all albums
        $io->section('Matching albums...');
        
        // Count total for progress bar
        $totalCount = $this->albumRepository->count([]);
        $io->progressStart($totalCount);

        // Use DQL with Array Hydration for memory efficiency and speed
        $query = $this->entityManager->createQuery(
            'SELECT a.id, a.title, art.name as artistName, a.imageUrl 
             FROM App\Entity\Album a 
             JOIN a.artist art'
        );
        
        $iterable = $query->toIterable([], Query::HYDRATE_ARRAY);

        $updates = [];
        $batchSize = 1000;
        $processed = 0;
        $matchedCount = 0;

        foreach ($iterable as $row) {
            $artistName = $row['artistName'];
            $albumTitle = $row['title'];
            $currentUrl = $row['imageUrl'];
            $albumId = $row['id'];
            
            // Generate the expected path
            $expectedPath = $this->getPathInCoverBucket($albumTitle, $artistName, 'webp');
            
            if (isset($existingEntries[$expectedPath])) {
                // Found!
                $fullPath = self::BUCKET_PREFIX . $expectedPath;
                
                if ($currentUrl !== $fullPath) {
                    $updates[] = [
                        'id' => $albumId,
                        'url' => $fullPath
                    ];
                    $matchedCount++;
                    
                    if ($io->isVerbose()) {
                        $io->text(sprintf('Matched: %s - %s -> %s', $artistName, $albumTitle, $fullPath));
                    }
                }
            }
            
            if (count($updates) >= $batchSize) {
                $this->flushUpdates($updates, $dryRun);
                $updates = [];
                // Clear EntityManager to free memory
                $this->entityManager->clear();
                gc_collect_cycles();
            }

            $io->progressAdvance();
            $processed++;
        }

        if (count($updates) > 0) {
            $this->flushUpdates($updates, $dryRun);
        }

        $io->progressFinish();

        $io->success(sprintf('Processed %d albums. Updated %d.', $processed, $matchedCount));

        return Command::SUCCESS;
    }

    /**
     * @param array<array{id: int, url: string}> $updates
     */
    private function flushUpdates(array $updates, bool $dryRun): void
    {
        if ($dryRun || empty($updates)) {
            return;
        }

        $conn = $this->entityManager->getConnection();
        
        // Get metadata to ensure correct table/column names
        $metadata = $this->entityManager->getClassMetadata(Album::class);
        $tableName = $metadata->getTableName();
        $colId = $metadata->getColumnName('id');
        $colImage = $metadata->getColumnName('imageUrl');
        $colImageFetchFailed = $metadata->getColumnName('imageFetchFailed');

        $conn->beginTransaction();
        try {
            $sql = sprintf(
                'UPDATE %s SET %s = :url, %s = 0 WHERE %s = :id', 
                $tableName, 
                $colImage, 
                $colImageFetchFailed,
                $colId
            );
            $stmt = $conn->prepare($sql);

            foreach ($updates as $update) {
                $stmt->executeQuery([
                    'url' => $update['url'], 
                    'id' => $update['id']
                ]);
            }
            
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e; // Re-throw to handle/log in execute
        }
    }

    /**
     * Return map of existing entries in Spaces bucket.
     * Adapted from k8s/covers.functions.php
     * 
     * @return array<string, bool> Map of "path/to/file.ext" => true (relative to covers/)
     */
    private function getExistingEntries(S3Client $s3Client, string $bucketName, SymfonyStyle $io): array
    {
        $matches = [];
        $continuationToken = null;
        
        try {
            do {
                $params = [
                    'Bucket' => $bucketName,
                    'Prefix' => self::BUCKET_PREFIX,
                ];
                
                if ($continuationToken) {
                    $params['ContinuationToken'] = $continuationToken;
                }

                $result = $s3Client->listObjectsV2($params);

                $files = $result['Contents'] ?? [];
                foreach ($files as $file) {
                    $path = $file['Key'];
                    
                    if (str_starts_with($path, self::BUCKET_PREFIX)) {
                        $relativePath = substr($path, strlen(self::BUCKET_PREFIX));
                        if (!empty($relativePath)) {
                             $matches[$relativePath] = true;
                        }
                    }
                }

                $continuationToken = $result['NextContinuationToken'] ?? null;
            } while ($continuationToken);
            
        } catch (\Exception $e) {
            $io->error('Failed to list objects: ' . $e->getMessage());
            return [];
        }

        return $matches;
    }

    // --- Helper functions adapted from k8s/covers.functions.php ---

    private function getPathInCoverBucket(string $albumName, string $artistName, string $fileExtension): string
    {
        $albumName = strtolower($albumName);
        $artistName = strtolower($artistName);
        
        $firstLetter = mb_substr($artistName, 0, 1);
        
        $coverArtDirection = sprintf(
            '%s/%s', 
            $this->sanitizeS3Key($firstLetter), 
            $this->sanitizeS3Key($artistName)
        );
        
        return sprintf('%s/%s.%s', $coverArtDirection, $this->sanitizeS3Key($albumName), $fileExtension);
    }

    private function sanitizeS3Key(string $key, string $replacement = '_'): string 
    {
        $key = $this->removeDiacrites($key);

        $allowedPattern = '/[^a-zA-Z0-9\-_. !*\'()\/]/';
        $sanitizedKey = preg_replace($allowedPattern, $replacement, $key);

        $sanitizedKey = trim($sanitizedKey, '/');

        return $sanitizedKey;
    }

    private function removeDiacrites(string $input): string 
    {
        if (class_exists(\Transliterator::class)) {
            static $transliterator = null;
            if ($transliterator === null) {
                $transliterator = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
            }
            if ($transliterator) {
                return $transliterator->transliterate($input);
            }
        }
        
        // Fallback
        return iconv('UTF-8', 'ASCII//TRANSLIT', $input);
    }
}
