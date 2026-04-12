<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audit log table for Doctrine-level change tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(16) NOT NULL, entity_type VARCHAR(191) NOT NULL, entity_id VARCHAR(191) DEFAULT NULL, actor_identifier VARCHAR(191) DEFAULT NULL, changes JSON NOT NULL, context JSON DEFAULT NULL, occurred_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_audit_log_occurred_at (occurred_at), INDEX idx_audit_log_entity (entity_type, entity_id, occurred_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE audit_log');
    }
}
