---
title: "Contributing"
description: "Contribution workflow, pull request expectations, dependency policy, verification, and task tracking."
type: contributing
tags: \[contributing, documentation]
updated: 2026-07-30
---

# Contributing

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. Workflow](#1-workflow)
- [2. Pull requests](#2-pull-requests)
- [3. Dependency changes](#3-dependency-changes)
- [4. Verification](#4-verification)
- [5. Task tracking](#5-task-tracking)

</details>

---

## 1. Workflow

- Branch from the latest `main` for each execution bead.
- Keep branches short-lived. `main` is the only long-lived branch.
- Open one pull request per execution bead. Bundle multiple beads only when they must land atomically, such as a migration, its model, and the architecture test that enforces them.
- Keep each pull request focused. Do not include unrelated cleanup or dependency changes.

## 2. Pull requests

Use a Conventional Commit title because the repository squash-merges the pull request title into `main`:

```text
<type>: <description>
<type>(<scope>): <description>
```

Allowed types are `feat`, `fix`, `chore`, `docs`, `test`, and `refactor`.

Examples:

```text
docs: add contribution policy
feat(chinook): add artist import pipeline
fix(search): preserve lexical-only projections
chore(deps): update Composer dependencies
```

Link the execution bead and its originating GitHub issue when one exists. Complete the pull request template, include the verification commands and results, and identify any beads bundled for atomic delivery.

Pull requests use squash merge. GitHub deletes the source branch after merge.

## 3. Dependency changes

Do not include incidental dependency or lock-file updates in feature, fix, documentation, test, or refactor pull requests.

If a work item requires a new dependency or minimum version, include that change in the work item's pull request and explain why it is required. Routine Composer, npm, and GitHub Actions updates use isolated, grouped Dependabot pull requests on the weekly schedule. Security advisories use out-of-cycle dependency pull requests.

## 4. Verification

Run the narrowest relevant checks while developing, then run the repository gate before review:

```bash
composer ci:check
```

Record the commands and results in the pull request. If an environment prevents a required check, state the blocker instead of marking the check complete.

## 5. Task tracking

GitHub Issues owns wayfinder maps and decision tickets. Beads owns execution tasks. Claim an execution bead before editing and close it only after implementation and verification finish.

See [`docs/agents/issue-tracker.md`](docs/agents/issue-tracker.md) for the tracker workflow.
