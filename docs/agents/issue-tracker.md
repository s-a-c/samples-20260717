# Issue Tracker

This repo overrides the global GitHub Issues default with **bd (beads)**.

## 1. Commands

```bash
bd prime             # load workflow context (run when context is missing or stale)
bd ready             # list ready work
bd show <id>         # inspect a task
bd update <id> --claim
bd close <id> --reason="..."
bd create "Title" --type task --description="..." --priority 2
bd dep <blocker-id> --blocks <blocked-id>   # wire dependencies
bd remember "insight"                        # persistent knowledge (NOT MEMORY.md)
```

Use `bd` for task tracking — not markdown TODO lists or `TodoWrite`. Issue IDs
have the prefix `samples-20260717-` (e.g. `samples-20260717-jl2.3`).

## 2. Beads runtime data

`.beads/` is a local Dolt issue database. Sync with `bd dolt push` / `bd dolt pull`
through the `refs/dolt/data` git ref, not normal git commits. `.beads/issues.jsonl`
is a passive auto-export for viewers, not a backup. This repo runs
`no-git-ops = true` (conservative profile) — run `bd dolt push` explicitly to sync;
bd never auto-pushes.

## 3. Wayfinder

This repo's issue tracker hosts wayfinder maps: a `wayfinder:map`-labelled epic
is the index; child tickets are decisions/investigations sized to one session.
Blocking edges use bd's native dependency relationship. See the `wayfinder` skill.

## 4. Resolution order

1. This file (repo-local bd override).
2. Global default at `~/.config/agents/docs/agents/issue-tracker.md` (GitHub Issues).
