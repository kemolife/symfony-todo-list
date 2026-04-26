<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406103517 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add owner FK to to_do_list';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE to_do_list ADD owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE to_do_list ADD CONSTRAINT FK_4A6048EC7E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_4A6048EC7E3C61F9 ON to_do_list (owner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE to_do_list DROP CONSTRAINT FK_4A6048EC7E3C61F9');
        $this->addSql('DROP INDEX IDX_4A6048EC7E3C61F9');
        $this->addSql('ALTER TABLE to_do_list DROP owner_id');
    }
}
