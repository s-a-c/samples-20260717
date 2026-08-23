<?php

declare(strict_types=1);

namespace App\Services\ProductImport;

use App\Services\ProductImport\Schema\SearchProjectionSchema;
use Illuminate\Support\Facades\DB;
use PDO;
use InvalidArgumentException;

/**
 * Build migration-backed staging schemas for product imports.
 *
 * Creates {@see <product>_staging} with the same app-shaped table structure
 * as the live {@see <product>} schema, plus schema-local search projections
 * and triggers. Staging tables are created via {@see CREATE TABLE ... (LIKE ...)}
 * to avoid duplicating migration DDL.
 *
 * Upstream data lives in {@see <product>_source}; staging is app-shaped.
 */
class StagingSchemaBuilder
{
    /**
     * Search projection table configurations per product.
     *
     * @return array<string, array<string, array{type: string, d: string, c: string}>>
     */
    public static function projectionConfigs(): array
    {
        return [
            'chinook' => [
                'artists' => ['type' => 'artist', 'd' => 'NEW.name', 'c' => 'NULL'],
                'albums' => ['type' => 'album', 'd' => 'NEW.title', 'c' => 'NULL'],
                'tracks' => ['type' => 'track', 'd' => 'NEW.name', 'c' => 'NEW.composer'],
                'customers' => ['type' => 'customer', 'd' => "NEW.first_name || ' ' || NEW.last_name", 'c' => 'NEW.company'],
                'employees' => ['type' => 'employee', 'd' => "NEW.first_name || ' ' || NEW.last_name", 'c' => 'NEW.title'],
                'playlists' => ['type' => 'playlist', 'd' => 'NEW.name', 'c' => 'NULL'],
                'genres' => ['type' => 'genre', 'd' => 'NEW.name', 'c' => 'NULL'],
                'media_types' => ['type' => 'media_type', 'd' => 'NEW.name', 'c' => 'NULL'],
                'invoices' => ['type' => 'invoice', 'd' => "'Invoice #' || NEW.id::text", 'c' => 'NULL'],
            ],
            'northwind' => [
                'products' => ['type' => 'product', 'd' => 'NEW.product_name', 'c' => 'NULL'],
                'categories' => ['type' => 'category', 'd' => 'NEW.category_name', 'c' => 'NEW.description'],
                'customers' => ['type' => 'customer', 'd' => 'NEW.company_name', 'c' => 'NEW.contact_name'],
                'employees' => ['type' => 'employee', 'd' => "NEW.first_name || ' ' || NEW.last_name", 'c' => 'NEW.title'],
                'suppliers' => ['type' => 'supplier', 'd' => 'NEW.company_name', 'c' => 'NEW.contact_name'],
                'shippers' => ['type' => 'shipper', 'd' => 'NEW.company_name', 'c' => 'NULL'],
                'orders' => ['type' => 'order', 'd' => 'NEW.ship_name', 'c' => 'NULL'],
                'regions' => ['type' => 'region', 'd' => 'NEW.region_description', 'c' => 'NULL'],
                'territories' => ['type' => 'territory', 'd' => 'NEW.territory_description', 'c' => 'NULL'],
            ],
            'pagila' => [
                'films' => ['type' => 'film', 'd' => 'NEW.title', 'c' => 'NEW.description'],
                'actors' => ['type' => 'actor', 'd' => "NEW.first_name || ' ' || NEW.last_name", 'c' => 'NULL'],
                'categories' => ['type' => 'category', 'd' => 'NEW.name', 'c' => 'NULL'],
                'customers' => ['type' => 'customer', 'd' => "NEW.first_name || ' ' || NEW.last_name", 'c' => 'NEW.email'],
                'staff' => ['type' => 'staff', 'd' => "NEW.first_name || ' ' || NEW.last_name", 'c' => 'NEW.email'],
                'stores' => ['type' => 'store', 'd' => "'Store ' || NEW.id::text", 'c' => 'NULL'],
                'languages' => ['type' => 'language', 'd' => 'NEW.name', 'c' => 'NULL'],
                'cities' => ['type' => 'city', 'd' => 'NEW.city', 'c' => 'NULL'],
                'countries' => ['type' => 'country', 'd' => 'NEW.country', 'c' => 'NULL'],
            ],
        ];
    }

    /**
     * Build the staging schema for a product.
     *
     * Creates {@see <product>_staging} with app-shaped tables copied from the
     * live schema, search projections, and schema-aware triggers.
     *
     * @param  string  $product  Product key: chinook, northwind, or pagila
     *
     * @throws InvalidArgumentException If the product is unknown
     */
    public function build(string $product): void
    {
        $configs = self::projectionConfigs();

        if (! isset($configs[$product])) {
            throw new InvalidArgumentException("Unknown product: {$product}");
        }

        $liveSchema = $product;
        $stagingSchema = "{$product}_staging";

        // Drop and recreate staging schema
        DB::statement("DROP SCHEMA IF EXISTS {$stagingSchema} CASCADE");
        DB::statement("CREATE SCHEMA {$stagingSchema}");

        // Copy table structures from live schema (LIKE ... INCLUDING ALL)
        // This copies columns, defaults, constraints (CHECK/NOT NULL), and indexes
        // but NOT foreign keys or triggers.
        $pdo = DB::getPdo();
        $stmt = $pdo->prepare(
            "SELECT tablename FROM pg_tables WHERE schemaname = :schema AND tablename != 'search_projections' ORDER BY tablename"
        );
        $stmt->execute([':schema' => $liveSchema]);
        /** @var list<string> $tableNames */
        $tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tableNames as $tbl) {
            DB::statement(
                "CREATE TABLE {$stagingSchema}.{$tbl} (LIKE {$liveSchema}.{$tbl} INCLUDING ALL)"
            );
        }

        // Create search projections table and schema-aware triggers
        SearchProjectionSchema::createTable($stagingSchema);
        SearchProjectionSchema::createTriggers($stagingSchema, $configs[$product]);
    }

    /**
     * Drop the staging schema for a product.
     */
    public function drop(string $product): void
    {
        $stagingSchema = "{$product}_staging";
        DB::statement("DROP SCHEMA IF EXISTS {$stagingSchema} CASCADE");
    }

    /**
     * Drop the source schema for a product.
     */
    public function dropSource(string $product): void
    {
        $sourceSchema = "{$product}_source";
        DB::statement("DROP SCHEMA IF EXISTS {$sourceSchema} CASCADE");
    }
}
