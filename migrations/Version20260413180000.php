<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill two_factor_confirmed=true for users who already have a TOTP secret';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE "user" SET two_factor_confirmed = true WHERE top_secret IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE "user" SET two_factor_confirmed = false WHERE top_secret IS NOT NULL');
    }
}
