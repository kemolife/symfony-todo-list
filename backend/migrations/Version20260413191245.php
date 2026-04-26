<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260413191245 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ADD enrollment_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD enrollment_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649DE9CFA39 ON "user" (enrollment_token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8D93D649DE9CFA39');
        $this->addSql('ALTER TABLE "user" DROP enrollment_token');
        $this->addSql('ALTER TABLE "user" DROP enrollment_token_expires_at');
    }
}
