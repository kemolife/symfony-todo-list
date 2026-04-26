<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260426172101 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move api_key from user table to dedicated api_key table with per-key permissions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE api_key (
            id SERIAL NOT NULL,
            user_id INT NOT NULL,
            key_value VARCHAR(64) NOT NULL,
            name VARCHAR(100) NOT NULL,
            permissions JSON NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            last_used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C912ED9D_KEY_VALUE ON api_key (key_value)');
        $this->addSql('CREATE INDEX IDX_API_KEY_USER_ID ON api_key (user_id)');
        $this->addSql('ALTER TABLE api_key ADD CONSTRAINT FK_API_KEY_USER FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("INSERT INTO api_key (user_id, key_value, name, permissions, created_at)
            SELECT id, api_key, 'Default', '[\"read\",\"create\",\"update\",\"delete\"]', NOW()
            FROM \"user\" WHERE api_key IS NOT NULL");

        $this->addSql('DROP INDEX UNIQ_8D93D649C912ED9D');
        $this->addSql('ALTER TABLE "user" DROP COLUMN api_key');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD api_key VARCHAR(36) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649C912ED9D ON "user" (api_key)');
        $this->addSql('UPDATE "user" u SET api_key = (
            SELECT LEFT(key_value, 36) FROM api_key a WHERE a.user_id = u.id ORDER BY created_at DESC LIMIT 1
        )');
        $this->addSql('ALTER TABLE api_key DROP CONSTRAINT FK_API_KEY_USER');
        $this->addSql('DROP TABLE api_key');
    }
}
