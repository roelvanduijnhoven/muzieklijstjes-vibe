<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251227100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add music_brainz_id to artist table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE artist ADD music_brainz_id VARCHAR(36) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE artist DROP music_brainz_id');
    }
}

