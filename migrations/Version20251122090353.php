<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251122090353 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE critic_genre (critic_id INT NOT NULL, genre_id INT NOT NULL, INDEX IDX_CFBABDE9C7BE2830 (critic_id), INDEX IDX_CFBABDE94296D31F (genre_id), PRIMARY KEY(critic_id, genre_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE genre (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_835033F85E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE critic_genre ADD CONSTRAINT FK_CFBABDE9C7BE2830 FOREIGN KEY (critic_id) REFERENCES critic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE critic_genre ADD CONSTRAINT FK_CFBABDE94296D31F FOREIGN KEY (genre_id) REFERENCES genre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE album_list ADD genre_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE album_list ADD CONSTRAINT FK_20C34E584296D31F FOREIGN KEY (genre_id) REFERENCES genre (id)');
        $this->addSql('CREATE INDEX IDX_20C34E584296D31F ON album_list (genre_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album_list DROP FOREIGN KEY FK_20C34E584296D31F');
        $this->addSql('ALTER TABLE critic_genre DROP FOREIGN KEY FK_CFBABDE9C7BE2830');
        $this->addSql('ALTER TABLE critic_genre DROP FOREIGN KEY FK_CFBABDE94296D31F');
        $this->addSql('DROP TABLE critic_genre');
        $this->addSql('DROP TABLE genre');
        $this->addSql('DROP INDEX IDX_20C34E584296D31F ON album_list');
        $this->addSql('ALTER TABLE album_list DROP genre_id');
    }
}
