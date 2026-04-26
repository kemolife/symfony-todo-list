<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260426172951 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Merge create+update permissions into single write permission in api_key table';
    }

    public function up(Schema $schema): void
    {
        // Remove 'create' and 'update' entries, add 'write' if either was present
        $this->addSql("
            UPDATE api_key
            SET permissions = (
                SELECT COALESCE(
                    (
                        SELECT jsonb_agg(elem)
                        FROM jsonb_array_elements_text(permissions::jsonb) AS elem
                        WHERE elem NOT IN ('create', 'update')
                    ),
                    '[]'::jsonb
                ) ||
                CASE
                    WHEN permissions::jsonb @> '[\"create\"]' OR permissions::jsonb @> '[\"update\"]'
                    THEN '[\"write\"]'::jsonb
                    ELSE '[]'::jsonb
                END
            )::json
        ");
    }

    public function down(Schema $schema): void
    {
        // Expand 'write' back into 'create' + 'update'
        $this->addSql("
            UPDATE api_key
            SET permissions = (
                SELECT COALESCE(
                    (
                        SELECT jsonb_agg(elem)
                        FROM jsonb_array_elements_text(permissions::jsonb) AS elem
                        WHERE elem != 'write'
                    ),
                    '[]'::jsonb
                ) ||
                CASE
                    WHEN permissions::jsonb @> '[\"write\"]'
                    THEN '[\"create\",\"update\"]'::jsonb
                    ELSE '[]'::jsonb
                END
            )::json
        ");
    }
}
