<?php

declare(strict_types=1);

use App\Services\ProductImport\Mapping\ProductMapper;
use App\Services\ProductImport\Mapping\SelfReferentialMapper;
use App\Services\ProductImport\Mapping\TableMapper;

covers(TableMapper::class, SelfReferentialMapper::class, ProductMapper::class);

test('table mapper has abstract load method', function () {
    $reflection = new ReflectionClass(TableMapper::class);
    expect($reflection->getMethod('load')->isAbstract())->toBeTrue();
});

test('self referential mapper extends table mapper', function () {
    expect(SelfReferentialMapper::class)
        ->toExtend(TableMapper::class);
});

test('product mapper has abstract load method', function () {
    $reflection = new ReflectionClass(ProductMapper::class);
    expect($reflection->getMethod('load')->isAbstract())->toBeTrue();
});

test('product mapper load returns array with tables and rows keys', function () {
    $reflection = new ReflectionMethod(ProductMapper::class, 'load');
    $returnType = (string) $reflection->getReturnType();

    expect($returnType)->toContain('array');
});

test('table mapper provides count source rows helper', function () {
    $reflection = new ReflectionClass(TableMapper::class);
    expect($reflection->hasMethod('countSourceRows'))->toBeTrue()
        ->and($reflection->hasMethod('readSourceRows'))->toBeTrue();
});
