<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Schema;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the search_projections table and schema-aware trigger functions.
 *
 * Trigger functions use {@see TG_TABLE_SCHEMA} to write to the correct
 * schema's {@see search_projections} table, so the same function definition
 * works in both live (`chinook`) and staging (`chinook_staging`) schemas.
 */
class SearchProjectionSchema
{
    /**
     * Create the search_projections table in the given schema.
     */
    public static function createTable(string $schema): void
    {
        self::ensureTextSearchConfiguration();

        Schema::create("{$schema}.search_projections", function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity_type');
            $table->text('weight_d_text')->nullable();
            $table->text('weight_c_text')->nullable();
            $table->text('weight_b_text')->nullable();
            $table->text('weight_a_text')->nullable();
            $table->string('embedding_profile')->nullable();
            $table->string('content_digest')->nullable();
            $table->timestamp('embedded_at')->nullable();
            $table->string('embedding_state')->default('pending');
            $table->timestamps();
        });

        DB::statement("
            ALTER TABLE {$schema}.search_projections
            ADD COLUMN document_tsv tsvector GENERATED ALWAYS AS (
                setweight(to_tsvector('public.en_unaccent', coalesce(weight_d_text, '')), 'D') ||
                setweight(to_tsvector('public.en_unaccent', coalesce(weight_c_text, '')), 'C') ||
                setweight(to_tsvector('public.en_unaccent', coalesce(weight_b_text, '')), 'B') ||
                setweight(to_tsvector('public.en_unaccent', coalesce(weight_a_text, '')), 'A')
            ) STORED;
        ");

        DB::statement("
            ALTER TABLE {$schema}.search_projections
            ADD COLUMN embedding public.vector(1024) NULL;
        ");

        DB::statement("CREATE INDEX idx_{$schema}_search_tsv ON {$schema}.search_projections USING GIN (document_tsv);");
        DB::statement("CREATE INDEX idx_{$schema}_search_embedding ON {$schema}.search_projections USING hnsw (embedding public.vector_cosine_ops) WITH (m = 16, ef_construction = 64);");
    }

    private static function ensureTextSearchConfiguration(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
        DB::statement(<<<'SQL'
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_ts_config WHERE cfgname = 'en_unaccent') THEN
                    CREATE TEXT SEARCH CONFIGURATION en_unaccent (COPY = english);
                    ALTER TEXT SEARCH CONFIGURATION en_unaccent
                        ALTER MAPPING FOR word, asciiword, hword, numword WITH unaccent, english_stem;
                END IF;
            END
            $$;
        SQL);
    }

    /**
     * Create schema-aware trigger functions and triggers.
     *
     * @param  string  $schema  The schema name (e.g., 'chinook', 'chinook_staging')
     * @param  array<string, array{type: string, d: string, c: string}>  $tables  Table config: tablename => [type, d_expr, c_expr]
     */
    public static function createTriggers(string $schema, array $tables): void
    {
        foreach ($tables as $table => $config) {
            $fnName = "{$schema}.sync_{$table}_search_projection";
            $trgName = "trg_{$schema}_{$table}_search";
            $entityType = $config['type'];
            $dExpr = $config['d'];
            $cExpr = $config['c'];

            // Schema-aware trigger function using TG_TABLE_SCHEMA
            DB::statement("
                CREATE OR REPLACE FUNCTION {$fnName}()
                RETURNS trigger AS \$\$
                BEGIN
                    IF (TG_OP = 'DELETE') THEN
                        EXECUTE format('DELETE FROM %I.search_projections WHERE id = \$1', TG_TABLE_SCHEMA) USING OLD.id;
                        RETURN OLD;
                    ELSE
                        EXECUTE format(
                            'INSERT INTO %I.search_projections (id, entity_type, weight_d_text, weight_c_text, weight_a_text, embedding_state, created_at, updated_at)
                             VALUES (\$1, \$2, \$3, \$4, \$5, ''pending'', NOW(), NOW())
                             ON CONFLICT (id) DO UPDATE SET
                                 entity_type = EXCLUDED.entity_type,
                                 weight_d_text = EXCLUDED.weight_d_text,
                                 weight_c_text = EXCLUDED.weight_c_text,
                                 weight_a_text = EXCLUDED.weight_a_text,
                                 embedding_state = ''pending'',
                                 updated_at = NOW()',
                            TG_TABLE_SCHEMA
                        ) USING NEW.id, '{$entityType}', {$dExpr}, {$cExpr}, NEW.id::text;
                        RETURN NEW;
                    END IF;
                END;
                \$\$ LANGUAGE plpgsql;
            ");

            DB::statement("
                CREATE TRIGGER {$trgName}
                AFTER INSERT OR UPDATE OR DELETE ON {$schema}.{$table}
                FOR EACH ROW EXECUTE FUNCTION {$fnName}();
            ");
        }
    }

    /**
     * Drop search projection objects from a schema.
     */
    public static function drop(string $schema): void
    {
        Schema::dropIfExists("{$schema}.search_projections");
    }
}
