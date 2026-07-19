# SAGE Integration

## 1. Namespace

`sage://project/samples-20260717/...`

The namespace is owned by this repo's bootstrap identity and is isolated from
`workspace/home` and from other projects. Scope is determined by which bearer
token direnv loads — see Identity loading.

## 2. MCP endpoint

- URL: `https://sage.stands-macbook-pro.local/v1/mcp`
- Auth: `Authorization: Bearer ${SAGE_MCP_BEARER_TOKEN}`
- Declared in `.mcp.json` (Claude Code), `.cursor/mcp.json` (Cursor),
  `.zed/settings.json` (Zed), `.vscode/mcp.json` (Copilot). OpenCode + Codex
  read the server from their global configs. The name stays `sage` everywhere;
  the token-switch (not a server rename) handles scoping.

## 3. Identity loading

direnv sources `.env.sage` (git-ignored) via `.envrc` and re-exports the project
identity under each host's expected env-var name. ONE shared SAGE bearer covers
all seven hosts (single-operator trade-off: lose per-host revocation, gain one
secret). The token determines scope:

- **Inside this repo** → `SAGE_MCP_BEARER_TOKEN` = project token → `project/samples-20260717`
- **Outside this repo** → home direnv restores the home token → `workspace/home`

`.env.sage` is hydrated from Infisical (source of truth) on `direnv reload`, with
the local cache as offline fallback. If absent, the `SAGE_*` vars stay empty and
the `sage` MCP server fails closed (401). This is correct for a fresh clone
pre-bootstrap.

## 4. v11 authentication model

MCP tokens authenticate as the SAGE **node operator** (`f61638ab…`), not the
bootstrap project agent (`fe68b34e…`, owner of the `project.samples-20260717`
domain). The node operator has broad domain access; project scoping is enforced
by the **domain tag** (`project.samples-20260717`) on memories and the
`SAGE_WORKSPACE_ID` env var, not by per-project agent identities. This differs
from the v10 pattern documented in the chinook-laravel worked example.

## 5. When to use

- Recall project-scoped agent memory across sessions
- Cite or follow substrate refs (`sage://project/samples-20260717/...`)
- Persist durable project context that should survive session resets

## 6. When not to use

SAGE is not a task store, a PKM, validation evidence, or a governance authority.

- Tasks and issues → **bd** (beads). See [`issue-tracker.md`](issue-tracker.md).
- Notes / PKM → **Siyuan**. See [`siyuan.md`](siyuan.md).
- Domain glossary → [`../../CONTEXT.md`](../../CONTEXT.md)
- Implementation evidence / validation output → git + tests, not SAGE

## 7. Dual-track boundary

This integration provisions **Agent Workspace recall** only — the optional,
project-local namespace for this repo's own agent memory.

**Spoke Worker Memory** (`spoke/{spoke-id}/bounty/{bounty-id}`) is a separate
flow: Control-Plane-owned, created by `bounty-dispatch`, authenticated via
Ed25519 signing against `SPOKE_SAGE_IDENTITY`, not via this MCP bearer. The two
flows share no runtime vars or transport.

Do **not** repoint the `sage` server to browse spoke bounty memory. Use the
CEREBRUM dashboard (`https://cerebrum.stands-macbook-pro.local/ui/`) for admin
browsing.

## 8. Fail closed

If SAGE health checks fail, do not claim recall succeeded. Run the verification
command below.

## 9. Verification

```bash
./scripts/verify-sage.sh
```

Layer 1+2 (readiness + workspace assertion) reuse the generalized home script.
Layer 3 (identity cross-check) confirms the loaded token belongs to the node
operator, not a stale home token.

## 10. Bootstrap / re-bootstrap

```bash
./scripts/bootstrap-sage.sh   # idempotent; reprovisions org/domain/agent
```

The SAGE bearer is minted separately via `docker exec sage-substrate-sage-1
sage-gui mcp-token create --name samples-20260717` and stored in Infisical
(`SAMPLES_SAGE_MCP_BEARER_TOKEN`).

## 11. References

- Generic guide: `~/docs/20-operations/2005-memory/200502-project-integration-guide.md`
- Worked example: `~/docs/20-operations/2005-memory/200517-chinook-laravel-integration-example.md`
- Secret management: `~/docs/20-operations/2005-memory/200508-secret-management.md`
- Skills: `sage-home`, `sage-usage`
