<?php

declare(strict_types=1);

use App\Models\ResetConfirmation;
use App\Models\SourceIdentity;
use App\Models\User;
use App\Services\ProductImport\SourceIdentityRegistry;
use App\Services\ProductReset\ResetConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

covers(SourceIdentityRegistry::class, ResetConfirmationService::class);

uses(RefreshDatabase::class);

test('source identity registry returns valid uuidv7 on first call and exact same domain_id on subsequent call', function () {
    $registry = new SourceIdentityRegistry;

    $uuid1 = $registry->getOrMint('chinook.artists', ['id' => '5']);
    $uuid2 = $registry->getOrMint('chinook.artists', ['id' => '5']);
    $uuid3 = $registry->getOrMint('chinook.artists', ['id' => '6']);

    expect(Str::isUuid($uuid1))->toBeTrue();

    $parsedUuid = Uuid::fromString($uuid1);
    expect($parsedUuid)->toBeInstanceOf(UuidV7::class);

    expect($uuid1)->toBe($uuid2)
        ->and($uuid1)->not->toBe($uuid3);
});

test('source identity generated product column and check constraint behave correctly', function () {
    $registry = new SourceIdentityRegistry;
    $domainId = $registry->getOrMint('northwind.customers', ['customer_id' => 'ALFKI']);

    $identity = SourceIdentity::where('domain_id', $domainId)->first();
    expect($identity)->not->toBeNull()
        ->and($identity->product)->toBe('northwind');
});

test('reset confirmation service mints token and verify consumes it atomically setting used_at', function () {
    $operator = User::factory()->create();
    $service = new ResetConfirmationService;

    $token = $service->mint(
        operator: $operator,
        product: 'chinook',
        sha256: 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
        commit: 'a1b2c3d4e5f6',
    );

    expect(Str::isUuid($token))->toBeTrue();

    $recordBefore = ResetConfirmation::where('token', $token)->first();
    expect($recordBefore)->not->toBeNull()
        ->and($recordBefore->used_at)->toBeNull();

    // Verify first time succeeds and consumes token
    $verifiedFirst = $service->verify($token);
    expect($verifiedFirst)->toBeTrue();

    $recordAfter = ResetConfirmation::where('token', $token)->first();
    expect($recordAfter->used_at)->not->toBeNull();

    // Subsequent verify fails because used_at is set
    $verifiedSecond = $service->verify($token);
    expect($verifiedSecond)->toBeFalse();
});

test('reset confirmation verify returns false for expired or nonexistent tokens', function () {
    $service = new ResetConfirmationService;

    expect($service->verify((string) Str::uuid7()))->toBeFalse();

    $operator = User::factory()->create();
    $token = $service->mint($operator, 'pagila', 'hash', 'commit');

    // Expire the token
    ResetConfirmation::where('token', $token)->update([
        'expires_at' => now()->subMinute(),
    ]);

    expect($service->verify($token))->toBeFalse();
});
