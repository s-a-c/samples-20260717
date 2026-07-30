<?php

declare(strict_types=1);

use App\Concerns\GeneratesUniqueTeamSlugs;
use App\Models\Team;

covers(GeneratesUniqueTeamSlugs::class);

test('generateUniqueTeamSlug returns the default slug when no collisions exist', function () {
    expect(Team::generateUniqueTeamSlug('Brand New Team'))->toBe('brand-new-team');
});

test('generateUniqueTeamSlug excludes the given id when checking collisions', function () {
    $existing = Team::factory()->create(['name' => 'Acme', 'slug' => 'acme']);

    $slug = Team::generateUniqueTeamSlug('Acme', $existing->id);

    expect($slug)->toBe('acme');
});

test('generateUniqueTeamSlug appends a suffix when the default slug is taken', function () {
    Team::factory()->create(['name' => 'Acme', 'slug' => 'acme']);

    $slug = Team::generateUniqueTeamSlug('Acme');

    expect($slug)->toBe('acme-1');
});

test('generateUniqueTeamSlug ignores existing slugs that do not match the numeric suffix pattern', function () {
    Team::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    Team::factory()->create(['name' => 'Acme Weird', 'slug' => 'acme-xyz']);

    $slug = Team::generateUniqueTeamSlug('Acme');

    // 'acme' → suffix 0, 'acme-xyz' → no match (null), so maxSuffix is 0 → acme-1
    expect($slug)->toBe('acme-1');
});

test('generateUniqueTeamSlug finds the highest numeric suffix and increments', function () {
    Team::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    Team::factory()->create(['name' => 'Acme One', 'slug' => 'acme-1']);
    Team::factory()->create(['name' => 'Acme Ten', 'slug' => 'acme-10']);

    expect(Team::generateUniqueTeamSlug('Acme'))->toBe('acme-11');
});

test('generateUniqueTeamSlug considers soft deleted teams', function () {
    Team::factory()->create(['name' => 'Acme', 'slug' => 'acme'])->delete();

    expect(Team::generateUniqueTeamSlug('Acme'))->toBe('acme-1');
});
