<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404151015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE to_do_list ALTER status DROP DEFAULT');
        $this->addSql('ALTER TABLE to_do_list ALTER updated_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN to_do_list.status IS \'\'');
        $this->addSql('COMMENT ON COLUMN to_do_list.updated_at IS \'\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE to_do_list ALTER status SET DEFAULT \'pending\'');
        $this->addSql('ALTER TABLE to_do_list ALTER updated_at SET DEFAULT \'now()\'');
        $this->addSql('COMMENT ON COLUMN to_do_list.status IS \'(DC2Type:todo_status)\'');
        $this->addSql('COMMENT ON COLUMN to_do_list.updated_at IS \'(DC2Type:datetime_immutable)\'');
    }
}
