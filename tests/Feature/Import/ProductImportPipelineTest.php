<?php

declare(strict_types=1);

use App\Models\ResetConfirmation;
use App\Models\ResetRun;
use App\Models\User;
use App\Services\ProductImport\ProductImportPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

covers(App\Console\Commands\ProductImportCommand::class, ProductImportPipeline::class);

uses(RefreshDatabase::class);

test('product import command with dry-run runs successfully without mutating DB state and returns exit code 0', function () {
    $initialRunCount = ResetRun::count();

    $this->artisan('product:import chinook --dry-run')
        ->assertExitCode(0);

    expect(ResetRun::count())->toBe($initialRunCount);
});

test('product status command displays active and recent reset runs', function () {
    $run = ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'chinook',
        'kind' => 'import',
        'status' => 'running',
        'current_phase' => 'staging',
    ]);

    $this->artisan('product:status chinook')
        ->expectsTable(
            ['ID', 'Product', 'Kind', 'Status', 'Phase', 'Created At'],
            [
                [
                    $run->id,
                    'chinook',
                    'import',
                    'running',
                    'staging',
                    $run->fresh()->created_at->toDateTimeString(),
                ],
            ]
        )
        ->assertExitCode(0);
});

test('product confirm command mints a confirmation token for super_admin', function () {
    Role::findOrCreate('super_admin', 'web');

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->artisan('product:confirm chinook')
        ->assertExitCode(0);

    expect(ResetConfirmation::where('product', 'chinook')->where('operator_id', $admin->id)->exists())->toBeTrue();
});

test('product abort command marks an active run as failed and aborted', function () {
    $run = ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'chinook',
        'kind' => 'import',
        'status' => 'running',
        'current_phase' => 'staging',
    ]);

    $this->artisan("product:abort {$run->id}")
        ->assertExitCode(0);

    $freshRun = $run->fresh();
    expect($freshRun->status)->toBe('failed');
    expect($freshRun->current_phase)->toBe('aborted');
});

test('product recover command initiates a recovery child run for a failed run', function () {
    $failedRun = ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'chinook',
        'kind' => 'import',
        'status' => 'failed',
        'current_phase' => 'failed',
    ]);

    $this->artisan("product:recover {$failedRun->id}")
        ->assertExitCode(0);

    $recoveryRun = ResetRun::where('recovery_of', $failedRun->id)->first();
    expect($recoveryRun)->not->toBeNull();
    expect($recoveryRun->kind)->toBe('recover');
    expect($recoveryRun->status)->toBe('running');
    expect($failedRun->fresh()->status)->toBe('recovering');
});

test('import pipeline rejects unknown product name gracefully without creating a run', function () {
    $initialRunCount = ResetRun::count();

    $pipeline = app(ProductImportPipeline::class);

    $result = $pipeline->run('unknown_product');

    expect($result)->toBe(['success' => false, 'error' => 'Unknown product: unknown_product']);
    expect(ResetRun::count())->toBe($initialRunCount);
});

test('import pipeline dry-run short-circuits before creating a reset run', function () {
    $initialRunCount = ResetRun::count();

    $pipeline = app(ProductImportPipeline::class);

    $result = $pipeline->run('chinook', dryRun: true);

    expect($result)->toBe(['success' => true]);
    expect(ResetRun::count())->toBe($initialRunCount);
});
