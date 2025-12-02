<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251202211748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review ADD rubric_id INT DEFAULT NULL, CHANGE rubric legacy_rubric VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6A29EC0FC FOREIGN KEY (rubric_id) REFERENCES rubric (id)');
        $this->addSql('CREATE INDEX IDX_794381C6A29EC0FC ON review (rubric_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6A29EC0FC');
        $this->addSql('DROP INDEX IDX_794381C6A29EC0FC ON review');
        $this->addSql('ALTER TABLE review DROP rubric_id, CHANGE legacy_rubric rubric VARCHAR(3) DEFAULT NULL');
    }
}
