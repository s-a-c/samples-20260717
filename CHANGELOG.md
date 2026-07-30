---
title: "Changelog"
description: "All notable changes to `samples-20260717` will be documented in this file."
type: changelog
tags: \[changelog, documentation]
updated: 2026-07-30
---

# Changelog

All notable changes to `samples-20260717` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] - 2026-07-24

### Added

- **Multi-Panel Authentication & Redirects**: Configured Fortify authentication integration across four Filament panels (`admin`, `chinook`, `northwind`, `pagila`). Unauthenticated access to panel roots strictly redirects (302) to `/login`.
- **System Operator Onboarding (`operator:create`)**: Console command and `ProvisionOperator` action to create or update the system operator account, automatically assign `super_admin` role, initialize personal team, and audit provisioning via Spatie Activitylog.
- **DatabaseSeeder Operator Initialization**: Dev-seeding of baseline operator moved out of migrations and into `DatabaseSeeder` via environment secrets (`OPERATOR_EMAIL`, `OPERATOR_PASSWORD`, `OPERATOR_NAME`).
- **Product-Scoped Policy Namespaces**: Product resource policies organized under `App\Policies\<Product>\` (`ChinookPolicy`, `NorthwindPolicy`) with architecture rule enforcement in `ProductPolicyNamespaceTest`.
- **Authorization Acceptance Matrix**: Acceptance matrix test suite (`AuthorizationAcceptanceMatrixTest`) validating global role access (`super_admin`, `chinook_curator`, `northwind_curator`, `pagila_curator`) across all four panels.

### Changed

- Refactored `User` model to incorporate Spatie `HasRoles` trait with conflict resolution for team membership relationships (`HasTeams::teams insteadof HasRoles`).
- Configured Filament Shield role resolution with Gate bypass for `super_admin` in `AppServiceProvider`.

### Fixed

- Idempotency and error handling for `operator:create` when environment credentials are unset or invalid.
- PHPStan type-checking compliance (Larastan max level) across operator commands, seeders, and product policies without `@phpstan-ignore` suppressions.
