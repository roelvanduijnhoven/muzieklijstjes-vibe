<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260504200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add category column to album_list (imported from legacy lijstenB.soort)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE album_list ADD category VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE album_list DROP category');
    }
}
