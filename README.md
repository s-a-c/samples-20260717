# samples-20260717

Laravel 13 reference application combining Chinook, Northwind, and Sakila sample products as distinct sample products.

## Table of Contents

- [Overview](#overview)
- [Product Structure](#product-structure)
- [Authentication](#authentication)
- [Authorization](#authorization)
- [Operator Provisioning](#operator-provisioning)
- [Testing](#testing)
- [Verification Scripts](#verification-scripts)

## Overview

This application demonstrates best practices for multi-product Laravel applications using Filament 5, Spatie Permission, and Spatie Activitylog. Each product (Chinook, Northwind, Sakila) operates as an independent domain with its own authentication, authorization, and data structure.

## Product Structure

Each product follows a consistent architecture:

```
app/
├── Models/
│   ├── User.php
│   ├── Team.php
│   ├── Membership.php
│   ├── Chinook/
│   │   └── Chinook.php
│   ├── Northwind/
│   │   └── Northwind.php
│   └── Sakila/
│       └── Sakila.php
├── Policies/
│   ├── ChinookPolicy.php
│   ├── NorthwindPolicy.php
│   └── SakilaPolicy.php
├── Console/
│   └── Commands/
│       └── OperatorCreate.php
└── Actions/
    └── Operators/
        └── ProvisionOperator.php
```

## Authentication

Authentication is handled via Laravel Fortify with Filament 5 panel integration:

- **Admin Panel**: `/admin` - Full system administration
- **Chinook Panel**: `/chinook` - Chinook product management
- **Northwind Panel**: `/northwind` - Northwind product management
- **Sakila Panel**: `/sakila` - Sakila product management

### Panel Configuration

Each panel is configured in `app/Providers/Filament/` with:

- Dedicated authentication guard (`web`)
- Product-specific roles and permissions
- Redirect to Fortify login for unauthenticated users

## Authorization

### Role-Based Access Control

| Role | Admin | Chinook | Northwind | Sakila |
|------|-------|---------|-----------|--------|
| super_admin | ✓ | ✓ | ✓ | ✓ |
| chinook_curator | ✓ | ✓ | ✗ | ✗ |
| northwind_curator | ✓ | ✗ | ✓ | ✗ |
| sakila_curator | ✓ | ✗ | ✗ | ✓ |

### Policy Namespace Rules

Policies use product-specific namespaces and are enforced via middleware:

```php
Gate::policy(Chinook::class, ChinookPolicy::class);
Gate::policy(Northwind::class, NorthwindPolicy::class);
Gate::policy(Sakila::class, SakilaPolicy::class);
```

## Operator Provisioning

### Environment Variables

| Variable | Required | Description |
|----------|----------|-------------|
| `OPERATOR_EMAIL` | Yes | System operator email address |
| `OPERATOR_PASSWORD` | Yes | System operator password |
| `OPERATOR_NAME` | No | System operator display name (default: "System Operator") |

### Command Usage

```bash
php artisan operator:create
```

This command:
1. Creates or updates the system operator account
2. Assigns the `super_admin` role
3. Creates a personal team
4. Logs the provisioning activity

### Idempotency

The command is idempotent - running it multiple times will:
- Update the operator's name if changed
- Preserve existing credentials
- Log each invocation as an audit event

## Testing

### Test Structure

```
tests/
├── Feature/
│   ├── Filament/
│   │   └── PanelAuthenticationTest.php
│   ├── Auth/
│   │   └── AuthorizationAcceptanceMatrixTest.php
│   └── Console/
│       └── OperatorCreateTest.php
├── Architecture/
│   └── ProductPolicyNamespaceTest.php
└── Unit/
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Console/OperatorCreateTest.php

# Run with coverage
php artisan test --coverage
```

### Test Environment

The `.env.testing` file configures test-specific values:

```env
APP_KEY=base64:your-key-here
OPERATOR_EMAIL=operator@example.com
OPERATOR_PASSWORD=operator-password
APP_ENV=testing
```

## Verification Scripts

The Linux CI runner is the authoritative static-analysis environment. Herd's
macOS PHP runtime needs the following local workaround when verifying PHPStan.

### PHPStan with Herd Xdebug

PHPStan 2.2.5 can exit with status 1 and no diagnostics when Xdebug is loaded.
Herd's `99-xdebug.ini` loads Xdebug by default. Disable Xdebug for the command:

```bash
XDEBUG_MODE=off vendor/bin/phpstan analyse --memory-limit=512M
```

These commands disable Xdebug modes for the command. The equivalent PHP-level
switch is:

```bash
php -dxdebug.mode=off vendor/bin/phpstan analyse --memory-limit=512M
```

Run the normal project check after the standalone diagnosis:

```bash
XDEBUG_MODE=off composer types:check
```

The command may report existing Larastan findings. Those findings remain
actionable code issues; disabling Xdebug only prevents Herd's silent-exit
failure.

### PHPStan parallelism

PHPStan 2.2.5 no longer accepts `--threads`. It parallelizes automatically
when the runtime supports `pcntl`, so do not add `--threads=1` to local or CI
commands.

### Herd extension warning

Herd may print `Module "herd" already loaded` because `herd-ext` is compiled
into Herd's PHP binary and loaded again by its PHP configuration. This warning
is cosmetic and does not change PHPStan's result.

### Environment boundary

These workarounds apply only to the local macOS Herd runner. Linux CI does not
use Herd's Xdebug configuration or duplicate `herd-ext` loading.

## Development Setup

1. Install dependencies:
   ```bash
   composer install
   ```

2. Configure environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Run migrations and seeders:
   ```bash
   php artisan migrate --seed
   ```

4. Create system operator:
   ```bash
   php artisan operator:create
   ```

5. Start development server:
   ```bash
   php artisan serve
   ```

## Contributing

This project follows Laravel Boost guidelines and PHP 8.5 standards. All new
code should include corresponding tests achieving 80%+ coverage.Last updated: 2026-07-24 21:36:53 UTC
