<?php

namespace App\Command;

use App\Entity\Album;
use App\Repository\AlbumRepository;
use App\Service\ImageStorageService;
use App\Service\MusicBrainzService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fetch-covers',
    description: 'Fetches missing album covers from MusicBrainz',
)]
class FetchAlbumCoversCommand extends Command
{
    public function __construct(
        private AlbumRepository $albumRepository,
        private MusicBrainzService $musicBrainzService,
        private ImageStorageService $imageStorageService,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit the number of albums to process', 50);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = (int) $input->getOption('limit');

        // Find albums without cover and not marked as failed
        $albums = $this->albumRepository->findBy(
            ['imageUrl' => null, 'imageFetchFailed' => false],
            ['id' => 'DESC'], // Newest first? Or random? Let's do ID DESC
            $limit
        );

        if (empty($albums)) {
            $io->success('No albums found needing covers.');
            return Command::SUCCESS;
        }

        $io->progressStart(count($albums));

        foreach ($albums as $album) {
            $artistName = $album->getArtist()->getName();
            $albumTitle = $album->getTitle();

            $io->text(sprintf(' Processing: %s - %s', $artistName, $albumTitle));

            try {
                // 1. Get URL
                $coverUrl = $this->musicBrainzService->getCoverArtUrl($artistName, $albumTitle);

                if (!$coverUrl) {
                    $io->text('  > No cover found in MusicBrainz');
                    $album->setImageFetchFailed(true);
                } else {
                    // 2. Download and Store
                    // Generate a path: covers/{id}_{slug}.jpg
                    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($artistName . '-' . $albumTitle));
                    $path = sprintf('covers/%d_%s.jpg', $album->getId(), trim($slug, '-'));

                    if ($this->imageStorageService->fetchAndStore($coverUrl, $path)) {
                         // We store the full URL if we want to link directly, or just the path if we serve it via a controller/CDN.
                         // The user said "add a property to the album that references some path within the Dropbox Spaces directory".
                         // Usually this implies the relative path or key.
                         // But to display it, we might need the full public URL. 
                         // Let's store the Key (path) for now, or if we have a CDN domain, prepend it.
                         // Assuming we want the relative path in the DB.
                         $album->setImageUrl($path);
                         $io->text('  > Downloaded and stored.');
                    } else {
                        $io->text('  > Failed to download/store image.');
                        // Maybe don't mark as failed permanently if it's a network error?
                        // For now, let's NOT mark as failed so we retry later, or maybe separate 'imageFetchFailed' vs 'imageStoreFailed'.
                        // But keeping it simple as per request.
                    }
                }

            } catch (\Exception $e) {
                $io->error('Error: ' . $e->getMessage());
            }

            // Flush every time or in batches?
            // Every time is safer for long running processes to avoid data loss on crash.
            $this->entityManager->flush();
            
            $io->progressAdvance();
        }

        $io->progressFinish();

        return Command::SUCCESS;
    }
}

