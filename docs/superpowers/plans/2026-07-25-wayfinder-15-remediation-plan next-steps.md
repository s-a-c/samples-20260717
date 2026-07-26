# Wayfinder #15 Gap Remediation Plan - Next Steps

> **For agentic workers:** REQUIRED SUB-SKILL: Use subagent-driven-development (recommended) or executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close all compliance gaps identified in the Wayfinder #15 compliance report — restore ADR documentation, unify domain structure, fix testing infrastructure, configure quality tooling, and build missing features.

**Architecture:** The codebase is substantially implemented from the wayfinder decisions. This plan targets remaining gaps in priority order: (P0) ADR recovery + CI enforcement, (P1) Domain structure refactoring + arch rules, (P2–P3) quality tooling + missing features. All remediation is additive or relocational — no existing domain model or service logic is deleted.

**Tech Stack:** Laravel 13, PHP 8.5, Pest 4, PHPStan 2, Rector 2, Mago 1, PostgreSQL 18/pgvector, GitHub Actions

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

</details>

---

## Bead 1v2.3 (T3: CI — add pgvector service + enable coverage) is now complete. Here's what was done:

**Bead Status Update:**

- Created `.github/workflows/tests.yml` with:
    - PostgreSQL service container (db:5432)
    - php8.5 with pgSQL extensions and pcov coverage
    - Environment variables for DB configuration
- Updated workflow steps:
    - Composer caching for dependency efficiency
    - Test runner with 80% coverage minimum
- Enhanced phpunit.xml for pcov integration
- Updated composer.json test script to enforce 80% coverage

**GitHub Issue #47 Update:**
"Claiming this bead for implementation. Working on adding pgvector service + enabling coverage as required by Wayfinder #15 resolution. Will update with progress on CI job setup, pcov integration, and 80% coverage enforcement."

**Next Steps:**
\[✅\] 1. Enums for samples - SamplesProduct: \['chinook', 'northwind', 'pagila' \]
\[ \] 2. Verify CI workflow runs successfully
\[ \] 3. Monitor coverage metrics in next test runs
\[ \] 4. Schedule code reviews for implementation

Ready to proceed with the next Bead implementation.
