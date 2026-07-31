<?php

declare(strict_types=1);

use App\Filament\Admin\Widgets\ProductPortfolioCard;
use App\Jobs\ProductImportJob;
use App\Models\ResetRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

covers(ProductPortfolioCard::class);

beforeEach(function () {
    Permission::findOrCreate('product::import', 'web');

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole(Role::findOrCreate('super_admin', 'web'));
});

test('refresh stats button is visible on card', function () {
    $this->actingAs($this->superAdmin);

    Livewire::test(ProductPortfolioCard::class, ['productKey' => 'chinook'])
        ->assertSee('Refresh Stats');
});

test('refresh stats dispatches refresh action and shows spinner', function () {
    $this->actingAs($this->superAdmin);

    Livewire::test(ProductPortfolioCard::class, ['productKey' => 'chinook'])
        ->call('refreshStats')
        ->assertOk();
});

test('refresh stats shows last refreshed timestamp', function () {
    $this->actingAs($this->superAdmin);

    Livewire::test(ProductPortfolioCard::class, ['productKey' => 'northwind'])
        ->call('refreshStats')
        ->assertSee('Last refreshed');
});

test('non super admin can see refresh stats button', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('chinook_curator', 'web'));

    $this->actingAs($user);

    Livewire::test(ProductPortfolioCard::class, ['productKey' => 'chinook'])
        ->assertSee('Refresh Stats');
});

test('import data button is visible to super admin', function () {
    $this->actingAs($this->superAdmin);

    Livewire::test(ProductPortfolioCard::class, ['productKey' => 'chinook'])
        ->assertSee('Import Data');
});

test('import data button is hidden from curator', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('chinook_curator', 'web'));

    $this->actingAs($user);

    Livewire::test(ProductPortfolioCard::class, ['productKey' => 'chinook'])
        ->assertDontSee('Import Data');
});

test('import action shows confirmation modal with product details', function () {
    $this->actingAs($this->superAdmin);

    $component = Livewire::test(ProductPortfolioCard::class, ['productKey' => 'chinook'])
        ->mountAction('importData');

    $component->assertActionMounted('importData');
    $component->assertSee('Chinook');

    $modalHtml = $component->getMountedActionModalHtml();
    expect($modalHtml)->toContain('Import chinook Data');
    expect($modalHtml)->toContain('Source commit');
});

test('confirming import dispatches ProductImportJob', function () {
    Queue::fake();

    $this->actingAs($this->superAdmin);

    Livewire::test(ProductPortfolioCard::class, ['productKey' => 'northwind'])
        ->callAction('importData');

    Queue::assertPushed(ProductImportJob::class, function (ProductImportJob $job) {
        return $job->product === 'northwind';
    });
});

test('confirming import shows started notification', function () {
    $this->actingAs($this->superAdmin);

    Livewire::test(ProductPortfolioCard::class, ['productKey' => 'pagila'])
        ->callAction('importData')
        ->assertNotified('Import started for pagila');
});

test('status badge shows running state when import in progress', function () {
    $this->actingAs($this->superAdmin);

    ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'chinook',
        'kind' => 'import',
        'status' => 'running',
        'current_phase' => 'staging',
    ]);

    Livewire::test(ProductPortfolioCard::class, ['productKey' => 'chinook'])
        ->assertSee('Importing');
});

test('status badge shows succeeded state after successful import', function () {
    $this->actingAs($this->superAdmin);

    ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'northwind',
        'kind' => 'import',
        'status' => 'succeeded',
        'current_phase' => 'complete',
    ]);

    Livewire::test(ProductPortfolioCard::class, ['productKey' => 'northwind'])
        ->assertSee('Succeeded');
});

test('status badge shows failed state after failed import', function () {
    $this->actingAs($this->superAdmin);

    ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'pagila',
        'kind' => 'import',
        'status' => 'failed',
        'current_phase' => 'failed',
    ]);

    Livewire::test(ProductPortfolioCard::class, ['productKey' => 'pagila'])
        ->assertSee('Failed');
});

test('stats auto-refresh when import transitions to succeeded', function () {
    $this->actingAs($this->superAdmin);

    ResetRun::create([
        'id' => (string) Str::uuid7(),
        'product' => 'chinook',
        'kind' => 'import',
        'status' => 'running',
        'current_phase' => 'staging',
    ]);

    $component = Livewire::test(ProductPortfolioCard::class, ['productKey' => 'chinook'])
        ->assertSee('Importing');

    ResetRun::where('product', 'chinook')
        ->latest('created_at')
        ->first()
        ->update(['status' => 'succeeded', 'current_phase' => 'complete']);

    $component
        ->call('refreshStatus')
        ->assertSee('Succeeded')
        ->assertNotified('Import completed for chinook');
});
