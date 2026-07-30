<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Http\Middleware\EnsureTeamMembership;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Route;

covers(EnsureTeamMembership::class);

beforeEach(function () {
    Route::middleware(EnsureTeamMembership::class)
        ->get('/_test/membership/{current_team}', fn () => response('ok'));

    Route::middleware(EnsureTeamMembership::class.':owner')
        ->get('/_test/membership-owner/{current_team}', fn () => response('ok'));
});

test('middleware aborts with 403 when the user is not a member of the team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $this->actingAs($user)
        ->get("/_test/membership/{$team->slug}")
        ->assertForbidden();
});

test('middleware allows pass-through for a team member without a minimum role', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $user->switchTeam($team);

    $this->actingAs($user)
        ->get("/_test/membership/{$team->slug}")
        ->assertOk();
});

test('middleware aborts 403 when the minimum role is not met', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $user->switchTeam($team);

    $this->actingAs($user)
        ->get("/_test/membership-owner/{$team->slug}")
        ->assertForbidden();
});

test('middleware allows pass-through when the minimum role is met', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $user->switchTeam($team);

    $this->actingAs($user)
        ->get("/_test/membership-owner/{$team->slug}")
        ->assertOk();
});

test('middleware aborts 403 when the minimum role string is invalid', function () {
    Route::middleware(EnsureTeamMembership::class.':nonexistent-role')
        ->get('/_test/membership-bad/{current_team}', fn () => response('ok'));

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $user->switchTeam($team);

    $this->actingAs($user)
        ->get("/_test/membership-bad/{$team->slug}")
        ->assertForbidden();
});

test('middleware switches team when current_team route param is not the current team', function () {
    $user = User::factory()->create();
    $personal = $user->personalTeam();
    assert($personal !== null);

    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $user->switchTeam($personal);
    expect($user->current_team_id)->toBe($personal->id);

    $this->actingAs($user)
        ->get("/_test/membership/{$team->slug}")
        ->assertOk();

    $freshUser = $user->fresh();
    assert($freshUser !== null);
    expect($freshUser->current_team_id)->toBe($team->id);
});
