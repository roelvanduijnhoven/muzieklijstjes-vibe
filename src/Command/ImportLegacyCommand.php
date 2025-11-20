<?php

namespace App\Command;

use App\Entity\Album;
use App\Entity\AlbumList;
use App\Entity\AlbumListItem;
use App\Entity\Artist;
use App\Entity\Critic;
use App\Entity\Magazine;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-legacy',
    description: 'Imports data from the legacy MySQL database',
)]
class ImportLegacyCommand extends Command
{
    // Maps now store Legacy ID => New ID (int)
    private array $artistMap = [];
    private array $magazineMap = []; // key: name (string) -> ID (int)
    private array $criticMap = []; // key: id (int) -> ID (int)
    private array $albumMap = []; // key: id (int) -> ID (int)
    private array $listMap = []; // key: id (int) -> ID (int)

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Connection $legacyConnection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('reset', null, InputOption::VALUE_NONE, 'Reset tables before import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('reset')) {
            $io->note('Resetting tables...');
            $this->resetTables();
        }

        // Disable logging for performance
        $this->entityManager->getConnection()->getConfiguration()->setSQLLogger(null);

        $io->section('Importing Artists');
        $this->importArtists($io);

        $io->section('Importing Magazines');
        $this->importMagazines($io);

        $io->section('Importing Critics');
        $this->importCritics($io);

        $io->section('Importing Albums');
        $this->importAlbums($io);

        $io->section('Importing Lists');
        $this->importLists($io);

        $io->success('Import complete.');

        return Command::SUCCESS;
    }

    private function resetTables(): void
    {
        $conn = $this->entityManager->getConnection();
        $platform = $conn->getDatabasePlatform();
        
        $tables = [
            'album_list_item',
            'album_list_album_list',
            'album_list',
            'album',
            'artist',
            'critic',
            'magazine'
        ];

        foreach ($tables as $table) {
            if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
                $conn->executeStatement("TRUNCATE TABLE $table CASCADE");
            } else {
                $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
                $conn->executeStatement("TRUNCATE TABLE $table");
                $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
            }
        }
    }

    private function importArtists(SymfonyStyle $io): void
    {
        $rows = $this->legacyConnection->fetchAllAssociative('SELECT * FROM artiest');
        $io->progressStart(count($rows));
        
        $batch = [];
        $i = 0;

        foreach ($rows as $row) {
            $artist = new Artist();
            $artist->setName($row['artiest']);
            $this->entityManager->persist($artist);
            
            $batch[$row['id']] = $artist;

            if ((++$i % 1000) === 0) {
                $this->entityManager->flush();
                foreach ($batch as $legacyId => $entity) {
                    $this->artistMap[$legacyId] = $entity->getId();
                }
                $this->entityManager->clear(); // Detach all
                $batch = [];
            }
            $io->progressAdvance();
        }
        
        // Flush remaining
        $this->entityManager->flush();
        foreach ($batch as $legacyId => $entity) {
            $this->artistMap[$legacyId] = $entity->getId();
        }
        $this->entityManager->clear();
        
        $io->progressFinish();
    }

    private function importMagazines(SymfonyStyle $io): void
    {
        $rows = $this->legacyConnection->fetchAllAssociative('SELECT * FROM tijdschrift');
        $io->progressStart(count($rows));

        foreach ($rows as $row) {
            $magazine = new Magazine();
            $magazine->setName($row['tijdschrift']);
            $this->entityManager->persist($magazine);
            $this->entityManager->flush(); // Small table, flush immediately to get ID
            
            $newId = $magazine->getId();
            $this->magazineMap[$row['id']] = $newId; 
            $this->magazineMap[strtolower(trim($row['tijdschrift']))] = $newId;
            
            $io->progressAdvance();
        }
        $this->entityManager->clear();
        $io->progressFinish();
    }

    private function importCritics(SymfonyStyle $io): void
    {
        $rows = $this->legacyConnection->fetchAllAssociative('SELECT * FROM recensent');
        $io->progressStart(count($rows));

        foreach ($rows as $row) {
            $critic = new Critic();
            $critic->setName($row['recensent']);
            
            $bioParts = [];
            if ($row['geboorteJaar']) $bioParts[] = 'Born: ' . $row['geboorteJaar'];
            if ($row['sterfteJaar']) $bioParts[] = 'Died: ' . $row['sterfteJaar'];
            if ($row['url']) $bioParts[] = 'URL: ' . $row['url'];
            
            if (!empty($bioParts)) {
                $critic->setBio(implode("\n", $bioParts));
            }

            $this->entityManager->persist($critic);
            $this->entityManager->flush();
            
            $newId = $critic->getId();
            $this->criticMap[$row['id']] = $newId;
            $this->criticMap[strtolower(trim($row['recensent']))] = $newId;

            $io->progressAdvance();
        }
        $this->entityManager->clear();
        $io->progressFinish();
    }

    private function importAlbums(SymfonyStyle $io): void
    {
        $rows = $this->legacyConnection->fetchAllAssociative('SELECT * FROM album');
        $io->progressStart(count($rows));
        
        $batch = [];
        $i = 0;

        foreach ($rows as $row) {
            if (!isset($this->artistMap[$row['artiest_id']])) {
                continue;
            }

            $album = new Album();
            $album->setTitle($row['album']);
            $album->setReleaseYear((int)$row['jaar']);
            
            // Use Reference
            $artistRef = $this->entityManager->getReference(Artist::class, $this->artistMap[$row['artiest_id']]);
            $album->setArtist($artistRef);

            $this->entityManager->persist($album);
            $batch[$row['id']] = $album;
            
            if ((++$i % 1000) === 0) {
                $this->entityManager->flush();
                foreach ($batch as $legacyId => $entity) {
                    $this->albumMap[$legacyId] = $entity->getId();
                }
                $this->entityManager->clear();
                $batch = [];
            }
            $io->progressAdvance();
        }
        $this->entityManager->flush();
        foreach ($batch as $legacyId => $entity) {
            $this->albumMap[$legacyId] = $entity->getId();
        }
        $this->entityManager->clear();
        $io->progressFinish();
    }

    private function importLists(SymfonyStyle $io): void
    {
        // 1. Import Base Lists (lijstenB)
        $rows = $this->legacyConnection->fetchAllAssociative('SELECT * FROM lijstenB');
        $io->section('Importing Base Lists (lijstenB)');
        $io->progressStart(count($rows));
        
        $batch = [];
        $i = 0;

        foreach ($rows as $row) {
            $list = new AlbumList();
            
            // Map omschrijving to Title, lijsten to Code, jaar to Year
            $list->setTitle($row['omschrijving'] ?: $row['lijst']); // Fallback if empty
            $list->setCode($row['lijst']);
            $list->setReleaseYear((int)$row['jaar']);
            $list->setDescription(null);
            
            // Import canon field as important
            $list->setImportant((bool)$row['canon']);
            
            // Initially set type based on legacy type, but we'll update AK types later
            $type = match(strtoupper($row['type'])) {
                'POS' => AlbumList::TYPE_ORDERED,
                'GP'  => AlbumList::TYPE_UNORDERED,
                'AK'  => AlbumList::TYPE_MENTIONED, // Default AK to MENTIONED initially
                default => AlbumList::TYPE_ORDERED
            };
            $list->setType($type);
            
            $bron = strtolower(trim($row['bron']));
            if (isset($this->magazineMap[$bron])) {
                $magRef = $this->entityManager->getReference(Magazine::class, $this->magazineMap[$bron]);
                $list->setMagazine($magRef);
            } elseif (isset($this->criticMap[$bron])) {
                $criticRef = $this->entityManager->getReference(Critic::class, $this->criticMap[$bron]);
                $list->setCritic($criticRef);
            }

            $this->entityManager->persist($list);
            $batch[$row['id']] = $list;

            if ((++$i % 1000) === 0) {
                $this->entityManager->flush();
                foreach ($batch as $legacyId => $entity) {
                    $this->listMap[$legacyId] = $entity->getId();
                }
                $this->entityManager->clear();
                $batch = [];
            }
            $io->progressAdvance();
        }
        $this->entityManager->flush();
        foreach ($batch as $legacyId => $entity) {
            $this->listMap[$legacyId] = $entity->getId();
        }
        $this->entityManager->clear();
        $io->progressFinish();

        // 2. Import List Items (lijsten)
        $io->section('Importing List Items (lijsten)');
        $lRows = $this->legacyConnection->fetchAllAssociative('SELECT * FROM lijsten');
        $io->progressStart(count($lRows));
        
        $i = 0;
        foreach ($lRows as $row) {
            $listId = $row['lijst_id'];
            $albumId = $row['album_id'];
            
            if (!isset($this->listMap[$listId]) || !isset($this->albumMap[$albumId])) {
                continue;
            }
            
            $item = new AlbumListItem();
            // References
            $listRef = $this->entityManager->getReference(AlbumList::class, $this->listMap[$listId]);
            $albumRef = $this->entityManager->getReference(Album::class, $this->albumMap[$albumId]);
            
            $item->setAlbumList($listRef);
            $item->setAlbum($albumRef);
            $item->setPosition($row['pos'] !== null && $row['pos'] !== '' ? (int)$row['pos'] : null);
            $item->setMentions($row['ak'] !== null && $row['ak'] !== '' ? (int)$row['ak'] : null);
            
            $this->entityManager->persist($item);
            
            if ((++$i % 1000) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear(); // Clear all items
            }
            $io->progressAdvance();
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
        $io->progressFinish();

        // 3. Import Contributors/Individual Lists (lijstenI)
        // lijstenI.lijsten_id references lijsten.id (not lijstenB.id)
        // We need to group by aggregate list and critic to create individual lists
        $io->section('Importing Contributors (lijstenI)');
        
        // First, get all aggregate lists
        $aggregateLists = $this->legacyConnection->fetchAllAssociative(
            "SELECT id, omschrijving FROM lijstenB WHERE type = 'ak'"
        );
        
        $io->progressStart(count($aggregateLists));
        
        foreach ($aggregateLists as $aggList) {
            $aggListId = $aggList['id'];
            
            if (!isset($this->listMap[$aggListId])) {
                continue;
            }
            
            // Get all critics who contributed to this list
            $contributors = $this->legacyConnection->fetchAllAssociative(
                "SELECT DISTINCT li.recensent_id, r.recensent 
                 FROM lijstenI li 
                 JOIN lijsten l ON li.lijsten_id = l.id 
                 JOIN recensent r ON li.recensent_id = r.id
                 WHERE l.lijst_id = ?",
                [$aggListId]
            );
            
            foreach ($contributors as $contributor) {
                $criticId = $contributor['recensent_id'];
                
                if (!isset($this->criticMap[$criticId])) {
                    continue;
                }
                
                // Fetch the parent aggregate list
                $parentList = $this->entityManager->find(AlbumList::class, $this->listMap[$aggListId]);
                $critic = $this->entityManager->find(Critic::class, $this->criticMap[$criticId]);
                
                // Create individual list for this critic
                $childList = new AlbumList();
                $childList->setTitle($parentList->getTitle() . ' - ' . $critic->getName());
                $childList->setType(AlbumList::TYPE_ORDERED);
                $childList->setCritic($critic);
                
                // Add as source to parent
                $parentList->addSource($childList);
                $this->entityManager->persist($childList);
                $this->entityManager->flush(); // Flush to get ID
                
                $childListId = $childList->getId();
                
                // Now get all albums this critic voted for in this list
                $votes = $this->legacyConnection->fetchAllAssociative(
                    "SELECT l.album_id, li.pos
                     FROM lijstenI li
                     JOIN lijsten l ON li.lijsten_id = l.id
                     WHERE l.lijst_id = ? AND li.recensent_id = ?
                     ORDER BY li.pos",
                    [$aggListId, $criticId]
                );
                
                // Add items to child list
                foreach ($votes as $vote) {
                    if (!isset($this->albumMap[$vote['album_id']])) {
                        continue;
                    }
                    
                    $item = new AlbumListItem();
                    $listRef = $this->entityManager->getReference(AlbumList::class, $childListId);
                    $albumRef = $this->entityManager->getReference(Album::class, $this->albumMap[$vote['album_id']]);
                    
                    $item->setAlbumList($listRef);
                    $item->setAlbum($albumRef);
                    $item->setPosition($vote['pos'] ?: null);
                    
                    $this->entityManager->persist($item);
                }
                
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
            
            $io->progressAdvance();
        }
        
        $io->progressFinish();
        
        // 4. Update lists to AGGREGATE type if they have sources
        // Only update those that were originally imported as MENTIONED (AK) but turned out to be AGGREGATES
        // because they have sources.
        $io->section('Updating aggregate list types');
        $conn = $this->entityManager->getConnection();
        $conn->executeStatement(
            "UPDATE album_list al 
             SET type = 'aggregate' 
             WHERE type = 'mentioned' AND EXISTS (
                 SELECT 1 FROM album_list_album_list alas 
                 WHERE alas.album_list_source = al.id
             )"
        );
        $io->writeln('Updated mentioned lists with sources to aggregate type');
    }
}
