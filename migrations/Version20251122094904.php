<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251122094904 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album ADD catalogue_number VARCHAR(20) DEFAULT NULL, ADD format VARCHAR(255) DEFAULT NULL, ADD external_url VARCHAR(255) DEFAULT NULL, ADD owned_by_hans TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE album_list ADD external_url VARCHAR(255) DEFAULT NULL, ADD visible TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE critic ADD birth_year INT DEFAULT NULL, ADD death_year INT DEFAULT NULL, ADD url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE magazine ADD abbreviation VARCHAR(10) DEFAULT NULL, ADD rating SMALLINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album DROP catalogue_number, DROP format, DROP external_url, DROP owned_by_hans');
        $this->addSql('ALTER TABLE critic DROP birth_year, DROP death_year, DROP url');
        $this->addSql('ALTER TABLE magazine DROP abbreviation, DROP rating');
        $this->addSql('ALTER TABLE album_list DROP external_url, DROP visible');
    }
}
