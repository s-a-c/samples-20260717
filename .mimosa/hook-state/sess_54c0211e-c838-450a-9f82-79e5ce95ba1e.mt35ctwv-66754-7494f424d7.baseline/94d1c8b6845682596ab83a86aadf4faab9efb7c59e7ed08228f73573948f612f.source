<?php

declare(strict_types=1);

use App\Enums\TeamArtefactType;
use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use Filament\Support\Icons\Heroicon;

covers(
    TeamArtefactType::class,
    TeamPermission::class,
    TeamRole::class,
);

// ---------------------------------------------------------------------------
// TeamPermission
// ---------------------------------------------------------------------------

test('team permission enum has all seven cases with correct values', function () {
    expect(TeamPermission::cases())->toHaveCount(7)
        ->and(TeamPermission::UpdateTeam->value)->toBe('team:update')
        ->and(TeamPermission::DeleteTeam->value)->toBe('team:delete')
        ->and(TeamPermission::AddMember->value)->toBe('member:add')
        ->and(TeamPermission::UpdateMember->value)->toBe('member:update')
        ->and(TeamPermission::RemoveMember->value)->toBe('member:remove')
        ->and(TeamPermission::CreateInvitation->value)->toBe('invitation:create')
        ->and(TeamPermission::CancelInvitation->value)->toBe('invitation:cancel');
});

// ---------------------------------------------------------------------------
// TeamRole
// ---------------------------------------------------------------------------

test('team role enum has three cases with correct values', function () {
    expect(TeamRole::cases())->toHaveCount(3)
        ->and(TeamRole::Owner->value)->toBe('owner')
        ->and(TeamRole::Admin->value)->toBe('admin')
        ->and(TeamRole::Member->value)->toBe('member');
});

test('team role label returns capitalised value', function () {
    expect(TeamRole::Owner->label())->toBe('Owner')
        ->and(TeamRole::Admin->label())->toBe('Admin')
        ->and(TeamRole::Member->label())->toBe('Member');
});

test('team role assignable excludes owner and includes admin and member', function () {
    $assignable = TeamRole::assignable();

    expect($assignable)->toHaveCount(2)
        ->and($assignable[0])->toBe(['value' => 'admin', 'label' => 'Admin'])
        ->and($assignable[1])->toBe(['value' => 'member', 'label' => 'Member']);
});

test('team role permissions follow the hierarchy', function () {
    expect(TeamRole::Owner->permissions())->toHaveCount(7)
        ->and(TeamRole::Owner->permissions())->toContain(TeamPermission::DeleteTeam)
        ->and(TeamRole::Admin->permissions())->toHaveCount(3)
        ->and(TeamRole::Admin->permissions())->toContain(TeamPermission::UpdateTeam)
        ->and(TeamRole::Admin->permissions())->toContain(TeamPermission::CreateInvitation)
        ->and(TeamRole::Admin->permissions())->toContain(TeamPermission::CancelInvitation)
        ->and(TeamRole::Member->permissions())->toBeEmpty();
});

test('team role has permission checks correctly', function () {
    expect(TeamRole::Owner->hasPermission(TeamPermission::DeleteTeam))->toBeTrue()
        ->and(TeamRole::Owner->hasPermission(TeamPermission::UpdateMember))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::UpdateTeam))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::DeleteTeam))->toBeFalse()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::RemoveMember))->toBeFalse()
        ->and(TeamRole::Member->hasPermission(TeamPermission::UpdateTeam))->toBeFalse();
});

test('team role levels reflect privilege hierarchy', function () {
    expect(TeamRole::Owner->level())->toBe(3)
        ->and(TeamRole::Admin->level())->toBe(2)
        ->and(TeamRole::Member->level())->toBe(1);
});

test('team role isAtLeast compares privilege levels', function () {
    expect(TeamRole::Owner->isAtLeast(TeamRole::Admin))->toBeTrue()
        ->and(TeamRole::Owner->isAtLeast(TeamRole::Owner))->toBeTrue()
        ->and(TeamRole::Admin->isAtLeast(TeamRole::Member))->toBeTrue()
        ->and(TeamRole::Admin->isAtLeast(TeamRole::Owner))->toBeFalse()
        ->and(TeamRole::Member->isAtLeast(TeamRole::Admin))->toBeFalse();
});

// ---------------------------------------------------------------------------
// TeamArtefactType
// ---------------------------------------------------------------------------

test('team artefact type has two cases with correct values', function () {
    expect(TeamArtefactType::cases())->toHaveCount(2)
        ->and(TeamArtefactType::SavedSearch->value)->toBe('saved_search')
        ->and(TeamArtefactType::TeamDashboard->value)->toBe('team_dashboard');
});

test('team artefact type labels are human-readable', function () {
    expect(TeamArtefactType::SavedSearch->getLabel())->toBe('Saved Search')
        ->and(TeamArtefactType::TeamDashboard->getLabel())->toBe('Team Dashboard');
});

test('team artefact type colors map to Filament palette names', function () {
    expect(TeamArtefactType::SavedSearch->getColor())->toBe('info')
        ->and(TeamArtefactType::TeamDashboard->getColor())->toBe('success');
});

test('team artefact type icons are Heroicons', function () {
    expect(TeamArtefactType::SavedSearch->getIcon())->toBe(Heroicon::OutlinedMagnifyingGlass)
        ->and(TeamArtefactType::TeamDashboard->getIcon())->toBe(Heroicon::OutlinedSquares2x2);
});
