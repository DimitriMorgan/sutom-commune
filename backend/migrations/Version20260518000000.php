<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260518000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Daily puzzle + guess event tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE daily_puzzle (
                id UUID NOT NULL,
                commune_id UUID NOT NULL,
                puzzle_date DATE NOT NULL,
                puzzle_number INT NOT NULL,
                theme VARCHAR(80) DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);

        $this->addSql('CREATE UNIQUE INDEX daily_puzzle_date_uniq ON daily_puzzle (puzzle_date)');
        $this->addSql('CREATE UNIQUE INDEX daily_puzzle_number_uniq ON daily_puzzle (puzzle_number)');
        $this->addSql('CREATE INDEX IDX_daily_puzzle_commune ON daily_puzzle (commune_id)');
        $this->addSql('ALTER TABLE daily_puzzle ADD CONSTRAINT FK_daily_puzzle_commune FOREIGN KEY (commune_id) REFERENCES commune (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("COMMENT ON COLUMN daily_puzzle.id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN daily_puzzle.commune_id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN daily_puzzle.puzzle_date IS '(DC2Type:date_immutable)'");
        $this->addSql("COMMENT ON COLUMN daily_puzzle.created_at IS '(DC2Type:datetime_immutable)'");

        $this->addSql(<<<'SQL'
            CREATE TABLE guess_event (
                id UUID NOT NULL,
                puzzle_id UUID NOT NULL,
                guess_commune_id UUID DEFAULT NULL,
                session_hash VARCHAR(64) NOT NULL,
                attempt_index INT NOT NULL,
                is_correct BOOLEAN NOT NULL,
                distance_km INT DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);

        $this->addSql('CREATE INDEX guess_event_puzzle_session_idx ON guess_event (puzzle_id, session_hash)');
        $this->addSql('CREATE INDEX IDX_guess_event_puzzle ON guess_event (puzzle_id)');
        $this->addSql('CREATE INDEX IDX_guess_event_guess_commune ON guess_event (guess_commune_id)');
        $this->addSql('ALTER TABLE guess_event ADD CONSTRAINT FK_guess_event_puzzle FOREIGN KEY (puzzle_id) REFERENCES daily_puzzle (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE guess_event ADD CONSTRAINT FK_guess_event_guess_commune FOREIGN KEY (guess_commune_id) REFERENCES commune (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("COMMENT ON COLUMN guess_event.id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN guess_event.puzzle_id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN guess_event.guess_commune_id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN guess_event.created_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE guess_event');
        $this->addSql('DROP TABLE daily_puzzle');
    }
}
