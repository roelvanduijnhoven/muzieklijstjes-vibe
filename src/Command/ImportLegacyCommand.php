<?php

namespace App\Command;

use App\Entity\Album;
use App\Entity\AlbumList;
use App\Entity\AlbumListItem;
use App\Entity\Artist;
use App\Entity\Critic;
use App\Entity\Feature;
use App\Entity\Genre;
use App\Entity\Issue;
use App\Entity\Magazine;
use App\Entity\Review;
use App\Entity\Rubric;
use App\Enum\AlbumFormat;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
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
    private array $genreMap = []; // key: id (int) -> ID (int) (for 'genre' table)
    private array $featureMap = []; // key: id (int) -> ID (int)
    private array $rubricMap = []; // key: legacy_id (int|string) -> ID (int)
    private array $issueMap = []; // key: "magId-year-issueNum" -> ID (int)

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

        $io->section('Importing Genres');
        $this->importGenres($io);

        $io->section('Importing Features');
        $this->importFeatures($io);

        $io->section('Importing Artists');
        $this->importArtists($io);

        $io->section('Importing Magazines');
        $this->importMagazines($io);
        
        $io->section('Importing Issues');
        $this->importIssues($io);

        $io->section('Importing Rubrics');
        $this->importRubrics($io);

        $io->section('Importing Critics');
        $this->importCritics($io);

        $io->section('Linking Critics to Genres');
        $this->linkCriticsToGenres($io);

        $io->section('Linking Critics to Features');
        $this->linkCriticsToFeatures($io);

        $io->section('Importing Albums');
        $this->importAlbums($io);

        $io->section('Importing Reviews');
        $this->importReviews($io);

        $io->section('Importing Lists');
        $this->importLists($io);
        
        // Import MBID map
        $io->section('Importing MusicBrainz IDs');
        $command = $this->getApplication()->find('app:artist:import-mbid-map');
        $command->run(new ArrayInput([]), $output);

        // Sync covers from bucket
        $io->section('Syncing Covers from Bucket');
        $command = $this->getApplication()->find('app:sync-covers-from-bucket');
        $command->run(new ArrayInput([]), $output);

        // Run aggregation command automatically
        $io->section('Running aggregation...');
        $command = $this->getApplication()->find('app:aggregate-lists');
        $arguments = [
            'command' => 'app:aggregate-lists',
        ];
        $aggregateInput = new ArrayInput($arguments);
        $command->run($aggregateInput, $output);

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
            'album_artist',
            'review',
            'issue',
            'album',
            'artist',
            'critic_genre',
            'critic_feature',
            'critic',
            'rubric',
            'magazine',
            'genre',
            'feature'
        ];

        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
                $conn->executeStatement("TRUNCATE TABLE $table CASCADE");
            } else {
                try {
                    $conn->executeStatement("TRUNCATE TABLE $table");
                } catch (\Exception $e) {
                    // If TRUNCATE fails (e.g. table doesn't exist), try deleting all rows
                    // This handles cases where TRUNCATE is restrictive or table state is inconsistent
                    // Also, if table doesn't exist, this will also fail, which is fine as we catch it.
                }
            }
        }
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function importGenres(SymfonyStyle $io): void
    {
        $existingGenres = []; // lower(name) => ID

        // Import 'genre' table
        $genreRows = $this->legacyConnection->fetchAllAssociative('SELECT * FROM genre');
        $io->text('Importing from table `genre`...');

        foreach ($genreRows as $row) {
            $name = trim($row['genre']);
            if ($name === '') continue;
            
            $key = strtolower($name);

            if (isset($existingGenres[$key])) {
                $this->genreMap[$row['id']] = $existingGenres[$key];
                continue;
            }

            $genre = new Genre();
            $genre->setName($name);
            $this->entityManager->persist($genre);
            $this->entityManager->flush();

            $id = $genre->getId();
            $this->genreMap[$row['id']] = $id;
            $existingGenres[$key] = $id;
        }

        $this->entityManager->clear();
    }

    private function importFeatures(SymfonyStyle $io): void
    {
        $rows = $this->legacyConnection->fetchAllAssociative('SELECT * FROM kenmerk');
        $io->progressStart(count($rows));
        
        $batch = [];
        $i = 0;

        foreach ($rows as $row) {
            $feature = new Feature();
            $feature->setName($row['kenmerk']);
            $this->entityManager->persist($feature);
            
            $batch[$row['id']] = $feature;

            if ((++$i % 100) === 0) {
                $this->entityManager->flush();
                foreach ($batch as $legacyId => $entity) {
                    $this->featureMap[$legacyId] = $entity->getId();
                }
                $this->entityManager->clear();
                $batch = [];
            }
            $io->progressAdvance();
        }
        
        $this->entityManager->flush();
        foreach ($batch as $legacyId => $entity) {
            $this->featureMap[$legacyId] = $entity->getId();
        }
        $this->entityManager->clear();
        
        $io->progressFinish();
    }

    private function linkCriticsToFeatures(SymfonyStyle $io): void
    {
        $rows = $this->legacyConnection->fetchAllAssociative('SELECT * FROM kenmerk2recensent');
        $io->progressStart(count($rows));
        
        $i = 0;
        $linkedCount = 0;

        foreach ($rows as $row) {
            $criticId = $row['recensent_id'];
            $featureId = $row['kenmerk_id'];

            if (isset($this->criticMap[$criticId]) && isset($this->featureMap[$featureId])) {
                $critic = $this->entityManager->find(Critic::class, $this->criticMap[$criticId]);
                $feature = $this->entityManager->getReference(Feature::class, $this->featureMap[$featureId]);
                
                if ($critic) {
                    $critic->addFeature($feature);
                    $this->entityManager->persist($critic);
                    $linkedCount++;
                }
            }
            
            if ((++$i % 1000) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
        $io->progressFinish();
        
        $io->text(sprintf('Linked %d features to critics.', $linkedCount));
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
            $artist->setSortName($row['sArtiest']);
            
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

        $i = 0;
        $batch = [];

        foreach ($rows as $row) {
            $magazine = new Magazine();
            $magazine->setName($row['tijdschrift']);
            $magazine->setAbbreviation($row['afkorting'] ?: null);
            $magazine->setHighestPossibleRating((int)$row['waardering']);
            $this->entityManager->persist($magazine);
            
            $batch[] = ['entity' => $magazine, 'id' => $row['id'], 'name' => $row['tijdschrift']];
            
            if ((++$i % 100) === 0) {
                 $this->entityManager->flush();
            foreach ($batch as $item) {
                $this->magazineMap[$item['id']] = $item['entity']->getId();
                $name = strtolower(trim($item['name']));
                $this->magazineMap[$name] = $item['entity']->getId();

                // Also map short name (before parenthesis) to handle cases like "Heaven (1999-nu)" matching "Heaven"
                if (str_contains($name, '(')) {
                    $parts = explode('(', $name);
                    $shortName = trim($parts[0]);
                    if ($shortName !== '' && !isset($this->magazineMap[$shortName])) {
                        $this->magazineMap[$shortName] = $item['entity']->getId();
                    }
                }
            }
                 $this->entityManager->clear();
                 $batch = [];
            }
            
            $io->progressAdvance();
        }
        
        $this->entityManager->flush();
        foreach ($batch as $item) {
            $this->magazineMap[$item['id']] = $item['entity']->getId();
            $name = strtolower(trim($item['name']));
            $this->magazineMap[$name] = $item['entity']->getId();

            // Also map short name (before parenthesis)
            if (str_contains($name, '(')) {
                $parts = explode('(', $name);
                $shortName = trim($parts[0]);
                if ($shortName !== '' && !isset($this->magazineMap[$shortName])) {
                    $this->magazineMap[$shortName] = $item['entity']->getId();
                }
            }
        }
        $this->entityManager->clear();
        
        $io->progressFinish();
    }

    private function importIssues(SymfonyStyle $io): void
    {
        // Import distinct issues from review table
        $rows = $this->legacyConnection->fetchAllAssociative(
            'SELECT DISTINCT tijdschrift_id, jaar, nummer FROM recensie 
             WHERE tijdschrift_id IS NOT NULL AND jaar IS NOT NULL'
        );
        
        $io->text('Importing Issues from reviews...');
        $io->progressStart(count($rows));
        
        $batch = [];
        $i = 0;

        foreach ($rows as $row) {
            $magId = $row['tijdschrift_id'];
            if (!isset($this->magazineMap[$magId])) {
                continue;
            }

            $year = (int)$row['jaar'];
            // Normalize issue number logic matching importReviews
            $rawNum = $row['nummer'];
            // Use 'n/a' for missing issue numbers to satisfy NOT NULL constraint
            $issueNum = ($rawNum !== '0' && $rawNum !== '' && $rawNum !== null) ? (string)$rawNum : 'n/a';

            // Create key for map
            $key = sprintf('%d-%d-%s', $magId, $year, $issueNum);
            
            if (isset($this->issueMap[$key])) {
                continue;
            }

            $issue = new Issue();
            $magRef = $this->entityManager->getReference(Magazine::class, $this->magazineMap[$magId]);
            $issue->setMagazine($magRef);
            $issue->setYear($year);
            $issue->setIssueNumber($issueNum);

            $this->entityManager->persist($issue);
            
            $batch[] = ['entity' => $issue, 'key' => $key];

            if ((++$i % 1000) === 0) {
                $this->entityManager->flush();
                foreach ($batch as $item) {
                    $this->issueMap[$item['key']] = $item['entity']->getId();
                }
                $this->entityManager->clear();
                $batch = [];
            }
            
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        foreach ($batch as $item) {
            $this->issueMap[$item['key']] = $item['entity']->getId();
        }
        $this->entityManager->clear();
        
        $io->progressFinish();
    }

    private function importRubrics(SymfonyStyle $io): void
    {
        // Import from 'rubriek' table
        $rows = $this->legacyConnection->fetchAllAssociative('SELECT * FROM rubriek');
        $io->text('Importing from table `rubriek`...');
        
        $io->progressStart(count($rows));
        
        $batch = [];
        $i = 0;

        foreach ($rows as $row) {
            $magazineId = $row['tijdschrift_id'];
            $abbr = $row['aRubriek']; // Abbreviation
            $name = $row['rubriek'];  // Full Name
            $legacyId = $row['id'];

            if (!isset($this->magazineMap[$magazineId])) {
                continue;
            }

            $rubric = new Rubric();
            $rubric->setAbbreviation($abbr);
            $rubric->setName($name);
            
            $magRef = $this->entityManager->getReference(Magazine::class, $this->magazineMap[$magazineId]);
            $rubric->setMagazine($magRef);

            $this->entityManager->persist($rubric);
            
            // Mapping key: Legacy ID
            $batch[$legacyId] = $rubric;

            if ((++$i % 100) === 0) {
                $this->entityManager->flush();
                foreach ($batch as $lid => $entity) {
                    $this->rubricMap[$lid] = $entity->getId();
                }
                $this->entityManager->clear();
                $batch = [];
            }
            $io->progressAdvance();
        }
        
        $this->entityManager->flush();
        foreach ($batch as $lid => $entity) {
            $this->rubricMap[$lid] = $entity->getId();
        }
        $this->entityManager->clear();
        
        $io->progressFinish();
    }

    private function importCritics(SymfonyStyle $io): void
    {
        $rows = $this->legacyConnection->fetchAllAssociative('SELECT * FROM recensent');
        $io->progressStart(count($rows));

        $i = 0;
        $batch = [];

        foreach ($rows as $row) {
            $critic = new Critic();
            $critic->setName($row['recensent']);
            $critic->setSortName($row['sRecensent']);
            $critic->setAbbreviation($row['aRecensent']);
            
            // Map new fields
            $critic->setBirthYear($row['geboorteJaar'] ? (int)$row['geboorteJaar'] : null);
            $critic->setDeathYear($row['sterfteJaar'] ? (int)$row['sterfteJaar'] : null);
            $critic->setUrl($row['url'] ?: null);

            // Bio no longer needs to contain these fields, but if there's other bio info in legacy (none seen in schema), we'd use it.
            // Since there is no 'bio' column in legacy 'recensent' table (only name, sortName, abbr, dates, url), 
            // and we are now mapping dates/url to dedicated fields, we can leave bio empty or NULL.
            $critic->setBio(null);

            $this->entityManager->persist($critic);
            $batch[] = ['entity' => $critic, 'id' => $row['id'], 'name' => $row['recensent']];

            if ((++$i % 1000) === 0) {
                $this->entityManager->flush();
                foreach ($batch as $item) {
                    $this->criticMap[$item['id']] = $item['entity']->getId();
                    $this->criticMap[strtolower(trim($item['name']))] = $item['entity']->getId();
                }
                $this->entityManager->clear();
                $batch = [];
            }

            $io->progressAdvance();
        }

        $this->entityManager->flush();
        foreach ($batch as $item) {
            $this->criticMap[$item['id']] = $item['entity']->getId();
            $this->criticMap[strtolower(trim($item['name']))] = $item['entity']->getId();
        }
        $this->entityManager->clear();
        
        $io->progressFinish();
    }

    private function linkCriticsToGenres(SymfonyStyle $io): void
    {
        $rows = $this->legacyConnection->fetchAllAssociative('SELECT * FROM genre2recensent');
        $io->progressStart(count($rows));
        
        $i = 0;
        $linkedCount = 0;

        foreach ($rows as $row) {
            $criticId = $row['recensent_id'];
            $genreId = $row['genre_id'];
            
            $targetGenreId = $this->genreMap[$genreId] ?? null;

            if (isset($this->criticMap[$criticId]) && $targetGenreId !== null) {
                $critic = $this->entityManager->find(Critic::class, $this->criticMap[$criticId]);
                $genre = $this->entityManager->getReference(Genre::class, $targetGenreId);
                
                if ($critic) {
                    $critic->addGenre($genre);
                    $this->entityManager->persist($critic); // Usually not needed for managed entity modifications but ensures change tracking
                    $linkedCount++;
                }
            }
            
            if ((++$i % 1000) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
        $io->progressFinish();
        
        if ($linkedCount === 0) {
            $io->warning('No genre links were created for critics. Check if genre2recensent IDs match the genre table.');
        } else {
            $io->text(sprintf('Linked %d genres to critics.', $linkedCount));
        }
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
            // Removed catalogueNumber
            // Removed externalUrl
            // Check if 'HD' exists if 'hd' is empty/false, just in case of case sensitivity or user hint
            $hd = $row['hd'] ?? $row['HD'] ?? 0;
            $album->setOwnedByHans((bool)$hd);
            $album->setLabel($row['label'] ?: null);
            
            if ($row['materiaal']) {
                $album->setFormat(AlbumFormat::fromLegacyCode($row['materiaal']));
            } else {
                // Default to CD if material is not set
                $album->setFormat(AlbumFormat::CD);
            }
            
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

    private function importReviews(SymfonyStyle $io): void
    {
        $rows = $this->legacyConnection->fetchAllAssociative('SELECT * FROM recensie');
        $io->progressStart(count($rows));

        $batchSize = 1000;
        $i = 0;

        foreach ($rows as $row) {
            if (!isset($this->albumMap[$row['album_id']])) {
                continue;
            }

            $review = new Review();
            
            // Relations
            $albumRef = $this->entityManager->getReference(Album::class, $this->albumMap[$row['album_id']]);
            $review->setAlbum($albumRef);

            if ($row['recensent_id'] && isset($this->criticMap[$row['recensent_id']])) {
                $criticRef = $this->entityManager->getReference(Critic::class, $this->criticMap[$row['recensent_id']]);
                $review->setCritic($criticRef);
            }

            // Fields
            $review->setRating($row['waardering'] !== null ? (float)$row['waardering'] : null);
            
            // Link to Issue
            if ($row['tijdschrift_id'] && $row['jaar']) {
                $rawNum = $row['nummer'];
                $issueNum = ($rawNum !== '0' && $rawNum !== '' && $rawNum !== null) ? (string)$rawNum : 'n/a';
                
                 $issueKey = sprintf('%d-%d-%s', 
                    $row['tijdschrift_id'], 
                    (int)$row['jaar'], 
                    $issueNum
                );
                
                if (isset($this->issueMap[$issueKey])) {
                    $issueRef = $this->entityManager->getReference(Issue::class, $this->issueMap[$issueKey]);
                    $review->setIssue($issueRef);

                    if ($row['rubriek']) {
                        $legacyRubricId = $row['rubriek'];
                        $review->setLegacyRubric($legacyRubricId);
                        
                        if (isset($this->rubricMap[$legacyRubricId])) {
                            $rubricRef = $this->entityManager->getReference(Rubric::class, $this->rubricMap[$legacyRubricId]);
                            $review->setRubric($rubricRef);
                        }
                    }
                    
                    // Only persist review if issue is found
                    $this->entityManager->persist($review);
                }
            }

            if ((++$i % $batchSize) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
            $io->progressAdvance();
        }

        $this->entityManager->flush();
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
            $list->setExternalUrl($row['url'] ?: null);
            $list->setVisible((bool)$row['zichtbaar']);
            
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
            }

            // Prefer the explicit recensent_id link; fall back to the legacy `bron` name lookup.
            $criticId = null;
            if (!empty($row['recensent_id']) && isset($this->criticMap[(int)$row['recensent_id']])) {
                $criticId = $this->criticMap[(int)$row['recensent_id']];
            } elseif (!$list->getMagazine() && isset($this->criticMap[$bron])) {
                $criticId = $this->criticMap[$bron];
            }
            if ($criticId !== null) {
                $list->setCritic($this->entityManager->getReference(Critic::class, $criticId));
            }

            // Import legacy `soort` (free-text string) as category
            $category = isset($row['soort']) ? trim((string)$row['soort']) : '';
            $list->setCategory($category !== '' ? $category : null);

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
                // Inherit genre from parent list
                $childList->setGenre($parentList->getGenre());
                
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

        // 5. Prune items from aggregate lists
        $io->section('Pruning items from aggregate lists');
        $conn->executeStatement(
            "DELETE FROM album_list_item 
             WHERE album_list_id IN (
                 SELECT id FROM album_list WHERE type = 'aggregate'
             )"
        );
        $io->writeln('Removed stored items from aggregate lists');
    }
}
