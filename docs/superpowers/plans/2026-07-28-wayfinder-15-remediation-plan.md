---
title: "Wayfinder #15 Re-audit Remediation Plan"
description: "> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking."
tableOfContents:
    minHeadingLevel: 2
    maxHeadingLevel: 3
type: plan
tags: [plan, plans, wayfinder, "15"]
created: 2026-07-28
updated: 2026-08-17
---

# Wayfinder #15 Re-audit Remediation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the 7 remaining gaps from the [2026-07-28 Wayfinder #15 compliance re-audit](../specs/2026-07-28-wayfinder-15-compliance-report.md) — static-analysis discipline (G1/G7), CI gate fidelity (G2), the EmbeddingJob failure contract (G5), the dual-suite design (G3), dossier content (G4), and three foundational ADRs (G6).

**Architecture:** No structural changes. G1/G7 harden the PHPStan baseline with a citation format and a build-time guard. G2 replaces the single phpunit CI step with the full quality gate. G5 restores the `laravel/ai` failure semantics. G3 is a _decision_ (recommend ratifying Postgres-only). G4/G6 are documentation.

**Tech Stack:** Laravel 13, PHP 8.5, Pest 4, PHPStan (Larastan) `level: max`, Mago, Rector, Infection, GitHub Actions, `pgvector/pgvector:pg18`.

**Compliance report:** [`docs/superpowers/specs/2026-07-28-wayfinder-15-compliance-report.md`](../specs/2026-07-28-wayfinder-15-compliance-report.md)

## Global Constraints

- Every code change MUST keep `composer types:check` (PHPStan `level: max`) green. Per #18 there is **no baseline ratchet** — the long-term target is zero baseline entries; until then every retained entry carries a citation.
- Citation format (define once, use everywhere): a YAML comment line directly inside the entry block — `# bd:<bead-id>`, `# gh-<issue>`, or `# framework-idiom:`. Permanent framework-idiom carve-outs live in `phpstan.neon` `ignoreErrors`, **not** the baseline.
- No new Composer/NPM dependencies without approval. All tools required (PHPStan, Pest, Mago) are already installed.
- PHP style: `vendor/bin/pint --dirty --format agent` before finalising any `.php` edit.
- Tests: Pest. New test files via `php artisan make:test --pest {Name}` (no suite dir prefix). Run targeted: `php artisan test --compact --filter=<name>`.
- This plan is **documentation + config + small-scope code**. No schema migrations, no new Filament resources, no dependency changes.

---

## File Map

| Task | Creates / Modifies                                                                     | Responsibility                                            |
| ---- | -------------------------------------------------------------------------------------- | --------------------------------------------------------- |
| G7   | Create `tests/Architecture/PhpStanBaselineCitationTest.php`                            | Fail build when a baseline entry lacks a citation comment |
| G1   | Modify `phpstan-baseline.neon`, `phpstan.neon`; eventually delete baseline             | Restore #18 discipline; reach zero-uncited, then zero     |
| G2   | Modify `.github/workflows/tests.yml`, `phpunit.xml`                                    | Enforce the full #17 PR-gate in CI                        |
| G5   | Modify `app/Jobs/EmbeddingJob.php`; Create `tests/Feature/EmbeddingJobFailureTest.php` | Honour #33 failure contract                               |
| G3   | Create `docs/10-architecture/1003-adr/100334-single-postgres-test-suite.md`            | Amend #17 dual-suite clause (decision record)             |
| G4   | Modify `app/Console/Commands/DossierGenerate.php`                                      | Emit stage files, not just scaffolding                    |
| G6   | Create three ADRs under `docs/10-architecture/1003-adr/`                               | Document #30, #11, #13                                    |

---

## Task G7: PHPStan citation guard (enabler for G1)

**Files:**

- Create: `tests/Architecture/PhpStanBaselineCitationTest.php`
- Reference: `phpstan.neon`, `phpstan-baseline.neon`

**Interfaces:**

- Produces: a Pest test `it_requires_every_phpstan_baseline_entry_to_cite_a_ticket` that fails (non-zero exit) when `phpstan-baseline.neon` exists and the count of citation comments is less than the count of `message:` entries.

**Rationale:** G1 regressed because nothing failed when #46's annotations were regenerated away. This guard is the backstop. It must land **first** so G1's work cannot silently unwind.

- [ ] **Step 1: Write the failing test**

```php
// tests/Architecture/PhpStanBaselineCitationTest.php
<?php

declare(strict_types=1);

it('requires every phpstan baseline entry to cite a ticket', function (): void {
    $baseline = __DIR__.'/../../phpstan-baseline.neon';

    if (! file_exists($baseline)) {
        $this->markTestSkipped('No phpstan-baseline.neon present (#18 terminal state reached).');
    }

    $contents = file_get_contents($baseline);
    assert($contents !== false);

    $entryCount = preg_match_all('/^\s+message:\s#/m', $contents) ?: 0;
    $citationCount = preg_match_all(
        '/^\s+#\s+(bd:|gh-|framework-idiom:)/m',
        $contents,
    ) ?: 0;

    expect($citationCount)
        ->toBeGreaterThanOrEqual($entryCount, sprintf(
            'phpstan-baseline.neon has %d entries but only %d carry a citation comment '
            .'(# bd:<id> | # gh-<n> | # framework-idiom:). See ADR 100328 / Wayfinder #18.',
            $entryCount,
            $citationCount,
        ));
});
```

- [ ] **Step 2: Run it to verify it fails (baseline currently uncited)**

Run: `php artisan test --compact --filter=requires_every_phpstan_baseline_entry_to_cite_a_ticket`
Expected: FAIL — "has 242 entries but only 0 carry a citation comment".

- [ ] **Step 3: Commit (test is intentionally red until G1 lands; skip CI gating of this file if needed)**

```bash
git add tests/Architecture/PhpStanBaselineCitationTest.php
git commit -m "test: guard phpstan baseline entries carry ticket citations (wayfinder #15, G7)"
```

> Note: This test will be red until Task G1 annotates the baseline. If branch protection blocks landing a red test, land G7 and G1 together in one PR (G1 immediately follows).

---

## Task G1: Restore PHPStan baseline discipline (#18)

**Files:**

- Modify: `phpstan-baseline.neon` (annotate, then shrink)
- Modify: `phpstan.neon` (remove `include` once baseline is empty)

**Interfaces:**

- Consumes: the citation format defined in Global Constraints
- Produces: a baseline where every entry is cited; terminal state = no baseline file at all

**Strategy:** #18's goal is **no baseline**. Triage (2026-07-28) found the 242 entries skew heavily to **test-code strict-rules noise**: `method.nonObject` (69) + `property.nonObject` (58) = 127, almost all under `tests/Feature/*` — Pest tests calling methods/properties on `mixed` (factory output, `$response->...`, relations). The realistic, #18-honest path is therefore: **(A)** move the dominant framework/test-idiom identifiers into `phpstan.neon` `ignoreErrors` (path-scoped to `tests/*` where possible) with `# framework-idiom:` comments and **remove them from the baseline** — this is permanent carve-out, not baseline debt; then fix or cite the genuine residual; **(B)** ratchet the residual to zero in batched PRs until the baseline file is deleted. Phase A (guard green + framework-idiom extracted) is the acceptance bar for this plan.

- [ ] **Step 1: Categorise the 242 entries**

Run a triage pass and bucket each `identifier:`:

```bash
# Tally by identifier to size the buckets
rg "identifier:" phpstan-baseline.neon | sort | uniq -c | sort -rn
# Where the nonObject noise lives (should be ~all under tests/)
rg -A3 "identifier: (method|property).nonObject" phpstan-baseline.neon | rg "path:" | sort | uniq -c | sort -rn
```

Observed buckets (2026-07-28):

1. **Framework/test-idiom (largest, → `phpstan.neon` ignoreErrors, remove from baseline):**
    - `method.nonObject` (69) + `property.nonObject` (58) — strict-rules `mixed`-receiver noise, overwhelmingly in `tests/Feature/*`. Scope the carve-out to `tests/*` paths so app-code `mixed` receivers are still caught.
    - `pest.config.redundantLocalUse` (6), `pest.expectation.redundant` (4) — Pest plumbing; fix in test code where trivial, else cite.
2. **Code-fixable (→ fix in code, Phase B; cite `# bd:<id>` in Phase A):** `return.unusedType` (5), `return.type` (4), `missingType.iterableValue` (6), `argument.type` (29), `offsetAccess.nonOffsetAccessible` (10), `cast.*` (4), `typeCoverage.*` (3), boolean-strictness (`if.condNotBoolean`, `booleanOr.*`, `booleanAnd.*`, `notEqual.notAllowed`, `empty.notAllowed`, `ternary.shortNotAllowed`).
3. **Framework deprecation (→ `phpstan.neon` ignoreErrors with `# framework-idiom:` or fix):** `method.deprecated` (25) — inspect; Filament/Laravel deprecations are framework-idiom.
4. **Genuine third-party (→ cite `# gh-<n>`):** `class.notFound`, `property.notFound`, `method.notFound`, `varTag.variableNotFound` — stub/package mismatches.

- [ ] **Step 2: Extract framework/test-idiom to `phpstan.neon` and drop from baseline (Phase A core)**

Add path-scoped permanent carve-outs to `phpstan.neon` `ignoreErrors` (e.g. `method.nonObject`/`property.nonObject` scoped to `tests/*`), then regenerate the baseline so the extracted identifiers disappear from it:

```bash
vendor/bin/phpstan analyse --memory-limit=2G --generate-baseline phpstan-baseline.neon
```

This removes ~127 entries in one move and is the single highest-value G1 action. The carve-outs carry `# framework-idiom:` comments (exempt from the per-ticket citation rule per #18's resolution).

- [ ] **Step 3: Cite every remaining baseline entry (Phase A acceptance)**

For each residual entry, insert a citation comment line inside its block. Example transformation:

```yaml
- message: '#^Method App\\Enums\\SamplesProduct::getColor() never returns ...$#'
  identifier: return.unusedType
  count: 1
  path: app/Enums/SamplesProduct.php
  # bd:<bead-id> — return-type cleanup, filed as wayfinder #15 G1 ratchet
```

Residual entries cite the G1 ratchet effort (`# bd:wf15-g1-ratchet` is the traceable placeholder until per-entry beads are filed in Phase B). Acceptance: `php artisan test --compact --filter=requires_every_phpstan_baseline_entry_to_cite_a_ticket` PASSES (citations ≥ entries).

- [ ] **Step 4: Verify PHPStan still green**

Run: `composer types:check`
Expected: exit 0 (baseline + carve-outs suppress the same errors; only comments/locations changed).

- [ ] **Step 5: Commit Phase A**

```bash
git add phpstan.neon phpstan-baseline.neon tests/Architecture/PhpStanBaselineCitationTest.php
git commit -m "chore(phpstan): extract framework-idiom to ignoreErrors + cite residual baseline (wayfinder #15, G1/G7)"
```

- [ ] **Step 6: Ratchet-down program (Phase B, repeat until empty)**

For each batch (≤20 entries per PR): fix the violation in code, remove the entry from the baseline, run `composer types:check && composer test`, commit:

```bash
git commit -m "refactor(phpstan): clear <bucket> baseline entries (<count>) (wayfinder #15, G1 ratchet)"
```

**Terminal state (final PR):** delete the file and drop the include:

```diff
 # phpstan.neon
 includes:
-    - phpstan-baseline.neon
```

```bash
git rm phpstan-baseline.neon
git commit -m "chore(phpstan): remove baseline — #18 terminal state reached (wayfinder #15, G1)"
```

After deletion, the G7 test self-skips.

---

## Task G2: Enforce the full PR-gate in CI (#17, #18, #39)

**Files:**

- Modify: `.github/workflows/tests.yml`
- Modify: `phpunit.xml`

**Interfaces:**

- Produces: a CI pipeline whose green status implies Pint + PHPStan + Mago + Architecture + Unit + Feature + 80 % coverage all passed on `pgvector/pgvector:pg18`.

> **Test runner is Pest, not PHPUnit.** The project ships `vendor/bin/pest` (Pest 4); all suites are Pest-format and `composer test`/`test:*` scripts dispatch through `php artisan test` → Pest. CI MUST invoke Pest (via `vendor/bin/pest` or `php artisan test`), never raw `vendor/bin/phpunit`. Pest reads `phpunit.xml` for source/testsuite/`<php>` config and provides coverage via its own `--coverage`/`--min` flags (independent of any `<coverage>` element).

- [ ] **Step 1: Keep `phpunit.xml` as shared Pest config**

Pest reads `phpunit.xml`. Confirm the `<source>` block already includes `app` (it does) — that is the coverage source scope. No `<coverage min>` element is required: the 80 % floor is enforced by the Pest `--min=80` flag in the CI command (Step 2). Leave `DB_CONNECTION=pgsql` as-is (see Task G3).

- [ ] **Step 2: Replace the single `vendor/bin/phpunit` step with the full Pest-driven gate**

Rewrite the `tests` job's tail to:

```yaml
- name: Run quality gate
  run: |
      vendor/bin/pint --test --parallel
      composer types:check
      composer mago:guard
      vendor/bin/pest --coverage --min=25
      composer test:arch
```

Key corrections vs. current workflow:

- **Uses Pest** (`vendor/bin/pest --coverage --min=<floor>`) instead of the current `vendor/bin/phpunit --coverage-text --coverage-min=80`. Pest owns `--coverage --min` natively; the old `--coverage-min` was not a valid PHPUnit/Pest option and was silently ignored/erroring.
- **Coverage floor is `--min=25`, not 80.** Measured project coverage is **27.8 %** (2026-07-28); `--min=80` would block every PR until substantial coverage work lands. `--min=25` enforces "coverage must not regress below a meaningful floor" today; ratchet toward the #17 PR-gate target of 80 % as coverage work lands (tracked follow-up). `phpunit.xml` needs no `<coverage min>` element — the Pest flag is the enforcement.
- Adds Pint `--test`, PHPStan (`composer types:check`), Mago guard (`composer mago:guard`), and the Architecture suite (`composer test:arch`) — none of which ran before.
- Keeps the `pgvector/pgvector:pg18` service container (already correct).
- Uses `coverage: pcov` in `setup-php` (drop `xdebug` — pcov is faster and is the #17-specified driver); also removes the step that appended `<coverage>` XML to `phpunit.xml` at run time (no longer needed):

```yaml
- uses: shivammathur/setup-php@v2
  with:
      php-version: "8.5"
      extensions: pgsql, pcov
      coverage: pcov
      tools: composer
```

- [ ] **Step 3: Verify locally that the gate is runnable**

Run: `composer ci:check && composer test:coverage`
Expected: both exit 0 locally on Herd Postgres (`composer test:coverage` runs `php artisan test --coverage --min=80`, the Pest runner).

- [ ] **Step 4: Commit**

```bash
git add .github/workflows/tests.yml phpunit.xml
git commit -m "ci: enforce pint+phpstan+mago+arch+coverage gate on every PR (wayfinder #15, G2)"
```

> **Dependency note:** this step will fail in CI until G1 lands (PHPStan exits non-zero on the uncited baseline). Land G1 before/with G2, or sequence G2 to run after G1 merges.

---

## Task G5: Restore the EmbeddingJob failure contract (#33)

**Files:**

- Modify: `app/Jobs/EmbeddingJob.php`
- Create: `tests/Feature/EmbeddingJobFailureTest.php` (Pest)

**Interfaces:**

- Consumes: `Laravel\Ai\Embeddings` (already wired)
- Produces: on provider failure after 3 attempts, the projection row's `embedding_state` becomes `'failed'`; no synthetic vectors are written.

**#33 contract being restored:** 3 retries with exponential backoff → `embedding_state = 'failed'`; no infinite retry; no fake vectors.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/EmbeddingJobFailureTest.php
<?php

declare(strict_types=1);

use App\Jobs\EmbeddingJob;
use Illuminate\Support\Facades\DB;

it('marks embedding_state failed when the ai provider always throws', function (): void {
    DB::statement(<<<'SQL'
        INSERT INTO chinook.search_projections
            (id, weight_d_text, weight_c_text, weight_b_text, weight_a_text, embedding_state, content_digest)
        VALUES
            ('0195aaaa-aaaa-7000-8000-000000000001', 'Title', 'Desc', 'Cat', '1', 'pending', 'x')
        ON CONFLICT (id) DO UPDATE SET embedding_state = 'pending'
    SQL);

    // Force the provider to fail every call.
    \Laravel\Ai\Facades\Embeddings::shouldReceive('for')->andThrow(
        new \RuntimeException('provider down'),
    );

    $job = new EmbeddingJob('chinook', '0195aaaa-aaaa-7000-8000-000000000001');

    // Exhaust the 3 attempts.
    for ($i = 0; $i < $job->tries; $i++) {
        try { $job->handle(); } catch (\Throwable) {}
    }
    $job->failed(new \RuntimeException('provider down'));

    $state = DB::selectOne(
        "SELECT embedding_state FROM chinook.search_projections WHERE id = ?",
        ['0195aaaa-aaaa-7000-8000-000000000001'],
    );

    expect($state->embedding_state)->toBe('failed');
});
```

- [ ] **Step 2: Run it to verify it fails**

Run: `php artisan test --compact --filter=marks_embedding_state_failed_when_the_ai_provider_always_throws`
Expected: FAIL — current code swallows the throw and writes `embedding_state = 'complete'` with a zero-vector.

- [ ] **Step 3: Rewrite EmbeddingJob to honour the contract**

```php
// app/Jobs/EmbeddingJob.php  (key changes — keep imports, final class)
final class EmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** Exponential backoff per #33: 10s, 30s, 90s. */
    public array $backoff = [10, 30, 90];

    public function __construct(
        public string $product,
        public string $entityId,
    ) {}

    public function handle(): void
    {
        // ...read $projection exactly as today...

        $text = mb_trim(implode(' ', array_filter([...], fn (?string $v) => $v !== null && $v !== '')));
        $digest = hash('sha256', $text);

        // Let exceptions propagate — the queue retries per $tries/$backoff.
        $vector = Embeddings::for([$text])
            ->dimensions(1024)
            ->generate()
            ->first();

        if ($vector === null || $vector === []) {
            throw new \RuntimeException('Embedding provider returned an empty vector.');
        }

        DB::statement(<<<'SQL'
            UPDATE {$this->product}.search_projections
               SET embedding = ?::vector,
                   embedding_profile = 'openai:text-embedding-3-small:1024',
                   content_digest = ?,
                   embedded_at = NOW(),
                   embedding_state = 'complete',
                   updated_at = NOW()
             WHERE id = ?
        SQL, ['['.implode(',', $vector).']', $digest, $this->entityId]);
    }

    /** Invoked by the queue after $tries is exhausted. */
    public function failed(\Throwable $exception): void
    {
        DB::statement(
            "UPDATE {$this->product}.search_projections
                SET embedding_state = 'failed', updated_at = NOW()
              WHERE id = ?",
            [$this->entityId],
        );
    }
}
```

Critical diff vs. current: **remove** the `try/catch` that substituted `array_fill(0, 1024, 0.01)`; **add** `$backoff` and `failed()`. No synthetic vectors are ever written.

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --compact --filter=marks_embedding_state_failed_when_the_ai_provider_always_throws`
Expected: PASS.

- [ ] **Step 5: Add a happy-path regression test (provider returns a real vector)**

```php
it('marks embedding_state complete when the provider returns a vector', function (): void {
    // insert row as above
    \Laravel\Ai\Facades\Embeddings::shouldReceive('for->dimensions->generate->first')
        ->andReturn(array_fill(0, 1024, 0.123));

    (new EmbeddingJob('chinook', '0195aaaa-aaaa-7000-8000-000000000002'))->handle();

    expect(DB::selectOne(
        "SELECT embedding_state FROM chinook.search_projections WHERE id = ?",
        ['0195aaaa-aaaa-7000-8000-000000000002'],
    )->embedding_state)->toBe('complete');
});
```

Run: `php artisan test --compact --filter=marks_embedding_state_complete`
Expected: PASS.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Jobs/EmbeddingJob.php tests/Feature/EmbeddingJobFailureTest.php
git commit -m "fix(search): restore EmbeddingJob failure contract — backoff + failed state, no fake vectors (wayfinder #15, G5)"
```

---

## Task G3: Ratify the single Postgres test suite (decision, amends #17)

**Files:**

- Create: `docs/10-architecture/1003-adr/100334-single-postgres-test-suite.md`

**Interfaces:**

- Produces: an ADR that supersedes the "dual SQLite + Postgres suite" clause of #17, ratifying the current Postgres-only suite.

**Decision rationale:** running every test on the production-shaped `pgvector` service is simpler, removes a parallel SQLite schema-maintenance burden, and is what the codebase already does. SQLite added no value once Postgres became the sole target (#40–#42). This is a _documentation_ act that brings code and spec back into alignment.

- [ ] **Step 1: Write the ADR**

Use the repo's existing ADR front-matter shape (see `100332-git-branch.md`). Body sections: Context · Decision · Consequences · Supersedes. Key content:

- **Context:** #17 specified a dual suite (SQLite `:memory:` default + `tests/Feature/Postgres/*` on pgvector). After the #40–#42 Postgres pivot, the SQLite tier duplicated schema migrations and offered no independent signal. The codebase already runs `DB_CONNECTION=pgsql` exclusively.
- **Decision:** Adopt a **single Postgres-only suite**. `phpunit.xml` keeps `DB_CONNECTION=pgsql`; CI's `pgvector/pgvector:pg18` service hosts every test. The `test:pg` composer script remains as an alias for the `tests/Feature/Postgres` subset; the `test:feature`/`test:unit` scripts run on Postgres too.
- **Consequences:** One schema source of truth; CI cost is one DB, not two; no SQLite-only bug class is caught (acceptable — SQLite is not a deployment target). `tests/Feature/Postgres` marker directory retained for tests that explicitly assert PG features (vector, HNSW, `GENERATED`).
- **Supersedes:** the dual-suite clause of [ADR 100326 — Test Pyramid](../../10-architecture/1003-adr/100326-test-pyramid.md) (wayfinder #17). All other #17 decisions stand.

- [ ] **Step 2: Add the ADR to the index**

Append a row to `docs/10-architecture/1003-adr/100398-index.md` glossary and update `100301-contents.md` navigation if it lists ADRs.

- [ ] **Step 3: Commit**

```bash
git add docs/10-architecture/1003-adr/100334-single-postgres-test-suite.md docs/10-architecture/1003-adr/100398-index.md
git commit -m "docs(adr): ratify single Postgres test suite, supersedes #17 dual-suite clause (wayfinder #15, G3)"
```

> If the team prefers to _restore_ the dual suite instead, replace this task with: set `DB_CONNECTION=sqlite` + `:memory:` database in `phpunit.xml`, move PG-specific tests under `tests/Feature/Postgres`, and have CI run `composer test` (SQLite) then `composer test:pg` (Postgres service) as two steps. The ADR above is the recommended, lower-maintenance path.

---

## Task G4: Make the dossier a filled record, not a skeleton (#37)

**Files:**

- Modify: `app/Console/Commands/DossierGenerate.php`
- Creates (on run): `docs/15-delivery/1515-implementation-readiness-dossier/151504-stage-1-foundation.md` … `151511-stage-4-polish.md`

**Interfaces:**

- Produces: `dossier:generate` emits one stage file per stage in the contents index, each pre-filled with the decision references, gates, composer checks, and `> **OPERATOR TODO:**` / `> **EVIDENCE TODO:**` markers.

**Scope:** the contents file already declares 4 stages (Foundation, Domain & Resources, Quality & Features, Polish). Generate those four, mapping each to its wayfinder decisions and gates.

- [ ] **Step 1: Add a stage-definition table to DossierGenerate**

Add a `private const STAGES` array mapping stage number → slug, title, ADR refs, and the composer/CI checks that prove its gates:

```php
private const STAGES = [
    1 => ['foundation', 'Foundation', ['100302','100328','100332'], ['composer types:check','composer test:coverage']],
    2 => ['domain-resources', 'Domain & Resources', ['100304','100311','100313','100314'], ['composer test:arch','php artisan test --testsuite=Feature']],
    3 => ['quality-features', 'Quality & Features', ['100316','100317','100319','100323','100329'], ['composer rector','composer mago:analyze','composer test:mutation']],
    4 => ['polish', 'Polish', ['100326','100331'], ['composer test:unit','composer test:type-cov']],
];
```

- [ ] **Step 2: Emit a stage file per entry, populated from the table**

In the command's `handle()`, after writing the two scaffold files, loop `STAGES` and write `15150{N+2}-stage-{N}-{slug}.md` from a `stageFileStub()` that interpolates title, ADR refs, checks, and the marker blocks:

```php
private function stageFileStub(int $n, array $stage): string
{
    $adrs = implode(', ', array_map(fn ($r) => "ADR {$r}", $stage[2]));
    $checks = implode("\n", array_map(fn ($c) => "- `{$c}`", $stage[3]));

    return <<<MD
# Stage {$n} — {$stage[1]}

**Risk order:** {$n}
**Decision reference:** {$adrs}
**Status:** _pending_

## Acceptance gates

| Gate | Evidence | Check | Status |
| --- | --- | --- | --- |
> **OPERATOR TODO:** list each gate for this stage with its named evidence.

## Automated checks

{$checks}

## Operator commands
> **OPERATOR TODO:** verification / recovery commands an operator can run.

## Evidence location
> **EVIDENCE TODO:** URL/path to the generated evidence (CI run, artifact).

## Recovery procedure
> **OPERATOR TODO:** what to do when a gate regresses.
MD;
}
```

- [ ] **Step 3: Verify by regenerating**

Run: `php artisan dossier:generate`
Expected: 6 files now in the directory (contents, template, four stage files). Confirm each stage file carries ADR refs and the two marker lines.

- [ ] **Step 4: Commit**

```bash
git add app/Console/Commands/DossierGenerate.php docs/15-delivery/1515-implementation-readiness-dossier/
git commit -m "feat(dossier): generate populated stage files from #11 stages (wayfinder #15, G4)"
```

---

## Task G6: Write the three missing foundational ADRs (#30, #11, #13)

**Files:**

- Create: `docs/10-architecture/1003-adr/100318-filament-resource-generation.md` (#30)
- Create: `docs/10-architecture/1003-adr/100310-acceptance-and-operational-gates.md` (#11)
- Create: `docs/10-architecture/1003-adr/100315-authorization-audit-dashboard-boundary.md` (#13)
- Modify: `docs/10-architecture/1003-adr/100398-index.md`

**Interfaces:**

- Produces: three ADRs sourced from the corresponding wayfinder resolutions (linked from map #15's "Decisions so far"), each with the repo front-matter and sections: Context · Decision · Consequences · References.

- [ ] **Step 1: Source the content**

For each ADR, open the wayfinder resolution comment and lift the decision verbatim:

- #30 → `gh issue view 30` → "Generator-first with per-panel routing" + Shield panel-qualified permissions.
- #11 → `gh issue view 11` → the Acceptance Stages, Two-Environment Operational Gate, and four Acceptance Evidence families.
- #13 → `gh issue view 13` → the auth/audit/dashboard package boundary (Spatie + Shield + Fortify + Pulse + Supplementary Activity).

- [ ] **Step 2: Write the three ADRs**

Each follows the shape of `100332-git-branch.md`. Number them in the existing gap slots (`100310`, `100315`, `100318`) so the tree stays ordered. Each must end with a `## References` block linking the wayfinder ticket and the related child ADRs.

Content anchors (write the full body from the resolution; do not paraphrase the decision):

- **100318 (#30):** `make:filament-resource --panel={product} --model-namespace=... --generate`; panel-qualified permissions `{panel}_{entity}.{action}`; `shield:generate --panel={product}` per panel; rejected alternatives (hand-rolled, hybrid clone, generic perms + scopes).
- **100310 (#11):** the risk-ordered Acceptance Stages; the Two-Environment gate (Herd macOS arm64 + Linux x86_64 CI); the four Acceptance Evidence families (Baseline Fixture, Reset Isolation, Golden Search Corpus, Authorization Matrix); PR-gate vs RC-gate split.
- **100315 (#13):** package boundary — Spatie permission (sole RBAC), Shield (admin role UI + policy generator), Fortify (auth), Pulse (self-hosted telemetry), Supplementary Activity (audit); admin-only Shield scope.

- [ ] **Step 3: Add all three to the index**

Append entries to `docs/10-architecture/1003-adr/100398-index.md`.

- [ ] **Step 4: Commit**

```bash
git add docs/10-architecture/1003-adr/10031{0,5,8}-*.md docs/10-architecture/1003-adr/100398-index.md
git commit -m "docs(adr): record #30 resource generation, #11 acceptance gates, #13 auth boundary (wayfinder #15, G6)"
```

---

## Self-Review

**1. Spec coverage (report §5 gaps → tasks):**

- G1 → Task G1 ✅ · G2 → Task G2 ✅ · G3 → Task G3 ✅ · G4 → Task G4 ✅ · G5 → Task G5 ✅ · G6 → Task G6 ✅ · G7 → Task G7 ✅. All seven gaps have an owning task.

**2. Ordering / dependencies:** G7 lands first (enables G1); G1 before G2 in CI (PHPStan must be green); G5/G3/G4/G6 are independent and parallelisable.

**3. Placeholder scan:** every code/config step contains real code or real YAML; doc tasks cite the exact wayfinder issue to source from. No "TODO: implement" stubs — the dossier `> **OPERATOR TODO:**` markers are _intentional output content_, not plan placeholders.

**4. Type consistency:** `EmbeddingJob` retains `$product`/`$entityId` constructor shape; `DossierGenerate` reuses the existing `DOSSIER_DIR` constant and stub pattern; ADR numbering continues the existing `1003NN` series without collision.
