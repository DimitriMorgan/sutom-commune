<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260518100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Analytics event table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE analytics_event (
                id UUID NOT NULL,
                type VARCHAR(64) NOT NULL,
                session_hash VARCHAR(64) NOT NULL,
                payload JSON NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);
        $this->addSql('CREATE INDEX analytics_event_type_idx ON analytics_event (type)');
        $this->addSql('CREATE INDEX analytics_event_created_at_idx ON analytics_event (created_at)');
        $this->addSql("COMMENT ON COLUMN analytics_event.id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN analytics_event.created_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE analytics_event');
    }
}
