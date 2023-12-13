<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231213191440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task DROP CONSTRAINT FK_527EDB25727ACA70');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25727ACA70 FOREIGN KEY (parent_id) REFERENCES task (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE task DROP CONSTRAINT fk_527edb25727aca70');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT fk_527edb25727aca70 FOREIGN KEY (parent_id) REFERENCES task (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
