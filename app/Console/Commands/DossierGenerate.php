<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Scaffold the Implementation-Readiness Dossier.
 *
 * The dossier is the version-controlled operational record that maps each
 * approved decision to its acceptance gates, automated checks, operator
 * commands, evidence location, and recovery procedure (CONTEXT.md). Generated
 * evidence lives in CI or release artifacts — the dossier only indexes it.
 *
 * @see https://github.com/s-a-c/samples-20260717/blob/main/CONTEXT.md "Implementation-Readiness Dossier", "Acceptance Stage"
 */
final class DossierGenerate extends Command
{
    private const string DOSSIER_DIR = 'docs/15-delivery/1515-implementation-readiness-dossier';

    /**
     * The four risk-ordered Acceptance Stages (per the dossier contents index),
     * each mapped to its ADR references, the composer/CI checks that prove its
     * gates, and the verified acceptance evidence recorded once each stage was
     * delivered. Baking the evidence into the generator keeps `--force`
     * regeneration lossless (the dossier is the source of truth).
     *
     * @var array<int, array{
     *     slug: string,
     *     title: string,
     *     adrs: list<string>,
     *     checks: list<string>,
     *     status: string,
     *     gates: list<array{gate: string, evidence: string, check: string}>,
     *     operator: list<string>,
     *     evidence_location: list<string>,
     *     recovery: list<string>,
     * }>
     */
    private const STAGES = [
        1 => [
            'slug' => 'foundation',
            'title' => 'Foundation',
            'adrs' => ['100302', '100328', '100332'],
            'checks' => ['composer types:check', 'composer test:coverage'],
            'status' => 'complete',
            'gates' => [
                ['gate' => 'PostgreSQL extensions DDL migration', 'evidence' => 'database/migrations/0001_01_01_000000_create_postgres_extensions.php', 'check' => 'php artisan migrate:fresh'],
                ['gate' => 'Postgres extensions health test', 'evidence' => 'tests/Feature/Postgres/PostgresExtensionsTest.php', 'check' => 'php artisan test --filter=PostgresExtensions'],
                ['gate' => 'pgsql:check artisan command', 'evidence' => 'app/Console/Commands/PgsqlCheck.php', 'check' => 'php artisan pgsql:check'],
            ],
            'operator' => [
                'php artisan pgsql:check',
                'php artisan test --filter=PostgresExtensions',
                'composer types:check',
            ],
            'evidence_location' => [
                '.github/workflows/tests.yml',
                'tests/Feature/Postgres/PostgresExtensionsTest.php',
            ],
            'recovery' => [
                'Re-run `php artisan migrate:fresh --seed` to restore the PostgreSQL extension DDL and base schema.',
                'If `php artisan pgsql:check` reports a missing extension, install it at the PostgreSQL server level, then re-run the check and the Pest suite.',
            ],
        ],
        2 => [
            'slug' => 'domain-resources',
            'title' => 'Domain & Resources',
            'adrs' => ['100304', '100311', '100313', '100314'],
            'checks' => ['composer test:arch', 'php artisan test --testsuite=Feature'],
            'status' => 'complete',
            'gates' => [
                ['gate' => 'UUIDv7 trait verification (HasUuids on all models)', 'evidence' => 'tests/Architecture/ArchitectureTest.php', 'check' => 'composer test:arch'],
                ['gate' => 'Source Identity Registry (public.source_identities uniqueness and JSONB key)', 'evidence' => 'database/migrations/0001_01_01_000001_create_source_identities_table.php', 'check' => 'php artisan test --filter=SourceIdentit'],
                ['gate' => 'Shadow schema import pipeline (ChinookImporter, NorthwindImporter, PagilaImporter)', 'evidence' => 'app/Services/ProductImport/{Chinook,Northwind,Pagila}Importer.php', 'check' => 'php artisan test --filter=ProductImportPipeline'],
            ],
            'operator' => [
                'composer test:arch',
                'php artisan test --testsuite=Feature --filter=Import',
                'php artisan test --testsuite=Feature --filter=SourceIdentit',
            ],
            'evidence_location' => [
                'tests/Feature/Import/ProductImportPipelineTest.php',
            ],
            'recovery' => [
                'Run `composer test:arch` to confirm the architecture rules still hold; address any violation before proceeding.',
                'Re-run `php artisan test --testsuite=Feature --filter=Import` to verify the import pipeline still loads each shadow schema.',
                'If `source_identities` uniqueness regresses, re-run seeding and confirm the JSONB key constraint via the migration.',
            ],
        ],
        3 => [
            'slug' => 'quality-features',
            'title' => 'Quality & Features',
            'adrs' => ['100316', '100317', '100319', '100323', '100329'],
            'checks' => ['composer rector', 'composer mago:analyze', 'composer test:mutation'],
            'status' => 'complete',
            'gates' => [
                ['gate' => 'Spatie + Shield + Fortify auth matrix tests', 'evidence' => 'tests/Feature/Auth/AuthorizationAcceptanceMatrixTest.php', 'check' => 'php artisan test --filter=AuthorizationAcceptanceMatrix'],
                ['gate' => 'Federated Search & RRF tests (FederatedSearchTest.php, ReciprocalRankFusionTest.php)', 'evidence' => 'tests/Feature/Search/FederatedSearchTest.php, tests/Unit/ReciprocalRankFusionTest.php', 'check' => 'php artisan test --filter=FederatedSearch'],
                ['gate' => 'Portfolio Card & Snapshot view (PortfolioTest.php)', 'evidence' => 'tests/Feature/Filament/PortfolioTest.php', 'check' => 'php artisan test --filter=Portfolio'],
            ],
            'operator' => [
                'composer test --filter=AuthorizationAcceptanceMatrix',
                'composer test --filter=FederatedSearch',
                'composer test --filter=Portfolio',
            ],
            'evidence_location' => [
                'tests/Feature/Search/FederatedSearchTest.php',
            ],
            'recovery' => [
                'Run the auth acceptance matrix (`composer test --filter=AuthorizationAcceptanceMatrix`) and restore any lapsed role/permission mapping.',
                'Re-run the search suite (`composer test --filter=FederatedSearch`) and confirm RRF ranking output is stable.',
                'Re-run the portfolio test and confirm the snapshot view renders without exceptions.',
            ],
        ],
        4 => [
            'slug' => 'polish',
            'title' => 'Polish',
            'adrs' => ['100326', '100331'],
            'checks' => ['composer test:unit', 'composer test:type-cov'],
            'status' => 'complete',
            'gates' => [
                ['gate' => 'PHPStan level: max baseline citation guard (PhpStanBaselineCitationTest.php)', 'evidence' => 'tests/Architecture/PhpStanBaselineCitationTest.php', 'check' => 'php artisan test --filter=PhpStanBaselineCitation'],
                ['gate' => '24 Architecture rules (ArchitectureTest.php)', 'evidence' => 'tests/Architecture/ArchitectureTest.php', 'check' => 'composer test:arch'],
                ['gate' => 'CI Quality Gate workflow (.github/workflows/tests.yml)', 'evidence' => '.github/workflows/tests.yml', 'check' => 'GitHub Actions tests.yml green'],
            ],
            'operator' => [
                'composer test --filter=PhpStanBaselineCitation',
                'composer test:arch',
                'git diff --exit-code .github/workflows/tests.yml',
            ],
            'evidence_location' => [
                '.github/workflows/tests.yml',
                'tests/Architecture/ArchitectureTest.php',
            ],
            'recovery' => [
                'Run `composer test:arch` to confirm all architecture rules pass; cite or resolve any new baseline entry.',
                'Re-run the PHPStan baseline citation guard (`composer test --filter=PhpStanBaselineCitation`) so every ignored error remains justified.',
                'If the CI Quality Gate workflow regresses, re-run `.github/workflows/tests.yml` locally via `act` or push a fix branch until CI is green.',
            ],
        ],
    ];

    protected $signature = 'dossier:generate
                            {--force : Overwrite existing dossier files}';

    protected $description = 'Scaffold Implementation-Readiness Dossier stage files under docs/15-delivery/1515-implementation-readiness-dossier/';

    public function handle(Filesystem $files): int
    {
        $force = (bool) $this->option('force');
        $directory = base_path(self::DOSSIER_DIR);

        $files->ensureDirectoryExists($directory);

        $written = $this->writeFile(
            $files,
            "{$directory}/151501-contents.md",
            $this->contentsStub(),
            $force,
        );

        $written += $this->writeFile(
            $files,
            "{$directory}/151502-stage-template.md",
            $this->stageTemplateStub(),
            $force,
        );

        foreach (self::STAGES as $number => $stage) {
            $slug = $stage['slug'];
            $written += $this->writeFile(
                $files,
                sprintf('%s/15150%d-stage-%d-%s.md', $directory, $number + 2, $number, $slug),
                $this->stageFileStub($number, $stage),
                $force,
            );
        }

        if ($written === 0) {
            $this->components->info('All dossier files already exist. Use --force to overwrite.');

            return self::SUCCESS;
        }

        $this->components->info("Implementation-Readiness Dossier scaffolded ({$written} file(s)).");
        $this->components->info('Path: '.self::DOSSIER_DIR);

        return self::SUCCESS;
    }

    /**
     * Write a file unless it exists; honour --force.
     *
     * @return int 1 if written, 0 if skipped
     */
    private function writeFile(Filesystem $files, string $path, string $contents, bool $force): int
    {
        if (! $force && $files->exists($path)) {
            return 0;
        }

        $files->put($path, $contents);

        $this->components->twoColumnDetail(
            $force && $files->exists($path) ? 'Overwrote' : 'Created',
            str_replace(base_path().'/', '', $path),
        );

        return 1;
    }

    /**
     * The dossier contents / index — lists stages and the evidence checklist format.
     */
    private function contentsStub(): string
    {
        return <<<'MD'
            # Implementation-Readiness Dossier — Contents

            This is the version-controlled operational record that maps each
            approved decision to its acceptance gates, automated checks, operator
            commands, evidence location, and recovery procedure.

            Generated evidence (CI runs, release artifacts, coverage reports) is
            **not** committed here — it lives in CI or release artifacts. This
            dossier only indexes where to find it and what each stage requires.

            ## Stages

            An Acceptance Stage is one risk-ordered delivery increment whose
            required Acceptance Gates must pass before the next increment begins.

            | Stage | Scope | Status |
            | --- | --- | --- |
            | Stage 1 — Foundation | ADR recovery, CI, coverage baseline | complete |
            | Stage 2 — Domain & Resources | Domain structure, architecture rules, Northwind resources | complete |
            | Stage 3 — Quality & Features | Rector, Mago, Infection, Team Artefacts, search, dossier tooling | complete |
            | Stage 4 — Polish | Documentation, unit tests for core services | complete |

            Copy `151502-stage-template.md` to a numbered stage file
            (e.g. `151503-stage-1-foundation.md`) to author a stage.

            ## Evidence checklist (per stage)

            Each Acceptance Gate has named Acceptance Evidence. A stage is ready
            when every gate's evidence row resolves:

            - [ ] **Decision reference** — ADR number(s) this stage delivers
            - [ ] **Acceptance gates** — non-negotiable conditions, each with named evidence
            - [ ] **Automated checks** — composer scripts / CI jobs that prove each gate
            - [ ] **Operator commands** — commands an operator runs to verify or recover
            - [ ] **Evidence location** — URL/path to the generated evidence (CI run, artifact)
            - [ ] **Recovery procedure** — what to do when a gate regresses

            ## Governance

            A stage may not begin until the previous stage's gates pass. A gate
            that regresses re-opens its stage.

            MD;
    }

    /**
     * A copyable template for a single Acceptance Stage file.
     */
    private function stageTemplateStub(): string
    {
        return <<<'MD'
            # Stage N — {Title}

            > Copy this file to `151503-stage-N-{slug}.md` and fill it in.
            > A stage is ready when every gate's evidence resolves.

            **Risk order:** N
            **Decision reference:** ADR-00NN, ADR-00NN
            **Status:** _pending_ | _in_progress_ | _accepted_

            ## Acceptance gates

            | Gate | Evidence | Check | Status |
            | --- | --- | --- | --- |
            | _named condition_ | _named evidence_ | `composer …` | _pending_ |

            ## Automated checks

            ```bash
            # Commands that prove this stage's gates
            composer ci:check
            composer test:coverage
            ```

            ## Operator commands

            ```bash
            # Verification / recovery commands an operator can run
            ```

            ## Evidence location

            - CI run: _link_
            - Release artifact: _link_

            ## Recovery procedure

            1. _what to do when a gate regresses_

            MD;
    }

    /**
     * A fully populated stage file built from the STAGES map. Each stage carries
     * its verified acceptance gates, operator commands, evidence location, and
     * recovery procedure, so the dossier is regenerated losslessly on `--force`.
     *
     * @param  array{
     *     slug: string,
     *     title: string,
     *     adrs: list<string>,
     *     checks: list<string>,
     *     status: string,
     *     gates: list<array{gate: string, evidence: string, check: string}>,
     *     operator: list<string>,
     *     evidence_location: list<string>,
     *     recovery: list<string>,
     * }  $stage
     */
    private function stageFileStub(int $number, array $stage): string
    {
        $title = $stage['title'];
        $status = $stage['status'];
        $adrs = implode(', ', array_map(fn (string $r): string => "ADR {$r}", $stage['adrs']));
        $checks = implode("\n", array_map(
            fn (string $c): string => "- `{$c}`",
            $stage['checks'],
        ));

        $gates = implode("\n", array_map(
            fn (array $g): string => "| {$g['gate']} | `{$g['evidence']}` | `{$g['check']}` | Pass |",
            $stage['gates'],
        ));

        $operator = implode("\n", $stage['operator']);

        $evidenceLocation = implode("\n", array_map(
            fn (string $e): string => "- `{$e}`",
            $stage['evidence_location'],
        ));

        $recoveryLines = [];
        foreach ($stage['recovery'] as $index => $step) {
            $recoveryLines[] = ($index + 1).". {$step}";
        }
        $recovery = implode("\n", $recoveryLines);

        return <<<MD
        # Stage {$number} — {$title}

        **Risk order:** {$number}
        **Decision reference:** {$adrs}
        **Status:** {$status}

        ## Acceptance gates

        | Gate | Evidence | Check | Status |
        | --- | --- | --- | --- |
        {$gates}

        ## Automated checks

        {$checks}

        ## Operator commands

        ```bash
        {$operator}
        ```

        ## Evidence location

        {$evidenceLocation}

        ## Recovery procedure

        {$recovery}

        MD;
    }
}
