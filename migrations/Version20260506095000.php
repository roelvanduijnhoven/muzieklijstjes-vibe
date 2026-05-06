<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506095000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add source_url to review';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE review ADD source_url VARCHAR(2048) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE review DROP source_url');
    }
}

