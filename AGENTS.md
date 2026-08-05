---
trigger: always_on
---

# Samples Application Agent Instructions

Project-level contract for AI agents working in **samples-20260717**. Global
behavior, rule triggers, skill discovery, git policy, and the DOX hierarchy are
defined in [`~/.config/agents/AGENTS.md`](../../.config/agents/AGENTS.md). This file
adds repo scope and does not duplicate or weaken global rules.

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Instruction Priority](#1-instruction-priority)
- [2. Project Scope](#2-project-scope)
- [3. Agent skills config](#3-agent-skills-config)
- [4. Identity switch](#4-identity-switch)
- [5. ADR Compliance Mandate](#5-adr-compliance-mandate)
    - [5.1. Development Governance](#51-development-governance)
    - [5.2. ADR Creation Triggers](#52-adr-creation-triggers)
    - [5.3. Required Documentation](#53-required-documentation)
    - [5.4. Implementation Process](#54-implementation-process)
    - [5.5. Documentation Maintenance](#55-documentation-maintenance)
- [6. Laravel Boost Guidelines](#6-laravel-boost-guidelines)
    - [6.1. Foundational Context](#61-foundational-context)
        - [6.1.1. Skills Activation](#611-skills-activation)
        - [6.1.2. Conventions](#612-conventions)
        - [6.1.3. Verification Scripts](#613-verification-scripts)
        - [6.1.4. Application Structure \& Architecture](#614-application-structure--architecture)
        - [6.1.5. Frontend Bundling](#615-frontend-bundling)
        - [6.1.6. Documentation Files](#616-documentation-files)
        - [6.1.7. Replies](#617-replies)
    - [6.2. Laravel Boost](#62-laravel-boost)
        - [6.2.1. Tools](#621-tools)
        - [6.2.2. Searching Documentation (IMPORTANT)](#622-searching-documentation-important)
            - [6.2.2.1. Search Syntax](#6221-search-syntax)
        - [6.2.3. Artisan](#623-artisan)
        - [6.2.4. Tinker](#624-tinker)
    - [6.3. PHP](#63-php)
    - [6.4. Deployment](#64-deployment)
    - [6.5. Test Enforcement](#65-test-enforcement)
    - [6.6. Do Things the Laravel Way](#66-do-things-the-laravel-way)
        - [6.6.1. Model Creation](#661-model-creation)
        - [6.6.2. APIs \& Eloquent Resources](#662-apis--eloquent-resources)
        - [6.6.3. URL Generation](#663-url-generation)
        - [6.6.4. Testing](#664-testing)
        - [6.6.5. Vite Error](#665-vite-error)
    - [6.7. Livewire](#67-livewire)
    - [6.8. Laravel Pint Code Formatter](#68-laravel-pint-code-formatter)
    - [6.9. Pest](#69-pest)
- [7. Beads Issue Tracker](#7-beads-issue-tracker)
    - [7.1. Quick Reference](#71-quick-reference)
    - [7.2. Rules](#72-rules)
    - [7.3. Agent Context Profiles](#73-agent-context-profiles)
    - [7.4. Session Completion](#74-session-completion)

</details>

---

## 1. Instruction Priority

1. Explicit user, system, and orchestrator instructions win.
2. The nearest applicable `AGENTS.md` wins for local work details.
3. Global rules in [`~/.config/agents/AGENTS.md`](../../.config/agents/AGENTS.md) and
   `~/.config/agents/rules/` apply when this file and its linked children do not
   say otherwise.

## 2. Project Scope

- Laravel 13 + PHP 8.5 application that presents **Chinook, Northwind, and
  Pagila** as distinct sample products. Shared application capabilities connect
  them without treating their unrelated business concepts as one domain.
- Domain glossary: [`CONTEXT.md`](CONTEXT.md).
- A `Sample Product` is one independently recognisable reference dataset and its
  UX (Chinook, Northwind, or Pagila) — not a tenant or customer account.

## 3. Agent skills config

- **Issue tracker:** Hybrid — GitHub Issues is the source of truth for wayfinder
  maps and decision tickets (native parent/child/blocking + the `wayfinder:*`
  labels already in use by map #1); bd (beads) mirrors GitHub via JSONL sync and
  owns execution-task tracking for AFK implementation slices; local markdown
  (`docs/issues/*.md`) is the fallback when GitHub is unreachable. Run `bd prime`
  for bd workflow context. See [`docs/agents/issue-tracker.md`](docs/agents/issue-tracker.md).
- **Governed memory:** SAGE, namespace `project/samples-20260717`. direnv loads
  `.env.sage` → `SAGE_MCP_BEARER_TOKEN` (project identity). Cite memories as
  `sage://project/samples-20260717/...`. See [`docs/agents/sage.md`](docs/agents/sage.md).
- **PKM:** Siyuan, notebook `samples-20260717` (id `20260719071714-xyhlut0`)
  on the multi-tenant `siyuan-workspaces` container. Direct HTTP via
  `$SIYUAN_API_TOKEN` (inherited from HOME Infisical via direnv
  `source_up`). **The token is shared across four notebooks in this
  container (home, samples-20260717, pgaak, chinook-laravel) — touch ONLY
  the `samples-20260717` notebook. The Control Plane / `the-hub--spoke`
  notebook lives on a SEPARATE container and is unreachable from this
  token.** See [`docs/agents/siyuan.md`](docs/agents/siyuan.md).

## 4. Identity switch

direnv (`.envrc`) is the namespace switch: `cd` into this repo → SAGE identity
becomes `project/samples-20260717`; `cd` out → home identity is restored. All
seven agent hosts (OpenCode, Codex, Claude Code, Cursor, Zed, Junie, GitHub
Copilot) read the same shared SAGE bearer under their expected env-var name.
Infisical is the source of truth; `.env.sage` is a git-ignored offline cache.

## 5. ADR Compliance Mandate

### 5.1. Development Governance

- All development decisions must be documented in the architecture decision records (ADRs) system unless explicitly exempted by a documented exception
- The ADR system is located at `docs/10-architecture/1003-adr/`
- Each new feature, architectural change, or technical decision requires a new ADR entry following the established naming convention (0001-0017+)
- Decisions to deviate from ADR requirements must themselves be documented as exceptions

### 5.2. ADR Creation Triggers

New ADRs are required for:

- Addition of new sample products or domains
- Major architectural changes beyond existing patterns
- Technology selections not documented in current ADRs
- Significant security, performance, or deployment architecture decisions
- Removal or deprecation of existing functionality
- Definition of new APIs or data models

### 5.3. Required Documentation

Every ADR must follow the documentation-formats and documentation-structure rules in `~/.agents/rules/`, including:

- YAML front-matter with title, description, type, tags, and updated date
- Numbered sections with hierarchical structure
- Table of contents and navigation
- References to existing documentation

### 5.4. Implementation Process

- Before any major development work, verify relevant ADRs exist at `docs/10-architecture/1003-adr/`
- All code changes and implementation decisions must reference their enabling ADR
- Wayfinder #15 compliance is the primary ADR enforcement mechanism for this project
- Any ADR-labeled work must be tracked via the Beads issue tracker (Wayfinder #15 epic, work items T1-T17)

### 5.5. Documentation Maintenance

- ADR status should be tracked as "proposed", "accepted", "rejected", or "superseded"
- Newer ADRs take precedence over older ones; old ADRs may be moved to archive when superseded
- All ADR changes are tracked in the wayfinder:map tags
- Review process follows Wayfinder workflow governance

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- filament/filament (FILAMENT) - v5
- laravel/ai (AI) - v0
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/horizon (HORIZON) - v5
- laravel/prompts (PROMPTS) - v0
- laravel/pulse (PULSE) - v1
- laravel/reverb (REVERB) - v1
- laravel/sanctum (SANCTUM) - v4
- laravel/telescope (TELESCOPE) - v5
- livewire/flux (FLUXUI_FREE) - v2
- livewire/flux-pro (FLUXUI_PRO) - v2
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v5
- phpunit/phpunit (PHPUNIT) - v13
- rector/rector (RECTOR) - v2

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `pnpm run build`, `pnpm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
    - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `pnpm run build` or ask the user to run `pnpm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>

<!-- BEGIN BEADS INTEGRATION v:1 profile:minimal hash:6cd5cc61 -->

## 7. Beads Issue Tracker

This project uses **bd (beads)** for issue tracking. Run `bd prime` to see full workflow context and commands.

### 7.1. Quick Reference

```bash
bd ready              # Find available work
bd show <id>          # View issue details
bd update <id> --claim  # Claim work
bd close <id>         # Complete work
```

### 7.2. Rules

- Use `bd` for ALL task tracking — do NOT use TodoWrite, TaskCreate, or markdown TODO lists
- Run `bd prime` for detailed command reference and session close protocol
- Use `bd remember` for persistent knowledge — do NOT use MEMORY.md files

**Architecture in one line:** issues live in a local Dolt DB; sync uses `refs/dolt/data` on your git remote; `.beads/issues.jsonl` is a passive export. See https://github.com/gastownhall/beads/blob/main/docs/SYNC_CONCEPTS.md for details and anti-patterns.

### 7.3. Agent Context Profiles

The managed Beads block is task-tracking guidance, not permission to override repository, user, or orchestrator instructions.

- **Conservative (default)**: Use `bd` for task tracking. Do not run git commits, git pushes, or Dolt remote sync unless explicitly asked. At handoff, report changed files, validation, and suggested next commands.
- **Minimal**: Keep tool instruction files as pointers to `bd prime`; use the same conservative git policy unless active instructions say otherwise.
- **Team-maintainer**: Only when the repository explicitly opts in, agents may close beads, run quality gates, commit, and push as part of session close. A current "do not commit" or "do not push" instruction still wins.

### 7.4. Session Completion

This protocol applies when ending a Beads implementation workflow. It is subordinate to explicit user, repository, and orchestrator instructions.

1. **File issues for remaining work** - Create beads for anything that needs follow-up
2. **Run quality gates** (if code changed) - Tests, linters, builds
3. **Update issue status** - Close finished work, update in-progress items
4. **Handle git/sync by active profile**:
    ```bash
    # Conservative/minimal/default: report status and proposed commands; wait for approval.
    git status

    # Team-maintainer opt-in only, unless current instructions forbid it:
    git pull --rebase
    git push
    git status
    ```
5. **Hand off** - Summarize changes, validation, issue status, and any blocked sync/commit/push step

**Critical rules:**

- Explicit user or orchestrator instructions override this Beads block.
- Do not commit or push without clear authority from the active profile or the current user request.
- If a required sync or push is blocked, stop and report the exact command and error.

<!-- END BEADS INTEGRATION -->

<!--VITE PLUS START-->

# Using Vite+, the Unified Toolchain for the Web

This project is using Vite+, a unified toolchain built on top of Vite, Rolldown, Vitest, tsdown, Oxlint, Oxfmt, and Vite Task. Vite+ wraps runtime management, package management, and frontend tooling in a single global CLI called `vp`. Vite+ is distinct from Vite, and it invokes Vite through `vp dev` and `vp build`. Run `vp help` to print a list of commands and `vp <command> --help` for information about a specific command.

Docs are local at `node_modules/vite-plus/docs` or online at https://viteplus.dev/guide/.

## Built-in Commands vs Scripts

`vp <name>` runs a built-in command. `vp run <name>` runs a `package.json` script or a `vite.config.ts` task. Scripts cannot overwrite built-ins, so `vp dev` and `vp run dev` may do different things. Check `package.json` and `vite.config.ts` first, and run `vp run <name>` when the project defines a script or task with that name.

## Review Checklist

- [ ] Run `vp install` after pulling remote changes and before getting started.
- [ ] Run `vp check` and `vp test` to format, lint, type check and test changes.
- [ ] Check if there are `vite.config.ts` tasks or `package.json` scripts necessary for validation, run via `vp run <script>`.
- [ ] If setup, runtime, or package-manager behavior looks wrong, run `vp env doctor` and include its output when asking for help.

<!--VITE PLUS END-->
