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
            | Stage 1 — Foundation | ADR recovery, CI, coverage baseline | _pending_ |
            | Stage 2 — Domain & Resources | Domain structure, architecture rules, Northwind resources | _pending_ |
            | Stage 3 — Quality & Features | Rector, Mago, Infection, Team Artefacts, search, dossier tooling | _pending_ |
            | Stage 4 — Polish | Documentation, unit tests for core services | _pending_ |

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
}
