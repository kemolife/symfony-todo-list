<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404142443 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status and updated_at columns to to_do_list';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE to_do_list ADD status VARCHAR(255) NOT NULL DEFAULT 'pending'");
        $this->addSql('ALTER TABLE to_do_list ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql("COMMENT ON COLUMN to_do_list.status IS '(DC2Type:todo_status)'");
        $this->addSql("COMMENT ON COLUMN to_do_list.updated_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql('ALTER TABLE to_do_list ALTER tag TYPE VARCHAR(100)');
        $this->addSql('ALTER TABLE to_do_list ALTER created_at SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE to_do_list DROP status');
        $this->addSql('ALTER TABLE to_do_list DROP updated_at');
        $this->addSql('ALTER TABLE to_do_list ALTER tag TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE to_do_list ALTER created_at DROP NOT NULL');
    }
}
