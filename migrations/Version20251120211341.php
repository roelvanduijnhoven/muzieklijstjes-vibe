<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251120211341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, album_id INT NOT NULL, critic_id INT DEFAULT NULL, magazine_id INT DEFAULT NULL, year INT DEFAULT NULL, month INT DEFAULT NULL, issue_number VARCHAR(20) DEFAULT NULL, rating DOUBLE PRECISION DEFAULT NULL, rubric VARCHAR(3) DEFAULT NULL, INDEX IDX_794381C61137ABCF (album_id), INDEX IDX_794381C6C7BE2830 (critic_id), INDEX IDX_794381C63EB84A1D (magazine_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C61137ABCF FOREIGN KEY (album_id) REFERENCES album (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6C7BE2830 FOREIGN KEY (critic_id) REFERENCES critic (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C63EB84A1D FOREIGN KEY (magazine_id) REFERENCES magazine (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C61137ABCF');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6C7BE2830');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C63EB84A1D');
        $this->addSql('DROP TABLE review');
    }
}
