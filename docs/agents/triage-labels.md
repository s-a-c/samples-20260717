---
title: "Triage Labels"
description: "This repo uses the canonical five-role vocabulary (no local override). The table"
type: guide
tags: \[guide, agents, triage, labels]
updated: 2026-07-30
---

# Triage Labels

This repo uses the canonical five-role vocabulary (no local override). The table
is reproduced here for discoverability; the global file remains authoritative.

| Label             | Meaning                                  |
| ----------------- | ---------------------------------------- |
| `needs-triage`    | Maintainer needs to evaluate this issue  |
| `needs-info`      | Waiting on reporter for more information |
| `ready-for-agent` | Fully specified, ready for an AFK agent  |
| `ready-for-human` | Requires human implementation            |
| `wontfix`         | Will not be actioned                     |

## Applying labels

This repo runs bd (beads), not GitHub Issues. Apply labels via bd, not `gh`:

```bash
bd update <id> --labels "ready-for-agent"
```

When a skill mentions a role (e.g. "apply the AFK-ready triage label"), use the
corresponding label string from the table.

## Resolution order

1. This file (repo-local pointer; bd label commands).
2. Global default at `~/.config/agents/docs/agents/triage-labels.md`.
