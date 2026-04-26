<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260414100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename to_do_list -> todo_list; create todo_item table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE to_do_list RENAME TO todo_list');

        $this->addSql('CREATE TABLE todo_item (
            id SERIAL NOT NULL,
            todo_list_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            is_completed BOOLEAN NOT NULL,
            position INT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('COMMENT ON COLUMN todo_item.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE todo_item ADD CONSTRAINT FK_todo_item_todo_list FOREIGN KEY (todo_list_id) REFERENCES todo_list(id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_todo_item_todo_list_id ON todo_item (todo_list_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE todo_item DROP CONSTRAINT FK_todo_item_todo_list');
        $this->addSql('DROP TABLE todo_item');
        $this->addSql('ALTER TABLE todo_list RENAME TO to_do_list');
    }
}
