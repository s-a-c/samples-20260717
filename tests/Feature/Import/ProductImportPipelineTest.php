<?php

declare(strict_types=1);

use App\Models\ResetConfirmation;
use App\Models\ResetRun;
use App\Models\User;
use App\Services\ProductImport\ChinookImporter;
use App\Services\ProductImport\NorthwindImporter;
use App\Services\ProductImport\PagilaImporter;
use App\Services\ProductImport\ProductImportPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Spatie\Permission\Models\Role;

covers(
    App\Console\Commands\ProductImportCommand::class,
    App\Console\Commands\ProductAbort::class,
    App\Console\Commands\ProductConfirm::class,
    App\Console\Commands\ProductRecover::class,
    App\Console\Commands\ProductStatusCommand::class,
    ProductImportPipeline::class,
);

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

test('import pipeline full run creates a reset run marks it succeeded and returns run_id', function () {
    $pipeline = app(ProductImportPipeline::class);

    $result = $pipeline->run('chinook', dryRun: false);

    expect($result['success'])->toBeTrue()
        ->and($result)->toHaveKey('run_id');

    $runId = $result['run_id'] ?? '';
    expect($runId)->not->toBeEmpty();

    $run = ResetRun::find($runId);
    expect($run)->not->toBeNull()
        ->and($run->status)->toBe('succeeded')
        ->and($run->current_phase)->toBe('complete')
        ->and($run->product)->toBe('chinook')
        ->and($run->kind)->toBe('import');
});

test('import pipeline full run for northwind succeeds', function () {
    $pipeline = app(ProductImportPipeline::class);

    $result = $pipeline->run('northwind');

    expect($result['success'])->toBeTrue()
        ->and($result)->toHaveKey('run_id');
});

test('import pipeline full run for pagila succeeds', function () {
    $pipeline = app(ProductImportPipeline::class);

    $result = $pipeline->run('pagila');

    expect($result['success'])->toBeTrue()
        ->and($result)->toHaveKey('run_id');
});

test('product abort command fails for non-existent run id', function () {
    $missingId = (string) Str::uuid7();

    $this->artisan('product:abort', ['run_id' => $missingId])
        ->assertFailed()
        ->expectsOutput("Reset run '{$missingId}' not found.");
});

test('product abort command fails for a run that is not active', function (string $status) {
    $run = ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'chinook',
        'kind' => 'import',
        'status' => $status,
    ]);

    $this->artisan("product:abort {$run->id}")
        ->assertFailed()
        ->expectsOutput("Reset run '{$run->id}' is not active (current status: {$status}).");

    expect($run->fresh()->status)->toBe($status);
})->with(['succeeded', 'failed']);

test('product status command shows no runs message when table is empty', function () {
    $this->artisan('product:status')
        ->assertSuccessful()
        ->expectsOutput('No reset runs found.');
});

test('product status command lists all runs when no product filter given', function () {
    $runA = ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'chinook',
        'kind' => 'import',
        'status' => 'succeeded',
        'current_phase' => 'complete',
    ]);

    $runB = ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'pagila',
        'kind' => 'reset',
        'status' => 'failed',
    ]);

    $this->artisan('product:status')->assertSuccessful();

    expect(ResetRun::count())->toBeGreaterThanOrEqual(2);
});

test('product recover command fails for non-existent run', function () {
    $missingId = (string) Str::uuid7();

    $this->artisan('product:recover', ['run_id' => $missingId])
        ->assertFailed()
        ->expectsOutput("Reset run '{$missingId}' not found.");
});

test('product recover command fails for a run that is not failed', function () {
    $run = ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'chinook',
        'kind' => 'import',
        'status' => 'running',
    ]);

    $this->artisan("product:recover {$run->id}")
        ->assertFailed()
        ->expectsOutput("Reset run '{$run->id}' cannot be recovered (current status: running).");
});

test('product import command rejects an invalid confirm token', function () {
    $missingToken = (string) Str::uuid7();

    $this->artisan("product:import chinook --dry-run --confirm-token={$missingToken}")
        ->assertFailed()
        ->expectsOutput('Invalid or expired confirmation token.');
});

test('product import command accepts a valid confirm token and proceeds in dry-run', function () {
    $operator = User::factory()->create();
    $confirmationService = app(App\Services\ProductReset\ResetConfirmationService::class);
    $token = $confirmationService->mint($operator, 'chinook', 'sha', 'commit');

    $this->artisan("product:import chinook --dry-run --confirm-token={$token}")
        ->assertSuccessful();
});

test('product confirm command creates a super admin when none exists', function () {
    expect(User::count())->toBe(0);

    $this->artisan('product:confirm northwind')
        ->assertSuccessful();

    $admin = User::where('email', 'superadmin@example.com')->first();
    expect($admin)->not->toBeNull()
        ->and($admin->hasRole('super_admin'))->toBeTrue();
});

test('import pipeline marks the run failed when the importer returns an error', function () {
    $chinook = Mockery::mock(app(ChinookImporter::class));
    $chinook->shouldReceive('import')
        ->andReturn(['success' => false, 'error' => 'import exploded']);

    $pipeline = new ProductImportPipeline(
        $chinook,
        app(NorthwindImporter::class),
        app(PagilaImporter::class),
    );

    $result = $pipeline->run('chinook');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toBe('import exploded');

    $run = ResetRun::where('product', 'chinook')->first();
    assert($run !== null);
    expect($run->status)->toBe('failed')
        ->and($run->current_phase)->toBe('failed')
        ->and($run->evidence['error'])->toBe('import exploded');
});

test('product import command reports failure for an unknown product', function () {
    $this->artisan('product:import', ['product' => 'invalid_product'])
        ->assertFailed()
        ->expectsOutput('Import failed: Unknown product: invalid_product');
});
