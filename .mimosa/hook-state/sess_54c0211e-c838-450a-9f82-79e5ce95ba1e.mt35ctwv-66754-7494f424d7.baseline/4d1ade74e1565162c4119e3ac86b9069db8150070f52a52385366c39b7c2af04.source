<?php

declare(strict_types=1);

use App\Enums\TeamArtefactType;
use App\Models\Team;
use App\Models\TeamArtefact;
use App\Models\User;

covers(TeamArtefact::class);

test('team artefact casts type to enum and configuration to array', function () {
    $artefact = TeamArtefact::factory()->create([
        'type' => TeamArtefactType::SavedSearch,
        'configuration' => ['query' => 'pgvector', 'filters' => ['product' => 'chinook']],
    ]);

    expect($artefact->type)->toBe(TeamArtefactType::SavedSearch)
        ->and($artefact->configuration)->toBe(['query' => 'pgvector', 'filters' => ['product' => 'chinook']])
        ->and($artefact->getRawOriginal('type'))->toBe('saved_search');
});

test('team artefact belongs to a team and a creator', function () {
    $team = Team::factory()->create();
    $creator = User::factory()->create();

    $artefact = TeamArtefact::factory()->for($team)->create(['created_by' => $creator->id]);

    expect($artefact->team_id)->toBe($team->id)
        ->and($artefact->created_by)->toBe($creator->id)
        ->and($artefact->team)->toBeInstanceOf(Team::class)
        ->and($artefact->creator)->toBeInstanceOf(User::class);

    $reloaded = Team::findOrFail($team->id);
    expect($reloaded->artefacts->pluck('id')->toArray())->toContain($artefact->id);
});

test('team artefact supports soft deletes', function () {
    $artefact = TeamArtefact::factory()->create();
    $id = $artefact->id;

    $artefact->delete();

    expect(TeamArtefact::find($id))->toBeNull()
        ->and(TeamArtefact::withTrashed()->find($id))->not->toBeNull();

    $artefact->forceDelete();
    expect(TeamArtefact::withTrashed()->find($id))->toBeNull();
});

test('creator deletion nulls created by but preserves the artefact', function () {
    $creator = User::factory()->create();
    $artefact = TeamArtefact::factory()->create(['created_by' => $creator->id]);

    $creator->delete();

    $reloaded = TeamArtefact::findOrFail($artefact->id);

    expect($reloaded->created_by)->toBeNull()
        ->and($reloaded->creator)->toBeNull();
});

test('force-deleting a team cascades to its artefacts', function () {
    $team = Team::factory()->create();
    $artefact = TeamArtefact::factory()->for($team)->create();

    $team->forceDelete();

    expect(TeamArtefact::find($artefact->id))->toBeNull();
});

test('team artefact type enum exposes filament identity', function (TeamArtefactType $type) {
    expect($type->getLabel())->toBeString()
        ->and($type->getColor())->toBeString()
        ->and($type->getIcon())->not->toBeNull();
})->with(TeamArtefactType::cases());
