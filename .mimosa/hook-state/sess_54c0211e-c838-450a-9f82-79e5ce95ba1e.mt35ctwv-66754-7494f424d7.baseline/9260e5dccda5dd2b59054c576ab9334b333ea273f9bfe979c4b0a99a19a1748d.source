<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

covers(App\Policies\ChinookPolicy::class);

test('product policy directories use product namespaces', function (): void {
    $paths = [];
    $projectRoot = dirname(__DIR__, 2);

    foreach (['Chinook', 'Northwind', 'Pagila'] as $product) {
        $directory = "{$projectRoot}/app/Policies/{$product}";

        if (! is_dir($directory)) {
            continue;
        }

        foreach (Finder::create()->files()->name('*.php')->in($directory) as $file) {
            $paths[] = $file->getRealPath();
        }
    }

    expect($paths)->toBeArray()->each->toBeString();
});
