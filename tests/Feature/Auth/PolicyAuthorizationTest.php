<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Chinook\Chinook;
use App\Models\Northwind\Northwind;
use App\Models\Team;
use App\Models\User;
use App\Policies\ChinookPolicy;
use App\Policies\NorthwindPolicy;
use App\Policies\PagilaPolicy;
use App\Policies\TeamPolicy;
use Spatie\Permission\Models\Role;
use stdClass;

covers(
    ChinookPolicy::class,
    NorthwindPolicy::class,
    PagilaPolicy::class,
    TeamPolicy::class,
);

// ---------------------------------------------------------------------------
// Product Policies — ChinookPolicy
// ---------------------------------------------------------------------------

test('chinook policy grants viewAny to chinook curator', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('chinook_curator', 'web'));

    expect((new ChinookPolicy)->viewAny($user))->toBeTrue();
});

test('chinook policy grants viewAny to super admin', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('super_admin', 'web'));

    expect((new ChinookPolicy)->viewAny($user))->toBeTrue();
});

test('chinook policy denies viewAny to unauthorized user', function () {
    $user = User::factory()->create();

    expect((new ChinookPolicy)->viewAny($user))->toBeFalse();
});

test('chinook policy view grants and denies correctly', function () {
    $curator = User::factory()->create();
    $curator->assignRole(Role::findOrCreate('chinook_curator', 'web'));

    $unauthorized = User::factory()->create();

    $model = new Chinook;

    expect((new ChinookPolicy)->view($curator, $model))->toBeTrue();
    expect((new ChinookPolicy)->view($unauthorized, $model))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Product Policies — NorthwindPolicy
// ---------------------------------------------------------------------------

test('northwind policy grants viewAny to northwind curator', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('northwind_curator', 'web'));

    expect((new NorthwindPolicy)->viewAny($user))->toBeTrue();
});

test('northwind policy denies viewAny to unauthorized user', function () {
    $user = User::factory()->create();

    expect((new NorthwindPolicy)->viewAny($user))->toBeFalse();
});

test('northwind policy view grants and denies correctly', function () {
    $curator = User::factory()->create();
    $curator->assignRole(Role::findOrCreate('northwind_curator', 'web'));

    $unauthorized = User::factory()->create();

    $model = new Northwind;

    expect((new NorthwindPolicy)->view($curator, $model))->toBeTrue();
    expect((new NorthwindPolicy)->view($unauthorized, $model))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Product Policies — PagilaPolicy
// ---------------------------------------------------------------------------

test('pagila policy grants viewAny to pagila curator', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('pagila_curator', 'web'));

    expect((new PagilaPolicy)->viewAny($user))->toBeTrue();
});

test('pagila policy denies viewAny to unauthorized user', function () {
    $user = User::factory()->create();

    expect((new PagilaPolicy)->viewAny($user))->toBeFalse();
});

test('pagila policy view grants and denies correctly', function () {
    $curator = User::factory()->create();
    $curator->assignRole(Role::findOrCreate('pagila_curator', 'web'));

    $unauthorized = User::factory()->create();

    $model = new stdClass;

    expect((new PagilaPolicy)->view($curator, $model))->toBeTrue();
    expect((new PagilaPolicy)->view($unauthorized, $model))->toBeFalse();
});

// ---------------------------------------------------------------------------
// TeamPolicy
// ---------------------------------------------------------------------------

beforeEach(function () {
    $this->team = Team::factory()->create();

    $this->owner = User::factory()->create();
    $this->admin = User::factory()->create();
    $this->member = User::factory()->create();
    $this->outsider = User::factory()->create();

    $this->team->members()->attach($this->owner, ['role' => TeamRole::Owner->value]);
    $this->team->members()->attach($this->admin, ['role' => TeamRole::Admin->value]);
    $this->team->members()->attach($this->member, ['role' => TeamRole::Member->value]);
});

test('team policy viewAny is always true', function () {
    $policy = new TeamPolicy;

    expect($policy->viewAny($this->outsider))->toBeTrue();
    expect($policy->viewAny($this->member))->toBeTrue();
});

test('team policy create is always true', function () {
    expect((new TeamPolicy)->create($this->outsider))->toBeTrue();
});

test('team policy view allows members and denies outsiders', function () {
    $policy = new TeamPolicy;

    expect($policy->view($this->owner, $this->team))->toBeTrue();
    expect($policy->view($this->member, $this->team))->toBeTrue();
    expect($policy->view($this->outsider, $this->team))->toBeFalse();
});

test('team policy update respects role permissions', function () {
    $policy = new TeamPolicy;

    expect($policy->update($this->owner, $this->team))->toBeTrue();
    expect($policy->update($this->admin, $this->team))->toBeTrue();
    expect($policy->update($this->member, $this->team))->toBeFalse();
    expect($policy->update($this->outsider, $this->team))->toBeFalse();
});

test('team policy add member respects role permissions', function () {
    $policy = new TeamPolicy;

    expect($policy->addMember($this->owner, $this->team))->toBeTrue();
    expect($policy->addMember($this->admin, $this->team))->toBeFalse();
    expect($policy->addMember($this->member, $this->team))->toBeFalse();
});

test('team policy update and remove member are owner-only', function () {
    $policy = new TeamPolicy;

    expect($policy->updateMember($this->owner, $this->team))->toBeTrue();
    expect($policy->updateMember($this->admin, $this->team))->toBeFalse();
    expect($policy->removeMember($this->owner, $this->team))->toBeTrue();
    expect($policy->removeMember($this->admin, $this->team))->toBeFalse();
});

test('team policy invite and cancel invitation respect role permissions', function () {
    $policy = new TeamPolicy;

    expect($policy->inviteMember($this->owner, $this->team))->toBeTrue();
    expect($policy->inviteMember($this->admin, $this->team))->toBeTrue();
    expect($policy->inviteMember($this->member, $this->team))->toBeFalse();
    expect($policy->cancelInvitation($this->owner, $this->team))->toBeTrue();
    expect($policy->cancelInvitation($this->admin, $this->team))->toBeTrue();
    expect($policy->cancelInvitation($this->member, $this->team))->toBeFalse();
});

test('team policy delete requires owner on non-personal team', function () {
    $policy = new TeamPolicy;

    expect($policy->delete($this->owner, $this->team))->toBeTrue();
    expect($policy->delete($this->admin, $this->team))->toBeFalse();
    expect($policy->delete($this->member, $this->team))->toBeFalse();
});

test('team policy delete is denied for personal teams', function () {
    $personalTeam = Team::factory()->personal()->create();
    $user = User::factory()->create();
    $personalTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

    expect((new TeamPolicy)->delete($user, $personalTeam))->toBeFalse();
});

test('team policy leave allows non-owner members and denies owner', function () {
    $policy = new TeamPolicy;

    expect($policy->leave($this->member, $this->team))->toBeTrue();
    expect($policy->leave($this->admin, $this->team))->toBeTrue();
    expect($policy->leave($this->owner, $this->team))->toBeFalse();
    expect($policy->leave($this->outsider, $this->team))->toBeFalse();
});

test('team policy leave is denied for personal teams', function () {
    $personalTeam = Team::factory()->personal()->create();
    $user = User::factory()->create();
    $personalTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

    expect((new TeamPolicy)->leave($user, $personalTeam))->toBeFalse();
});
