<?php

/**
 * Sakila Upstream Pin Manifest
 * Primary Source: bradleygrant/sakila-sqlite3
 * Comparative/Alternative PostgreSQL Reference Source: devrimgunduz/pagila (PostgreSQL port of Sakila)
 *
 * @return array{product: string, repository: string, commit_sha: string, filename: string, digest: string, format: string, alternative_source?: array{repository: string, description: string}}
 */
return [
    'product' => 'sakila',
    'repository' => 'bradleygrant/sakila-sqlite3',
    'commit_sha' => '9394b42',
    'filename' => 'sakila_master.db',
    'digest' => '7b396788e7a04918e957aa0df13b2c1fbdfa47ed3b9347edfa27c62ee27f42c1',
    'format' => 'sqlite_binary',
    'alternative_source' => [
        'repository' => 'devrimgunduz/pagila',
        'description' => 'Official PostgreSQL port of Sakila (Pagila) used as a comparative reference for Postgres-native schema conventions, data types, and constraints.',
    ],
];
