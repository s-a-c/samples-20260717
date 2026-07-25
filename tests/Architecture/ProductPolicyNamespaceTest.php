<?php

use Symfony\Component\Finder\Finder;

test('product policy directories use product namespaces', function (): void {
    $paths = [];

    foreach (['Chinook', 'Northwind', 'Pagila'] as $product) {
        $directory = app_path("Policies/{$product}");

        if (! is_dir($directory)) {
            continue;
        }

        foreach (Finder::create()->files()->name('*.php')->in($directory) as $file) {
            $paths[] = $file->getRealPath();
        }
    }

    expect($paths)->toBeArray()->each->toBeString();
});
