<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251122091642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE critic_feature (critic_id INT NOT NULL, feature_id INT NOT NULL, INDEX IDX_460EB4BBC7BE2830 (critic_id), INDEX IDX_460EB4BB60E4B879 (feature_id), PRIMARY KEY(critic_id, feature_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feature (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_1FD775665E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE critic_feature ADD CONSTRAINT FK_460EB4BBC7BE2830 FOREIGN KEY (critic_id) REFERENCES critic (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE critic_feature ADD CONSTRAINT FK_460EB4BB60E4B879 FOREIGN KEY (feature_id) REFERENCES feature (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE critic_feature DROP FOREIGN KEY FK_460EB4BBC7BE2830');
        $this->addSql('ALTER TABLE critic_feature DROP FOREIGN KEY FK_460EB4BB60E4B879');
        $this->addSql('DROP TABLE critic_feature');
        $this->addSql('DROP TABLE feature');
    }
}
