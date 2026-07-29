<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('northwind.search_projections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity_type');
            $table->text('weight_d_text')->nullable();
            $table->text('weight_c_text')->nullable();
            $table->text('weight_b_text')->nullable();
            $table->text('weight_a_text')->nullable();
            $table->string('embedding_profile')->nullable();
            $table->string('content_digest')->nullable();
            $table->timestamp('embedded_at')->nullable();
            $table->string('embedding_state')->default('pending'); // pending|complete|failed|mismatched|lexical_only
            $table->timestamps();
        });

        DB::statement("
            ALTER TABLE northwind.search_projections
            ADD COLUMN document_tsv tsvector GENERATED ALWAYS AS (
                setweight(to_tsvector('en_unaccent', coalesce(weight_d_text, '')), 'D') ||
                setweight(to_tsvector('en_unaccent', coalesce(weight_c_text, '')), 'C') ||
                setweight(to_tsvector('en_unaccent', coalesce(weight_b_text, '')), 'B') ||
                setweight(to_tsvector('en_unaccent', coalesce(weight_a_text, '')), 'A')
            ) STORED;
        ");

        DB::statement('
            ALTER TABLE northwind.search_projections
            ADD COLUMN embedding vector(1024) NULL;
        ');

        DB::statement('CREATE INDEX idx_northwind_search_tsv ON northwind.search_projections USING GIN (document_tsv);');
        DB::statement('CREATE INDEX idx_northwind_search_embedding ON northwind.search_projections USING hnsw (embedding vector_cosine_ops) WITH (m = 16, ef_construction = 64);');

        $tables = [
            'products' => ['type' => 'product', 'd' => 'NEW.product_name', 'c' => 'NULL'],
            'categories' => ['type' => 'category', 'd' => 'NEW.category_name', 'c' => 'NEW.description'],
            'customers' => ['type' => 'customer', 'd' => 'NEW.company_name', 'c' => 'NEW.contact_name'],
            'employees' => ['type' => 'employee', 'd' => "NEW.first_name || ' ' || NEW.last_name", 'c' => 'NEW.title'],
            'suppliers' => ['type' => 'supplier', 'd' => 'NEW.company_name', 'c' => 'NEW.contact_name'],
            'shippers' => ['type' => 'shipper', 'd' => 'NEW.company_name', 'c' => 'NULL'],
            'orders' => ['type' => 'order', 'd' => 'NEW.ship_name', 'c' => 'NULL'],
            'regions' => ['type' => 'region', 'd' => 'NEW.region_description', 'c' => 'NULL'],
            'territories' => ['type' => 'territory', 'd' => 'NEW.territory_description', 'c' => 'NULL'],
        ];

        foreach ($tables as $table => $config) {
            $fnName = "northwind.sync_{$table}_search_projection";
            $trgName = "trg_northwind_{$table}_search";
            $entityType = $config['type'];
            $dExpr = $config['d'];
            $cExpr = $config['c'];

            DB::statement("
                CREATE OR REPLACE FUNCTION {$fnName}()
                RETURNS trigger AS $$
                BEGIN
                    IF (TG_OP = 'DELETE') THEN
                        DELETE FROM northwind.search_projections WHERE id = OLD.id;
                        RETURN OLD;
                    ELSE
                        INSERT INTO northwind.search_projections (id, entity_type, weight_d_text, weight_c_text, weight_a_text, embedding_state, created_at, updated_at)
                        VALUES (NEW.id, '{$entityType}', {$dExpr}, {$cExpr}, NEW.id::text, 'pending', NOW(), NOW())
                        ON CONFLICT (id) DO UPDATE SET
                            entity_type = EXCLUDED.entity_type,
                            weight_d_text = EXCLUDED.weight_d_text,
                            weight_c_text = EXCLUDED.weight_c_text,
                            weight_a_text = EXCLUDED.weight_a_text,
                            embedding_state = 'pending',
                            updated_at = NOW();
                        RETURN NEW;
                    END IF;
                END;
                $$ LANGUAGE plpgsql;
            ");

            DB::statement("
                CREATE TRIGGER {$trgName}
                AFTER INSERT OR UPDATE OR DELETE ON northwind.{$table}
                FOR EACH ROW EXECUTE FUNCTION {$fnName}();
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('northwind.search_projections');
    }
};
