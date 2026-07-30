<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Providers\FortifyServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

covers(FortifyServiceProvider::class);

test('two-factor rate limiter is configured and keyed by login session id', function () {
    $closure = RateLimiter::limiter('two-factor');
    assert($closure instanceof Closure);

    $request = Request::create('/two-factor-challenge', 'POST');
    $request->setLaravelSession(app('session.store'));
    $request->session()->put('login.id', 'session-user-42');

    $limit = $closure($request);

    expect($limit)->toBeInstanceOf(Limit::class)
        ->and($limit->key)->toBe('session-user-42');
});

test('login rate limiter is keyed by username and ip', function () {
    $closure = RateLimiter::limiter('login');
    assert($closure instanceof Closure);

    $request = Request::create('/login', 'POST', [
        'email' => 'User@Example.com',
    ]);

    $limit = $closure($request);

    expect($limit)->toBeInstanceOf(Limit::class)
        ->and($limit->key)->toContain('user@example.com');
});

test('passkeys rate limiter uses credential id when present', function () {
    $closure = RateLimiter::limiter('passkeys');
    assert($closure instanceof Closure);

    $request = Request::create('/passkeys/login', 'POST', [
        'credential' => ['id' => 'cred-abc-123'],
    ]);

    $limit = $closure($request);

    expect($limit)->toBeInstanceOf(Limit::class)
        ->and($limit->key)->toContain('cred-abc-123');
});

test('passkeys rate limiter falls back to session id when credential id is absent', function () {
    $closure = RateLimiter::limiter('passkeys');
    assert($closure instanceof Closure);

    $request = Request::create('/passkeys/login', 'POST');
    $request->setLaravelSession(app('session.store'));

    $limit = $closure($request);

    expect($limit)->toBeInstanceOf(Limit::class)
        ->and($limit->key)->toContain($request->session()->getId());
});

test('login view renders without an invitation query param', function () {
    $this->get(route('login'))->assertOk();
});

test('login view renders with an invalid invitation code', function () {
    $this->get(route('login', ['invitation' => 'nonexistent-code']))
        ->assertOk();
});

test('register view renders with a valid pending invitation', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Invited Team Co']);
    $team->members()->attach($owner, ['role' => App\Enums\TeamRole::Owner->value]);

    TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'newuser@example.com',
        'invited_by' => $owner->id,
        'expires_at' => now()->addDays(7),
    ]);

    $invitation = TeamInvitation::where('email', 'newuser@example.com')->first();
    assert($invitation !== null);

    $this->get(route('register', ['invitation' => $invitation->code]))
        ->assertOk()
        ->assertSee('Invited Team Co');
});
