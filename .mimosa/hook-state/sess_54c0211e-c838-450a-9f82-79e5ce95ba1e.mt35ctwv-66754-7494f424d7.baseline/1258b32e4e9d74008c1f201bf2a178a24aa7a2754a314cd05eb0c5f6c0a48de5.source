<?php

declare(strict_types=1);

use App\Concerns\HasTeams;
use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

covers(HasTeams::class);

test('ownedTeams returns only teams where the user has the owner role', function () {
    $user = User::factory()->create();

    $ownedTeam = Team::factory()->create(['name' => 'Owned Team']);
    $ownedTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $memberTeam = Team::factory()->create(['name' => 'Member Team']);
    $memberTeam->members()->attach($user, ['role' => TeamRole::Member->value]);

    $ownedIds = $user->ownedTeams->pluck('id');

    expect($ownedIds)->toContain($ownedTeam->id)
        ->and($ownedIds)->toContain($user->personalTeam()->id)
        ->and($ownedIds)->not->toContain($memberTeam->id);
});

test('switchTeam returns false when the user does not belong to the team', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    expect($user->switchTeam($otherTeam))->toBeFalse();
});

test('ownsTeam returns true for owned teams and false otherwise', function () {
    $user = User::factory()->create();

    $ownedTeam = Team::factory()->create();
    $ownedTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $memberTeam = Team::factory()->create();
    $memberTeam->members()->attach($user, ['role' => TeamRole::Member->value]);

    expect($user->ownsTeam($ownedTeam))->toBeTrue()
        ->and($user->ownsTeam($memberTeam))->toBeFalse();
});

test('toTeamPermissions grants all permissions to an owner', function () {
    $user = User::factory()->create();
    $ownedTeam = Team::factory()->create();
    $ownedTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $permissions = $user->toTeamPermissions($ownedTeam);

    expect($permissions->canUpdateTeam)->toBeTrue()
        ->and($permissions->canDeleteTeam)->toBeTrue()
        ->and($permissions->canAddMember)->toBeTrue()
        ->and($permissions->canUpdateMember)->toBeTrue()
        ->and($permissions->canRemoveMember)->toBeTrue()
        ->and($permissions->canCreateInvitation)->toBeTrue()
        ->and($permissions->canCancelInvitation)->toBeTrue();
});

test('toTeamPermissions denies all permissions to a member', function () {
    $user = User::factory()->create();
    $memberTeam = Team::factory()->create();
    $memberTeam->members()->attach($user, ['role' => TeamRole::Member->value]);

    $permissions = $user->toTeamPermissions($memberTeam);

    expect($permissions->canUpdateTeam)->toBeFalse()
        ->and($permissions->canDeleteTeam)->toBeFalse()
        ->and($permissions->canAddMember)->toBeFalse()
        ->and($permissions->canUpdateMember)->toBeFalse()
        ->and($permissions->canRemoveMember)->toBeFalse()
        ->and($permissions->canCreateInvitation)->toBeFalse()
        ->and($permissions->canCancelInvitation)->toBeFalse();
});

test('toTeamPermissions falls back to false when the user has no role on the team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $permissions = $user->toTeamPermissions($team);

    expect($permissions->canUpdateTeam)->toBeFalse()
        ->and($permissions->canDeleteTeam)->toBeFalse()
        ->and($permissions->canAddMember)->toBeFalse();
});

test('fallbackTeam returns the alphabetically first team without excluding', function () {
    $user = User::factory()->create();

    $zulu = Team::factory()->create(['name' => 'Zulu Team']);
    $zulu->members()->attach($user, ['role' => TeamRole::Member->value]);

    $alpha = Team::factory()->create(['name' => 'Alpha Team']);
    $alpha->members()->attach($user, ['role' => TeamRole::Member->value]);

    expect($user->fallbackTeam()?->id)->toBe($alpha->id);
});

test('fallbackTeam excludes the given team', function () {
    $user = User::factory()->create();

    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $fallback = $user->fallbackTeam($team);

    expect($fallback)->not->toBeNull()
        ->and($fallback->id)->not->toBe($team->id);
});

test('hasTeamPermission reflects the users role', function () {
    $user = User::factory()->create();

    $ownedTeam = Team::factory()->create();
    $ownedTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $memberTeam = Team::factory()->create();
    $memberTeam->members()->attach($user, ['role' => TeamRole::Member->value]);

    $unrelatedTeam = Team::factory()->create();

    expect($user->hasTeamPermission($ownedTeam, TeamPermission::UpdateTeam))->toBeTrue()
        ->and($user->hasTeamPermission($memberTeam, TeamPermission::UpdateTeam))->toBeFalse()
        ->and($user->hasTeamPermission($unrelatedTeam, TeamPermission::UpdateTeam))->toBeFalse();
});
