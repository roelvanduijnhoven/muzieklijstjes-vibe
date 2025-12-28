<?php

namespace App\Command;

use App\Entity\Artist;
use App\Repository\ArtistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:artist:import-mbid-map',
    description: 'Imports MusicBrainz IDs from a JSON mapping file to Artist entities',
)]
class ImportArtistMbidMapCommand extends Command
{
    private const IMPORT_FILE = 'resources/import/artist_mbid_map.json';

    public function __construct(
        private ArtistRepository $artistRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Importing Artist MusicBrainz IDs from JSON Map');

        if (!file_exists(self::IMPORT_FILE)) {
            $io->error(sprintf('Mapping file not found at %s', self::IMPORT_FILE));
            return Command::FAILURE;
        }

        $content = file_get_contents(self::IMPORT_FILE);
        $mapping = json_decode($content, true);

        if (!is_array($mapping)) {
            $io->error('Invalid JSON format in mapping file.');
            return Command::FAILURE;
        }

        $count = count($mapping);
        $io->text(sprintf('Found %d mappings in file.', $count));

        $progressBar = new ProgressBar($output, $count);
        $progressBar->start();

        $updatedCount = 0;
        $batchSize = 250;

        // Fetch all artists with one query into memory (index by name)
        // This avoids N+1 queries for finding each artist.
        // Assuming we have enough memory for all artist objects (22k is manageable).
        $io->text('Loading all artists into memory...');
        
        $artists = $this->entityManager->createQuery('SELECT a FROM App\Entity\Artist a')
            ->getResult();
            
        // Index by name for fast lookup
        $artistMap = [];
        foreach ($artists as $artist) {
            $artistMap[$artist->getName()] = $artist;
        }
        
        $io->text(sprintf('Loaded %d artists.', count($artistMap)));
        
        $i = 0;

        foreach ($mapping as $artistName => $mbid) {
            $progressBar->advance();
            
            if (empty($artistName) || empty($mbid)) {
                continue;
            }

            // Fast memory lookup
            if (!isset($artistMap[$artistName])) {
                continue;
            }
            
            $artist = $artistMap[$artistName];

            // Update if different or missing
            if ($artist->getMusicBrainzId() !== $mbid) {
                $artist->setMusicBrainzId($mbid);
                $updatedCount++;
                $i++;
                
                // Only flush periodically if we made changes
                if (($i % $batchSize) === 0) {
                    $this->entityManager->flush();
                    // Do NOT clear() because we need the object references in $artistMap to stay valid
                    // Since we loaded them all at once, they are managed.
                    // If memory is an issue with 22k objects + updates, we would need a more complex iteration strategy,
                    // but for 22k entities, PHP should handle it fine.
                }
            }
        }

        // Final flush
        $this->entityManager->flush();
        
        $progressBar->finish();
        $io->newLine(2);

        $io->success(sprintf('Finished. Updated %d artists.', $updatedCount));

        return Command::SUCCESS;
    }
}

