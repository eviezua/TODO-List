<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231206152645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task ADD owner_id INT NOT NULL');
        $this->addSql('CREATE INDEX IDX_527EDB257E3C61F9 ON task (owner_id)');
        $this->addSql('COMMENT ON COLUMN task.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN task.completed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql(
            'ALTER TABLE task ADD CONSTRAINT FK_527EDB257E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX IDX_527EDB257E3C61F9');
        $this->addSql('ALTER TABLE task DROP CONSTRAINT FK_527EDB257E3C61F9');
        $this->addSql('ALTER TABLE task DROP owner_id');
    }
}
