# Project-Specific Agent Instructions: samples-20260717

## PHP & Herd Configuration
This project relies on Laravel Herd for its PHP environment. If `php` is not in your `PATH`, use the absolute path to the Herd binary:
- **PHP Path:** `/Users/s-a-c/Library/Application Support/Herd/bin/php`
- **Artisan:** `"/Users/s-a-c/Library/Application Support/Herd/bin/php" artisan`

To temporarily fix your session PATH:
```bash
export PATH="/Users/s-a-c/Library/Application Support/Herd/bin:$PATH"
```

## Build & Setup
- **Initial Setup:** `composer setup` (handles .env, migrations, and pnpm build).
- **Frontend:** `pnpm run build` or `pnpm run dev`.

## Testing
- **Test Runner:** Pest 4.
- **Run Tests:** `composer test` or `php artisan test`.
- **Filtering:** `php artisan test --filter=SomeTest`.

### Creating a Test
Use the Artisan command:
```bash
php artisan make:test --pest NewFeatureTest
```

### Example Test
```php
<?php

test('it works', function () {
    expect(true)->toBeTrue();
});
```

## Code Style
- **Formatting:** Laravel Pint. Run `composer lint` to fix style issues.
- **Static Analysis:** PHPStan (Larastan). Run `composer types:check`.
- **Architecture:** Follow ADRs in `docs/10-architecture/1003-adr/`.
