<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251227110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Increase music_brainz_id column length to 255';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE album CHANGE music_brainz_id music_brainz_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE artist CHANGE music_brainz_id music_brainz_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE album CHANGE music_brainz_id music_brainz_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE artist CHANGE music_brainz_id music_brainz_id VARCHAR(36) DEFAULT NULL');
    }
}

