<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration initiale : crée la table commune + l'extension pg_trgm + l'index GIN trgm
 * pour la recherche fuzzy insensible aux accents/ponctuation.
 */
final class Version20260517210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial: commune table + pg_trgm extension + GIN trigram index on name_normalized';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        $this->addSql(<<<'SQL'
            CREATE TABLE commune (
                id UUID NOT NULL,
                code_insee VARCHAR(5) NOT NULL,
                name VARCHAR(160) NOT NULL,
                name_normalized VARCHAR(160) NOT NULL,
                name_slug VARCHAR(180) NOT NULL,
                postal_codes JSON NOT NULL,
                department_code VARCHAR(3) NOT NULL,
                department_name VARCHAR(80) NOT NULL,
                region_code VARCHAR(3) NOT NULL,
                region_name VARCHAR(80) NOT NULL,
                latitude NUMERIC(10, 7) NOT NULL,
                longitude NUMERIC(10, 7) NOT NULL,
                population INT NOT NULL,
                altitude INT DEFAULT NULL,
                climate VARCHAR(32) NOT NULL,
                patrimoine JSON NOT NULL,
                specialite VARCHAR(200) DEFAULT NULL,
                wikipedia_url VARCHAR(500) DEFAULT NULL,
                is_playable BOOLEAN NOT NULL,
                difficulty VARCHAR(16) DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);

        $this->addSql('CREATE UNIQUE INDEX commune_code_insee_uniq ON commune (code_insee)');
        $this->addSql('CREATE UNIQUE INDEX commune_name_slug_uniq ON commune (name_slug)');
        $this->addSql('CREATE INDEX commune_is_playable_idx ON commune (is_playable)');
        $this->addSql('CREATE INDEX commune_department_code_idx ON commune (department_code)');
        $this->addSql('CREATE INDEX commune_name_normalized_trgm_idx ON commune USING gin (name_normalized gin_trgm_ops)');

        $this->addSql("COMMENT ON COLUMN commune.created_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN commune.updated_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN commune.id IS '(DC2Type:uuid)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE commune');
        // pg_trgm est intentionnellement conservée : d'autres tables pourraient en avoir besoin.
    }
}
