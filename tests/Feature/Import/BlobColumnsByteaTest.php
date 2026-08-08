<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('stores northwind.categories.picture as bytea', function () {
    $type = DB::selectOne("
        SELECT format_type(a.atttypid, a.atttypmod) AS type
        FROM pg_attribute a
        JOIN pg_class c ON c.oid = a.attrelid
        JOIN pg_namespace n ON n.oid = c.relnamespace
        WHERE n.nspname = 'northwind' AND c.relname = 'categories' AND a.attname = 'picture'
    ")->type;

    expect($type)->toBe('bytea');
});

it('stores northwind.employees.photo as bytea', function () {
    $type = DB::selectOne("
        SELECT format_type(a.atttypid, a.atttypmod) AS type
        FROM pg_attribute a
        JOIN pg_class c ON c.oid = a.attrelid
        JOIN pg_namespace n ON n.oid = c.relnamespace
        WHERE n.nspname = 'northwind' AND c.relname = 'employees' AND a.attname = 'photo'
    ")->type;

    expect($type)->toBe('bytea');
});

it('stores pagila.staff.picture as bytea', function () {
    $type = DB::selectOne("
        SELECT format_type(a.atttypid, a.atttypmod) AS type
        FROM pg_attribute a
        JOIN pg_class c ON c.oid = a.attrelid
        JOIN pg_namespace n ON n.oid = c.relnamespace
        WHERE n.nspname = 'pagila' AND c.relname = 'staff' AND a.attname = 'picture'
    ")->type;

    expect($type)->toBe('bytea');
});
