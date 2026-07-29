<?php

declare(strict_types=1);

use App\Contracts\HasProductDomain;
use App\Enums\SamplesProduct;
use App\Exceptions\ProductResetWindowOpen;
use App\Models\ResetRun;
use App\Services\ProductReset\RecoveryService;
use App\Services\ProductReset\ResetEvidence;
use App\Services\ProductReset\ResetWindow;
use App\Traits\BelongsToProductDomain;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

covers(ResetWindow::class, RecoveryService::class);

uses(RefreshDatabase::class);

test('reset window blocks writes when reset run is active', function () {
    ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'chinook',
        'kind' => 'reset',
        'status' => 'running',
    ]);

    $window = new ResetWindow;
    expect($window->isOpen(SamplesProduct::Chinook))->toBeTrue();

    $this->expectException(ProductResetWindowOpen::class);
    $window->assertWritable(SamplesProduct::Chinook);
});

test('reset window is open for pending running and recovering statuses', function (string $status) {
    ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'chinook',
        'kind' => 'reset',
        'status' => $status,
    ]);

    $window = new ResetWindow;
    expect($window->isOpen(SamplesProduct::Chinook))->toBeTrue();
})->with(['pending', 'running', 'recovering']);

test('reset window is closed when reset run is succeeded or failed', function (string $status) {
    ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'chinook',
        'kind' => 'reset',
        'status' => $status,
    ]);

    $window = new ResetWindow;
    expect($window->isOpen(SamplesProduct::Chinook))->toBeFalse();

    $window->assertWritable(SamplesProduct::Chinook);
})->with(['succeeded', 'failed']);

test('reset evidence vo can be created with schema version 1 and serialized unserialized', function () {
    $evidence = ResetEvidence::create([
        'metadata' => ['operator' => 'admin'],
        'execution_summary' => ['duration_ms' => 1200],
    ]);

    expect($evidence->getSchemaVersion())->toBe(1);
    expect(ResetEvidence::SCHEMA_VERSION)->toBe(1);
    expect(count($evidence->getSections()))->toBe(9);

    $serialized = $evidence->toArray();
    expect($serialized['schema_version'])->toBe(1);
    expect($serialized['sections'])->toHaveKey('metadata');
    expect($serialized['sections'])->toHaveKey('execution_summary');

    $restored = ResetEvidence::fromArray($serialized);
    expect($restored->getSchemaVersion())->toBe(1);
    expect($restored->getSection('metadata'))->toBe(['operator' => 'admin']);
    expect($restored->getSection('execution_summary'))->toBe(['duration_ms' => 1200]);

    $json = json_encode($evidence);
    expect($json)->toBeJson();
    $decoded = json_decode($json, true);
    expect($decoded['schema_version'])->toBe(1);
});

test('recovery service creates recovery run linked to failed run', function () {
    $failedRun = ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'pagila',
        'kind' => 'reset',
        'status' => 'failed',
    ]);

    $recoveryService = new RecoveryService;
    expect($recoveryService->canRecover($failedRun))->toBeTrue();

    $recoveryRun = $recoveryService->createRecoveryRun($failedRun);

    expect($recoveryRun->kind)->toBe('recover');
    expect($recoveryRun->status)->toBe('running');
    expect($recoveryRun->recovery_of)->toBe($failedRun->id);
    expect($failedRun->fresh()->status)->toBe('recovering');

    expect($recoveryRun->recoveryOf->id)->toBe($failedRun->id);
    expect($failedRun->fresh()->recoveryChild->id)->toBe($recoveryRun->id);
});

test('belongs to product domain trait prevents model mutation when reset window is open', function () {
    $testModel = new class extends Model implements HasProductDomain
    {
        use BelongsToProductDomain;

        protected $table = 'reset_runs';

        protected $guarded = [];

        public function getProductDomain(): SamplesProduct
        {
            return SamplesProduct::Northwind;
        }
    };

    ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'northwind',
        'kind' => 'reset',
        'status' => 'running',
    ]);

    expect(fn () => $testModel->save())->toThrow(ProductResetWindowOpen::class);
});

test('reset window memoizes isOpen result and clearMemo forces a fresh query', function () {
    $window = new ResetWindow;

    // No active run: window reads closed and memoizes the false result.
    expect($window->isOpen(SamplesProduct::Chinook))->toBeFalse();

    // A run becomes active after the value was cached.
    ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'chinook',
        'kind' => 'reset',
        'status' => 'running',
    ]);

    // Cached value is still served without re-querying the database.
    expect($window->isOpen(SamplesProduct::Chinook))->toBeFalse();

    // Clearing the memo forces a fresh query that now observes the open run.
    $window->clearMemo();
    expect($window->isOpen(SamplesProduct::Chinook))->toBeTrue();
});

test('recovery service refuses to create a recovery run for a non-failed run', function () {
    $runningRun = ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'chinook',
        'kind' => 'reset',
        'status' => 'running',
    ]);

    $recoveryService = new RecoveryService;

    expect($recoveryService->canRecover($runningRun))->toBeFalse();
    expect(fn () => $recoveryService->createRecoveryRun($runningRun))
        ->toThrow(InvalidArgumentException::class);
});
