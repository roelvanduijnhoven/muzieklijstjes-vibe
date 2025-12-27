<?php

namespace App\Command;

use App\Entity\Artist;
use App\Repository\ArtistRepository;
use App\Service\MusicBrainzService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:artist:match-musicbrainz',
    description: 'Matches artists with MusicBrainz and exports a mapping to JSON',
)]
class MatchArtistMusicBrainzCommand extends Command
{
    private const EXPORT_FILE = 'resources/import/artist_mbid_map.json';

    public function __construct(
        private ArtistRepository $artistRepository,
        private MusicBrainzService $musicBrainzService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Matching Artists with MusicBrainz');

        // Fetch all artists
        // Using iterator to handle large datasets effectively
        $query = $this->entityManager->createQuery('SELECT a FROM App\Entity\Artist a');
        $iterableResult = $query->toIterable();

        $mapping = [];
        $updatedCount = 0;
        $totalCount = 0;
        
        // Load existing mapping if available to append/update
        if (file_exists(self::EXPORT_FILE)) {
            $content = file_get_contents(self::EXPORT_FILE);
            $mapping = json_decode($content, true) ?? [];
        }

        foreach ($iterableResult as $artist) {
            /** @var Artist $artist */
            $totalCount++;
            $name = $artist->getName();
            $mbid = $artist->getMusicBrainzId();
            $changed = false;

            if (!$mbid) {
                $io->text("Searching for: $name");
                $mbid = $this->musicBrainzService->searchArtist($name);
                
                if ($mbid) {
                    $artist->setMusicBrainzId($mbid);
                    $io->success("  > Found: $mbid");
                    $updatedCount++;
                    $changed = true;
                } else {
                    $io->warning("  > Not found");
                }
            }

            if ($mbid) {
                $mapping[$name] = $mbid;
            }
            
            // Update database and file immediately if changed
            if ($changed) {
                $this->entityManager->flush();
                // Write back JSON immediately
                $json = json_encode($mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                file_put_contents(self::EXPORT_FILE, $json);
            }
            
            // Periodic clear to free memory
            if ($totalCount % 50 === 0) {
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('Finished. Updated %d artists. Mapping written to %s', $updatedCount, self::EXPORT_FILE));

        return Command::SUCCESS;
    }
}

