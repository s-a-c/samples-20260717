---
title: "ADR 0024: Bun as the Node Package Manager"
description: "Replace pnpm with bun for install, lockfile, scripts, and CI across the frontend toolchain"
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: adr
tags: [adr, architecture, toolchain, bun, pnpm, vite-plus, ci]
created: 2026-09-15
updated: 2026-09-15
---
# ADR 0024: Bun as the Node Package Manager

**Status:** Accepted
**Date:** 2026-09-15

**Context:** Frontend dependencies were managed by pnpm 12 (`pnpm-lock.yaml`, `pnpm-workspace.yaml`, `packageManager: pnpm@12.3.4`) with Vite+ (`vp`) owning build, dev, and tooling. The workspace file carried four pnpm-only concerns: the Vite+/Vite `catalog:` indirection, the `shell-quote` 1.9.0 security override (CVE-2026-13311 forced through concurrently's transitive pin), a `minimumReleaseAgeExclude` list with no corresponding global `minimumReleaseAge` (dead configuration — no release-age gate existed to exclude from), and `peerDependencyRules` tolerating the vite-plus-core/vite peer aliasing. CI (tests, mutation, tia-baseline) installed pnpm 11.24.0 via `pnpm/action-setup` — a version that had already drifted from the manifest's 12.3.4. Bun 1.4.2 is installed locally, Vite+ supports bun as a first-class package manager (shim selection reads the `packageManager` field), Dependabot's npm ecosystem supports `bun.lock` (GA since February 2025), and Bun resolves `overrides` and concrete dependency specs from `package.json` directly.

**Decision:**

1. **Bun is the single package manager.** `package.json` pins `packageManager: bun@1.4.2`; Vite+ resolves `vp install` to bun from this field (its selection order: explicit override → `VP_PACKAGE_MANAGER` → `packageManager` → `devEngines` → lockfile). `pnpm-lock.yaml` and `pnpm-workspace.yaml` are deleted; `bun.lock` is the committed lockfile.
2. **Catalog indirection replaced with concrete versions.** Bun projects under Vite+ declare the toolchain directly: `vite: npm:@voidzero-dev/vite-plus-core@0.3.1` and `vite-plus: 0.3.1`, exactly the versions the former pnpm catalog resolved. `vp up` rewrites these concrete specs on upgrade, so the upgrade flow keeps working.
3. **The shell-quote override survives in `package.json`.** `"overrides": {"shell-quote": "1.9.0"}` — Bun reads root-manifest overrides, applies them to transitive and peer ranges, and the gate suite asserts 1.9.0 in `bun.lock`.
4. **Dead workspace settings dropped, not migrated.** `minimumReleaseAgeExclude` excluded packages from a release-age gate that was never enabled in this repository (no global `minimumReleaseAge` in project or global pnpm config); `peerDependencyRules` has no Bun equivalent and Bun resolves peer ranges permissively by default. Neither is carried into `bunfig.toml`; no `bunfig.toml` exists. The repository `.npmrc` (`ignore-scripts=true`, `audit=true`) is retained — it is harmless under Bun, whose installer already blocks unapproved lifecycle scripts.
5. **Composer scripts and CI speak bun.** `composer setup` runs `bun install --frozen-lockfile` + `bun run build`; `composer update:requirements` uses `bunx sort-package-json`, `bunx npm-check-updates -u`, `bun update` (the `pnpm pkg fix` step is dropped — no Bun equivalent). All three frontend CI workflows use `oven-sh/setup-bun@v2` with `bun-version-file: package.json` so CI, local, and the manifest pin stay one version; the `cache: pnpm` option on `actions/setup-node` is removed.

**Consequences:**

- **Positive:** One version pin (`packageManager`) drives local, Vite+, and CI installs; the CI drift between pnpm 11.24.0 and 12.3.4 is gone.
- **Positive:** Install and build are faster; the lockfile is text (`bun.lock`), diffable like `pnpm-lock.yaml`.
- **Positive:** Dependabot's existing `npm` ecosystem entry keeps working — it accepts `bun.lock` without config change.
- **Tradeoff:** No release-age gate exists for npm dependencies (none existed before either — parity, not regression).
- **Tradeoff:** Lifecycle scripts remain blocked by default; if a future dependency needs a build script, it must be added to `trustedDependencies` in `package.json` (Bun's equivalent of pnpm's `allowBuilds`).
- **Neutral:** Historical mentions of pnpm in `docs/03-research/`, `docs/superpowers/plans/`, and `.beads/` records are untouched — they document past state.

**Related:**

- [ADR 0023: Unlogged Cache Tables + Local PgBouncer](100344-unlogged-cache-pgbouncer.md) — precedent for decision-record discipline on tooling changes
- Vite+ package-manager selection: `node_modules/vite-plus/docs/guide/env.md`
- Bun override/catalog semantics: `https://bun.com/docs/pm/overrides`, `https://bun.com/docs/install/catalogs`
- Bead: `samples-20260717-b9k` (execution record)
