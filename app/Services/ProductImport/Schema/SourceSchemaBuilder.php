<?php

declare(strict_types=1);

namespace App\Services\ProductImport\Schema;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Build upstream-shaped source schemas for test fixtures.
 *
 * The source schema mirrors the original upstream structure (integer PKs,
 * original table/column names) so that source SQL dumps can be loaded directly.
 * Mappers then transform from source to staging.
 */
class SourceSchemaBuilder
{
    /**
     * Create an empty upstream-shaped source schema for a product dump.
     *
     * The dump loader owns table creation; this method only establishes the
     * isolated schema and makes reruns deterministic.
     *
     * @throws InvalidArgumentException
     */
    public static function create(string $product): string
    {
        if (! in_array($product, ['chinook', 'northwind', 'pagila'], true)) {
            throw new InvalidArgumentException("Unknown product: {$product}");
        }

        $schema = "{$product}_source";
        DB::statement("DROP SCHEMA IF EXISTS {$schema} CASCADE");
        DB::statement("CREATE SCHEMA {$schema}");

        return $schema;
    }

    /**
     * Build the Chinook source schema with upstream-shaped tables.
     */
    public static function buildChinook(): void
    {
        self::create('chinook');

        DB::statement('CREATE TABLE chinook_source.artist (artist_id INTEGER PRIMARY KEY, name TEXT)');
        DB::statement('CREATE TABLE chinook_source.album (album_id INTEGER PRIMARY KEY, title TEXT, artist_id INTEGER)');
        DB::statement('CREATE TABLE chinook_source.genre (genre_id INTEGER PRIMARY KEY, name TEXT)');
        DB::statement('CREATE TABLE chinook_source.media_type (media_type_id INTEGER PRIMARY KEY, name TEXT)');
        DB::statement('CREATE TABLE chinook_source.employee (employee_id INTEGER PRIMARY KEY, last_name TEXT, first_name TEXT, title TEXT, reports_to INTEGER, birth_date TEXT, hire_date TEXT, address TEXT, city TEXT, state TEXT, country TEXT, postal_code TEXT, phone TEXT, fax TEXT, email TEXT)');
        DB::statement('CREATE TABLE chinook_source.customer (customer_id INTEGER PRIMARY KEY, first_name TEXT, last_name TEXT, company TEXT, address TEXT, city TEXT, state TEXT, country TEXT, postal_code TEXT, phone TEXT, fax TEXT, email TEXT, support_rep_id INTEGER)');
        DB::statement('CREATE TABLE chinook_source.track (track_id INTEGER PRIMARY KEY, name TEXT, album_id INTEGER, media_type_id INTEGER, genre_id INTEGER, composer TEXT, milliseconds INTEGER, bytes INTEGER, unit_price REAL)');
        DB::statement('CREATE TABLE chinook_source.playlist (playlist_id INTEGER PRIMARY KEY, name TEXT)');
        DB::statement('CREATE TABLE chinook_source.playlist_track (playlist_id INTEGER, track_id INTEGER)');
        DB::statement('CREATE TABLE chinook_source.invoice (invoice_id INTEGER PRIMARY KEY, customer_id INTEGER, invoice_date TEXT, billing_address TEXT, billing_city TEXT, billing_state TEXT, billing_country TEXT, billing_postal_code TEXT, total REAL)');
        DB::statement('CREATE TABLE chinook_source.invoice_line (invoice_line_id INTEGER PRIMARY KEY, invoice_id INTEGER, track_id INTEGER, unit_price REAL, quantity INTEGER)');
    }
    /**
     * Build the Northwind source schema with upstream-shaped tables.
     */
    public static function buildNorthwind(): void
    {
        self::create('northwind');

        DB::statement('CREATE TABLE northwind_source.categories (category_id SERIAL PRIMARY KEY, category_name TEXT, description TEXT, picture BYTEA)');
        DB::statement('CREATE TABLE northwind_source.customers (customer_id TEXT PRIMARY KEY, company_name TEXT, contact_name TEXT, contact_title TEXT, address TEXT, city TEXT, region TEXT, postal_code TEXT, country TEXT, phone TEXT, fax TEXT)');
        DB::statement('CREATE TABLE northwind_source.employees (employee_id SERIAL PRIMARY KEY, last_name TEXT, first_name TEXT, title TEXT, title_of_courtesy TEXT, birth_date TEXT, hire_date TEXT, address TEXT, city TEXT, region TEXT, postal_code TEXT, country TEXT, home_phone TEXT, extension TEXT, photo BYTEA, notes TEXT, reports_to INTEGER, photo_path TEXT)');
        DB::statement('CREATE TABLE northwind_source.suppliers (supplier_id SERIAL PRIMARY KEY, company_name TEXT, contact_name TEXT, contact_title TEXT, address TEXT, city TEXT, region TEXT, postal_code TEXT, country TEXT, phone TEXT, fax TEXT, homepage TEXT)');
        DB::statement('CREATE TABLE northwind_source.shippers (ShipperID SERIAL PRIMARY KEY, CompanyName TEXT, Phone TEXT)');
        DB::statement('CREATE TABLE northwind_source.regions (RegionID SERIAL PRIMARY KEY, RegionDescription TEXT)');
        DB::statement('CREATE TABLE northwind_source.territories (TerritoryID TEXT PRIMARY KEY, TerritoryDescription TEXT, RegionID INTEGER)');
        DB::statement('CREATE TABLE northwind_source.products (product_id SERIAL PRIMARY KEY, product_name TEXT, supplier_id INTEGER, category_id INTEGER, quantity_per_unit TEXT, unit_price REAL, units_in_stock INTEGER, units_on_order INTEGER, reorder_level INTEGER, discontinued INTEGER)');
        DB::statement('CREATE TABLE northwind_source.orders (OrderID SERIAL PRIMARY KEY, CustomerID TEXT, EmployeeID INTEGER, OrderDate TEXT, RequiredDate TEXT, ShippedDate TEXT, ShipVia INTEGER, Freight REAL, ShipName TEXT, ShipAddress TEXT, ShipCity TEXT, ShipRegion TEXT, ShipPostalCode TEXT, ShipCountry TEXT)');
    }

    /**
     * Build the Pagila source schema with upstream-shaped tables.
     */
    public static function buildPagila(): void
    {
        self::create('pagila');

        DB::statement('CREATE TABLE pagila_source.country (country_id SERIAL PRIMARY KEY, country TEXT)');
        DB::statement('CREATE TABLE pagila_source.city (city_id SERIAL PRIMARY KEY, city TEXT, country_id INTEGER)');
        DB::statement('CREATE TABLE pagila_source.category (category_id SERIAL PRIMARY KEY, name TEXT)');
        DB::statement('CREATE TABLE pagila_source.language (language_id SERIAL PRIMARY KEY, name TEXT)');
        DB::statement('CREATE TABLE pagila_source.actor (actor_id SERIAL PRIMARY KEY, first_name TEXT, last_name TEXT)');
        DB::statement('CREATE TABLE pagila_source.store (store_id SERIAL PRIMARY KEY, manager_staff_id INTEGER)');
        DB::statement('CREATE TABLE pagila_source.staff (staff_id SERIAL PRIMARY KEY, first_name TEXT, last_name TEXT, email TEXT, store_id INTEGER, active BOOLEAN, username TEXT, password TEXT)');
        DB::statement('CREATE TABLE pagila_source.film (film_id SERIAL PRIMARY KEY, title TEXT, description TEXT, release_year INTEGER, language_id INTEGER, rental_duration INTEGER, rental_rate REAL, length INTEGER, replacement_cost REAL, rating TEXT, special_features TEXT)');
        DB::statement('CREATE TABLE pagila_source.customer (customer_id SERIAL PRIMARY KEY, store_id INTEGER, first_name TEXT, last_name TEXT, email TEXT, active BOOLEAN)');
    }

}
