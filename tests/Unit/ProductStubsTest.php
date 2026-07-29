<?php

declare(strict_types=1);

use App\Enums\SamplesProduct;
use App\Models\Chinook\Chinook;
use App\Models\Northwind\Northwind;

covers(Chinook::class, Northwind::class);

test('chinook model returns Chinook product domain', function () {
    $model = new Chinook;

    expect($model->getProductDomain())->toBe(SamplesProduct::Chinook);
});

test('northwind model returns Northwind product domain', function () {
    $model = new Northwind;

    expect($model->getProductDomain())->toBe(SamplesProduct::Northwind);
});

test('chinook and northwind models use HasUuids and BelongsToProductDomain traits', function () {
    expect(in_array('Illuminate\Database\Eloquent\Concerns\HasUuids', class_uses_recursive(Chinook::class), true))->toBeTrue()
        ->and(in_array('App\Traits\BelongsToProductDomain', class_uses_recursive(Chinook::class), true))->toBeTrue()
        ->and(in_array('Illuminate\Database\Eloquent\Concerns\HasUuids', class_uses_recursive(Northwind::class), true))->toBeTrue()
        ->and(in_array('App\Traits\BelongsToProductDomain', class_uses_recursive(Northwind::class), true))->toBeTrue();
});
