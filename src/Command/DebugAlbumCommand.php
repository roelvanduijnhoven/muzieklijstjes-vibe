<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:debug-album')]
class DebugAlbumCommand extends Command
{
    public function __construct(
        private Connection $legacyConnection,
        private Connection $defaultConnection, // This is the default (target) connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("--- LEGACY (Source) ---");
        $sql = "SELECT * FROM album WHERE materiaal != '' LIMIT 1";
        $row = $this->legacyConnection->fetchAssociative($sql);
        dump($row);

        $output->writeln("--- DEFAULT (Target) ---");
        try {
            $sql = "SELECT * FROM album LIMIT 1";
            $row = $this->defaultConnection->fetchAssociative($sql);
            dump($row);
        } catch (\Exception $e) {
            $output->writeln("Error reading target: " . $e->getMessage());
        }
        
        return Command::SUCCESS;
    }
}

