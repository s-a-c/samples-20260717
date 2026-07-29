<?php

declare(strict_types=1);

use App\Data\TeamPermissions;
use App\Enums\SamplesProduct;
use App\Exceptions\ProductResetWindowOpen;
use App\Jobs\EmbeddingJob;
use App\Models\Chinook\Artist as ChinookArtist;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use App\Observers\Tier1SourceObserver;
use App\Rules\TeamName;
use App\Rules\UniqueTeamInvitation;
use App\Services\Portfolio\PortfolioSnapshotStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Validator;

covers(
    ProductResetWindowOpen::class,
    Tier1SourceObserver::class,
    PortfolioSnapshotStats::class,
    TeamName::class,
    UniqueTeamInvitation::class,
    TeamPermissions::class,
    TeamInvitationNotification::class,
);

// ---------------------------------------------------------------------------
// ProductResetWindowOpen
// ---------------------------------------------------------------------------

test('product reset window open exception has default message and 423 code', function () {
    $exception = new ProductResetWindowOpen;

    expect($exception->getMessage())->toBe('Product reset window is currently open.')
        ->and($exception->getCode())->toBe(423);
});

test('product reset window open accepts custom message', function () {
    $exception = new ProductResetWindowOpen('Custom message');

    expect($exception->getMessage())->toBe('Custom message');
});

test('product reset window open renders json for json requests', function () {
    $exception = new ProductResetWindowOpen;

    $request = Request::create('/', 'GET');
    $request->headers->set('Accept', 'application/json');

    $response = $exception->render($request);

    expect($response->getStatusCode())->toBe(423);
});

test('product reset window open renders plain response for non-json requests', function () {
    $exception = new ProductResetWindowOpen;

    $request = Request::create('/', 'GET');

    $response = $exception->render($request);

    expect($response->getStatusCode())->toBe(423);
});

// ---------------------------------------------------------------------------
// Tier1SourceObserver
// ---------------------------------------------------------------------------

test('tier1 source observer dispatches embedding job on model save', function () {
    Bus::fake();

    $artist = ChinookArtist::create(['name' => 'Observer Test Artist']);
    $observer = new Tier1SourceObserver;
    $observer->saved($artist);

    Bus::assertDispatched(EmbeddingJob::class, function (EmbeddingJob $job) use ($artist) {
        return $job->product === SamplesProduct::Chinook->value
            && $job->entityId === $artist->getKey();
    });
});

test('tier1 source observer skips dispatch when staging flag is set', function () {
    Bus::fake();
    app()->instance('is_staging', true);

    $artist = ChinookArtist::create(['name' => 'Staging Artist']);
    $observer = new Tier1SourceObserver;
    $observer->saved($artist);

    Bus::assertNotDispatched(EmbeddingJob::class);
});

test('tier1 source observer defaults to chinook product for non-domain models', function () {
    Bus::fake();

    $user = User::factory()->create();
    $observer = new Tier1SourceObserver;
    $observer->saved($user);

    Bus::assertDispatched(EmbeddingJob::class, function (EmbeddingJob $job) use ($user) {
        return $job->product === SamplesProduct::Chinook->value
            && $job->entityId === $user->getKey();
    });
});

// ---------------------------------------------------------------------------
// PortfolioSnapshotStats
// ---------------------------------------------------------------------------

test('portfolio snapshot stats returns all three products with stat arrays', function () {
    $stats = PortfolioSnapshotStats::byProduct();

    expect($stats)->toHaveKeys(['chinook', 'northwind', 'pagila'])
        ->and($stats['chinook'])->not->toBeEmpty()
        ->and($stats['northwind'])->not->toBeEmpty()
        ->and($stats['pagila'])->not->toBeEmpty();

    foreach ($stats as $items) {
        foreach ($items as $item) {
            expect($item)->toHaveKeys(['label', 'value']);
        }
    }
});

// ---------------------------------------------------------------------------
// TeamName rule
// ---------------------------------------------------------------------------

test('team name rule rejects reserved names', function ($reserved) {
    $validator = Validator::make(['name' => $reserved], ['name' => new TeamName]);

    expect($validator->fails())->toBeTrue();
})->with(['admin', 'api', 'login', 'settings', 'dashboard', '404']);

test('team name rule accepts non-reserved names', function ($valid) {
    $validator = Validator::make(['name' => $valid], ['name' => new TeamName]);

    expect($validator->fails())->toBeFalse();
})->with(['my-awesome-team', 'engineering-squad', 'design-guild']);

test('team name rule trims and lowercases before checking', function () {
    $validator = Validator::make(['name' => '  ADMIN  '], ['name' => new TeamName]);

    expect($validator->fails())->toBeTrue();
});

// ---------------------------------------------------------------------------
// UniqueTeamInvitation rule
// ---------------------------------------------------------------------------

test('unique team invitation rule rejects existing member email', function () {
    $team = Team::factory()->create();
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => 'member']);

    $validator = Validator::make(
        ['email' => $member->email],
        ['email' => new UniqueTeamInvitation($team)],
    );

    expect($validator->fails())->toBeTrue();
});

test('unique team invitation rule rejects pending invitation email', function () {
    $team = Team::factory()->create();
    TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'pending@example.com',
    ]);

    $validator = Validator::make(
        ['email' => 'pending@example.com'],
        ['email' => new UniqueTeamInvitation($team)],
    );

    expect($validator->fails())->toBeTrue();
});

test('unique team invitation rule accepts new email', function () {
    $team = Team::factory()->create();

    $validator = Validator::make(
        ['email' => 'newperson@example.com'],
        ['email' => new UniqueTeamInvitation($team)],
    );

    expect($validator->fails())->toBeFalse();
});

// ---------------------------------------------------------------------------
// TeamPermissions data object
// ---------------------------------------------------------------------------

test('team permissions data object stores boolean flags', function () {
    $permissions = new TeamPermissions(
        canUpdateTeam: true,
        canDeleteTeam: false,
        canAddMember: true,
        canUpdateMember: false,
        canRemoveMember: false,
        canCreateInvitation: true,
        canCancelInvitation: false,
    );

    expect($permissions->canUpdateTeam)->toBeTrue()
        ->and($permissions->canDeleteTeam)->toBeFalse()
        ->and($permissions->canAddMember)->toBeTrue()
        ->and($permissions->canUpdateMember)->toBeFalse()
        ->and($permissions->canRemoveMember)->toBeFalse()
        ->and($permissions->canCreateInvitation)->toBeTrue()
        ->and($permissions->canCancelInvitation)->toBeFalse();
});

// ---------------------------------------------------------------------------
// TeamInvitation notification
// ---------------------------------------------------------------------------

test('team invitation notification sends via mail channel', function () {
    $invitation = TeamInvitation::factory()->create();
    $notification = new TeamInvitationNotification($invitation);
    $user = User::factory()->create();

    expect($notification->via($user))->toBe(['mail']);
});

test('team invitation notification builds mail message', function () {
    $invitation = TeamInvitation::factory()->create();
    $notification = new TeamInvitationNotification($invitation);
    $user = User::factory()->create();

    $mail = $notification->toMail($user);

    expect($mail)->not->toBeNull();
});

test('team invitation notification array representation includes invitation data', function () {
    $invitation = TeamInvitation::factory()->create();
    $notification = new TeamInvitationNotification($invitation);
    $user = User::factory()->create();

    $array = $notification->toArray($user);

    expect($array)->toHaveKey('invitation_id', $invitation->id)
        ->and($array)->toHaveKey('team_id', $invitation->team_id)
        ->and($array)->toHaveKey('team_name')
        ->and($array)->toHaveKey('role', $invitation->role->value);
});
