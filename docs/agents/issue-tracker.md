---
title: "Issue Tracker"
description: "This repo uses a **hybrid** tracker: GitHub Issues is the source of truth for"
type: guide
tags: \[guide, agents, issue, tracker]
updated: 2026-07-30
---

# Issue Tracker

This repo uses a **hybrid** tracker: GitHub Issues is the source of truth for
planning artefacts (wayfinder maps, decision tickets); bd (beads) mirrors for
agent convenience and owns execution-task tracking; local markdown is the
fallback when GitHub is unreachable.

## 1. Layer 1 — GitHub Issues (primary)

GitHub is authoritative for anything needing native parent/child relationships,
blocking edges, or the `wayfinder:*` label vocabulary:

- `wayfinder:map` — the index issue for a planning effort
- `wayfinder:research` — AFK research decision ticket
- `wayfinder:prototype` — HITL prototype decision ticket
- `wayfinder:grilling` — HITL decision interview ticket
- `wayfinder:task` — manual work required to unblock a decision

Map #1 (`Wayfinder — Laravel Sample Database Products`, issue #1) and its 12
child decisions (#2–#13) live here. Future wayfinder maps continue on GitHub
for continuity.

### Commands

```bash
gh issue create --title "..." --body "$(cat <<'EOF'
...
EOF
)"                                                # create (heredoc for multi-line body)
gh issue view <N> --comments                      # read with comments
gh issue list --state open --json number,title,body,labels,assignees
gh issue comment <N> --body "..."
gh issue edit <N> --add-label "wayfinder:grilling" --remove-label "..."
gh issue edit <N> --add-assignee "@me"            # claim
gh issue close <N> --comment "..."
gh sub-issue add <parent-N> <child-N>             # native parent/child (GitHub sub-issues API)
```

## 2. Layer 2 — bd mirror + execution tasks (sync)

bd plays two roles:

1. **Mirror of GitHub** — refreshed via JSONL round-trip so `bd ready`, `bd dep`,
   and `bd graph` work against the current frontier. bd never writes back to GitHub.
2. **Execution tasks** — AFK implementation slices (TDD work, build tasks)
   spawned from a wayfinder decision live on bd directly and carry
   `external_ref: "gh-N"` pointing at their originating decision ticket.

### Commands

```bash
bd prime                                          # load workflow context when stale
bd ready                                          # list ready execution work
bd show <id>                                      # inspect a task
bd update <id> --claim                            # claim
bd close <id> --reason="..."                      # complete
bd create "Title" --type task --description="..." --priority 2 --external-ref="gh-42"
bd dep <blocker-id> --blocks <blocked-id>
bd remember "insight"                             # persistent knowledge (NOT MEMORY.md)
```

### Refreshing the mirror

The current bd install has no `bd github sync` subcommand. Use the JSONL round-trip:

```bash
gh issue list --repo s-a-c/samples-20260717 --state all --json \
  number,title,body,labels,assignees,state \
| jq -c '. | {title, description: .body, labels: (.labels | map(.name)),
              status: (if .state == "closed" then "closed" else "open" end),
              external_ref: ("gh-" + (.number | tostring))}' \
| bd import -
```

bd issue IDs carry the prefix `samples-20260717-` (e.g. `samples-20260717-jl2.3`).

## 3. Layer 3 — local markdown fallback (`docs/issues/`)

If GitHub is unreachable, capture decisions in `docs/issues/<slug>.md` using the
wayfinder skill's local-markdown tracker convention. Reconcile to GitHub when
connectivity returns; do not let local and GitHub diverge for more than one session.

## 4. Beads runtime data

`.beads/` is a local Dolt issue database. Sync with `bd dolt push` / `bd dolt pull`
through the `refs/dolt/data` git ref, not normal git commits. `.beads/issues.jsonl`
is a passive auto-export for viewers, not a backup. This repo runs
`no-git-ops = true` (conservative profile) — run `bd dolt push` explicitly to sync;
bd never auto-pushes.

## 5. Wayfinder

Wayfinder maps are GitHub `wayfinder:map`-labelled issues; child tickets are
their sub-issues. Blocking edges use GitHub's native parent/child and issue-body
links. bd mirrors the map for `bd ready` / dependency inspection but never owns
the canonical map. See the `wayfinder` skill.

## 6. Resolution order

1. This file (repo-local hybrid policy).
2. Global default at `~/.config/agents/docs/agents/issue-tracker.md` (GitHub Issues).
