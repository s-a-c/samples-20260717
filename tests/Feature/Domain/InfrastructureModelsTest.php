<?php

declare(strict_types=1);

use App\Models\ResetConfirmation;
use App\Models\ResetRun;
use App\Models\SourceIdentity;
use App\Models\User;
use Illuminate\Support\Str;

covers(
    ResetConfirmation::class,
    ResetRun::class,
    SourceIdentity::class,
);

test('reset run can be persisted and recovery relations resolve', function () {
    $parent = ResetRun::create([
        'product' => 'chinook',
        'kind' => 'import',
        'status' => 'succeeded',
    ]);

    $child = ResetRun::create([
        'product' => 'chinook',
        'kind' => 'recover',
        'status' => 'pending',
        'recovery_of' => $parent->id,
    ]);

    expect($parent->id)->not->toBeNull();
    expect($parent->recoveryOf)->toBeNull();
    expect($parent->recoveryChild->id)->toBe($child->id);
    expect($child->recoveryOf->id)->toBe($parent->id);
    expect($parent->evidence)->toBeNull();

    $parent->update(['evidence' => ['rows' => 100]]);

    expect($parent->fresh()->evidence)->toBe(['rows' => 100]);
});

test('reset confirmation can be persisted and operator relation resolves', function () {
    $user = User::factory()->create();

    $confirmation = ResetConfirmation::create([
        'operator_id' => $user->id,
        'product' => 'chinook',
        'source_sha256' => hash('sha256', 'abc'),
        'source_commit' => 'abc123',
        'token' => Str::uuid()->toString(),
        'expires_at' => now()->addHour(),
    ]);

    expect($confirmation->id)->not->toBeNull();
    expect($confirmation->operator->id)->toBe($user->id);
    expect($confirmation->expires_at)->not->toBeNull();
    expect($confirmation->used_at)->toBeNull();
});

test('source identity can be persisted with casted source key', function () {
    $identity = SourceIdentity::create([
        'entity' => 'chinook.albums',
        'source_key' => ['name' => 'Test Album'],
        'domain_id' => Str::uuid()->toString(),
    ]);

    expect($identity->id)->not->toBeNull();
    expect($identity->source_key)->toBe(['name' => 'Test Album']);
    expect($identity->entity)->toBe('chinook.albums');
});
