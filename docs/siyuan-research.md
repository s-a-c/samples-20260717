# Siyuan Integration — Research Findings (`samples-20260717`)

> First-of-kind Siyuan integration on this machine. Verified live against the
> running `infra/siyuan` stack (v3.7.2) on 2026-07-19. Source of truth for the
> API contract is the upstream kernel source
> ([`kernel/api/notebook.go`](https://github.com/siyuan-note/siyuan/blob/master/kernel/api/notebook.go),
> [`kernel/model/session.go`](https://github.com/siyuan-note/siyuan/blob/master/kernel/model/session.go)),
> corroborated by live `curl` calls below.

<details>
  <summary style="font-size: 1.25em; font-weight: bold; margin: 0.83em 0; cursor: pointer;">
    Expand for Table of Contents
  </summary>

- [1. TL;DR](#1-tldr)
- [2. Notebook creation](#2-notebook-creation)
  - [2.1. Endpoint (authoritative, from `kernel/api/router.go` + `notebook.go`)](#21-endpoint-authoritative-from-kernelapiroutergo--notebookgo)
  - [2.2. ⚠️ Idempotency is the caller's responsibility](#22-️-idempotency-is-the-callers-responsibility)
  - [2.3. Live result (this research session)](#23-live-result-this-research-session)
- [3. Token model — global, not per-notebook](#3-token-model--global-not-per-notebook)
  - [3.1. What the source says](#31-what-the-source-says)
  - [3.2. Verified live](#32-verified-live)
  - [3.3. Isolation verdict](#33-isolation-verdict)
  - [3.4. Practical implications](#34-practical-implications)
- [4. Agent access path](#4-agent-access-path)
  - [4.1. Recommendation: **direct HTTP JSON calls**](#41-recommendation-direct-http-json-calls)
  - [4.2. MCP server landscape (for reference, not adoption today)](#42-mcp-server-landscape-for-reference-not-adoption-today)
- [5. Auth — exact header for programmatic access](#5-auth--exact-header-for-programmatic-access)
  - [5.1. Setting / rotating the API token](#51-setting--rotating-the-api-token)
- [6. Credential storage — Infisical secrets under `SAMPLES_SIYUAN_*`](#6-credential-storage--infisical-secrets-under-samples_siyuan_)
  - [6.1. Pattern (mirrors chinook)](#61-pattern-mirrors-chinook)
- [7. Open questions / risks (need a human decision)](#7-open-questions--risks-need-a-human-decision)
- [8. References](#8-references)

</details>

---

## 1. TL;DR

| Question             | Verdict                                                                                                                                                                                                |
| -------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 1. Create notebook   | `POST /api/notebook/createNotebook` `{"name":"samples-20260717"}` — done, id `20260719071714-xyhlut0`                                                                                                  |
| 2. Token model       | **Global, not per-notebook.** One 16-char `api.token` for the whole instance; any valid token = `RoleAdministrator` over every notebook. Project isolation via tokens is **not achievable** in Siyuan. |
| 3. Agent access path | **Direct HTTP JSON calls** for the integration (matches chinook SDK-over-HTTP precedent). Optional `@porkll/siyuan-mcp` stdio server exists but is AI-generated/unreviewed — defer.                    |
| 4. Auth              | `Authorization: Token <api-token>` header. The `accessAuthCode` is browser-cookie only, never used by agents.                                                                                          |
| 5. Infisical secrets | 6 secrets under `SAMPLES_SIYUAN_*` mirroring `CHINOOK_SAGE_*` shape (see §5).                                                                                                                          |

---

## 2. Notebook creation

### 2.1. Endpoint (authoritative, from `kernel/api/router.go` + `notebook.go`)

```http
POST /api/notebook/createNotebook
Content-Type: application/json
Authorization: Token <api-token>

{"name": "samples-20260717"}
```

- **Gates:** `model.CheckAuth` → `model.CheckAdminRole` → `model.CheckReadonly`. The API token satisfies all three (it grants `RoleAdministrator`).
- **Body:** a single required field, `name` (string). No other fields.
- **Response shape:**
  ```json
  {"code":0,"msg":"","data":{"notebook":{"id":"20260719071714-xyhlut0","name":"samples-20260717","closed":false,…}}}
  ```
- **The notebook `id`** is a 14-digit UTC-timestamp + 6-char random suffix (`YYYYMMDDHHMMSS-aaaaaa`). It is **not** the name and must be captured from the response.

### 2.2. ⚠️ Idempotency is the caller's responsibility

`createNotebook` does **not** check for duplicate names. Calling it twice with the same `name` produces two notebooks with the same name and different IDs. The bootstrap script must therefore **always** `lsNotebooks` first and match by name:

```bash
# Idempotent create-or-return-existing
TOK="$SAMPLES_SIYUAN_API_TOKEN"
NAME="samples-20260717"

EXISTING=$(curl -s -X POST http://127.0.0.1:6806/api/notebook/lsNotebooks \
  -H "Content-Type: application/json" -H "Authorization: Token $TOK" -d '{}' \
  | python3 -c "
import sys, json
nbs = json.load(sys.stdin)['data']['notebooks']
m = [n for n in nbs if n['name'] == '$NAME']
print(m[0]['id'] if m else '')
")

if [[ -z "$EXISTING" ]]; then
  EXISTING=$(curl -s -X POST http://127.0.0.1:6806/api/notebook/createNotebook \
    -H "Content-Type: application/json" -H "Authorization: Token $TOK" \
    -d "{\"name\":\"$NAME\"}" \
    | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['notebook']['id'])")
fi
echo "$EXISTING"   # → 20260719071714-xyhlut0
```

### 2.3. Live result (this research session)

- Notebook **created**: `name=samples-20260717`, `id=20260719071714-xyhlut0`.
- Confirmed by `lsNotebooks` afterwards: the instance now has two notebooks (`Control Plane Golden Path` + `samples-20260717`).
- This is the intended production notebook, not test clutter — no cleanup needed.

---

## 3. Token model — global, not per-notebook

### 3.1. What the source says

From `kernel/model/session.go::CheckAuth`:

```go
if authHeader := c.GetHeader("Authorization"); "" != authHeader {
    var token string
    if after, ok := strings.CutPrefix(authHeader, "Token "); ok { token = after }
    // … also accepts "token ", "Bearer ", "bearer " prefixes
    if "" != token {
        if Conf.Api.Token == token {
            c.Set(RoleContextKey, RoleAdministrator)   // ← full admin
            c.Next()
            return
        }
        c.JSON(http.StatusUnauthorized, …); c.Abort(); return
    }
}
```

There is **one** `Conf.Api.Token` — a single string field in `conf.json` at
`api.token`. The model is binary: the token matches (→ `RoleAdministrator`
over the entire instance) or it doesn't (→ 401). There is no notebook
scoping, no per-notebook token, no ACL.

### 3.2. Verified live

```
$ curl /api/system/getConf -H "Authorization: Token <accessAuthCode>"   # wrong credential
{"code":-1,"msg":"Auth failed [header: Authorization]"}

$ curl /api/system/getConf -H "Authorization: Token <api.token>"        # correct
{"code":0,…,"conf":{"api":{"token":"<matches header>"},…}}
```

The `accessAuthCode` (48 chars, used to log into the browser UI) is **not**
accepted as an API token. They are independent credentials.

### 3.3. Isolation verdict

> **Project isolation via tokens is NOT achievable in Siyuan.** Any agent
> holding the API token has administrator rights over **every** notebook on
> the instance — including the existing `Control Plane Golden Path` notebook
> and any future project's notebook. The isolation boundary is the **process
> instance**, not the notebook.

Role model exists (`Administrator` / `Editor` / `Reader`), but only the
browser-cookie session flow can produce non-Administrator roles; the API
token path always elevates to Administrator. So even a "read-only agent"
cannot be constructed via API tokens today.

### 3.4. Practical implications

- The convention "agent only writes to its project notebook" is **agent
  discipline, not enforced** by Siyuan.
- For genuine isolation you would need a second Siyuan container (heavy:
  separate workspace volume, separate Caddy route, separate backup job).
- Treat the API token like a root credential: store in Infisical, never in
  git, rotate as a coordinated event (rotation invalidates **all** agents at
  once).

---

## 4. Agent access path

### 4.1. Recommendation: **direct HTTP JSON calls**

Rationale:

1. **First-of-kind on this machine.** Direct HTTP keeps the surface minimal
   and auditable — every call is a `curl`-equivalent the operator can read.
2. **Matches the chinook precedent.** The SAGE integration
   (`200517-chinook-laravel-integration-example.md`) drives a Python SDK over
   HTTP from a bootstrap script; no MCP server in the critical path. The same
   shape applies here: a small `scripts/bootstrap-siyuan.sh` that creates the
   notebook idempotently and writes evidence JSON.
3. **The API surface needed is tiny.** A project agent typically needs:
   `lsNotebooks`, `createNotebook` (bootstrap only), `createDocWithMd`,
   `getFile`, `putFile`, `searchFullTextBlock`. Six endpoints = a thin shell
   or PHP helper, not a protocol bridge.
4. **Fail-closed is easier.** A direct HTTP call returns a clear `code:-1`
   on auth failure; an MCP server adds a process boundary that can mask the
   underlying error.

### 4.2. MCP server landscape (for reference, not adoption today)

A web search found several community MCP servers for Siyuan. None are
official; the upstream project tracks MCP support in
[issue #13795](https://github.com/siyuan-note/siyuan/issues/13795) (open).

| Server                                                            | Install                                                                      | Notes                                                                                                                                              |
| ----------------------------------------------------------------- | ---------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| [`@porkll/siyuan-mcp`](https://github.com/porkll/siyuan-mcp)      | `npx @porkll/siyuan-mcp stdio --token <tok> --baseUrl http://127.0.0.1:6806` | 15 tools, TypeScript, npm. **README self-warns:** "code primarily developed with AI assistance, comprehensive code review has not been completed." |
| [`Lancernix/siyuan-mcp`](https://github.com/Lancernix/siyuan-mcp) | `pnpm add siyuan-mcp`                                                        | Wide atomic tool surface (notebooks, docs, blocks, attribute views, SQL).                                                                          |
| [`MyrkoF/siyuan-mcp`](https://github.com/MyrkoF/siyuan-mcp)       | clone + build                                                                | Requires the Query-and-View plugin installed in Siyuan.                                                                                            |

**Config shape (porkll, deferred):**

```json
{
  "mcpServers": {
    "siyuan": {
      "command": "npx",
      "args": ["-y", "@porkll/siyuan-mcp", "stdio",
               "--token", "${SAMPLES_SIYUAN_API_TOKEN}",
               "--baseUrl", "${SAMPLES_SIYUAN_API_URL}"]
    }
  }
}
```

Decision on MCP adoption is parked in §6 — it should follow a successful
direct-HTTP integration, not precede it.

---

## 5. Auth — exact header for programmatic access

| Credential                                           | Header                           | Scope                               | Use case                                                    |
| ---------------------------------------------------- | -------------------------------- | ----------------------------------- | ----------------------------------------------------------- |
| **API token** (16 chars, `conf.json::api.token`)     | `Authorization: Token <token>`   | Whole instance, `RoleAdministrator` | **Agents, scripts, CI** — the only credential an agent uses |
| `accessAuthCode` (48 chars, `--accessAuthCode` flag) | Browser cookie `/siyuan-session` | Browser UI session                  | Humans logging into the web UI; **never** used by agents    |

The API token is also accepted via:
- Lowercase prefix: `Authorization: token <token>`
- Bearer prefix: `Authorization: Bearer <token>`
- Query string: `?token=<token>` (avoid — leaks into logs)

**Recommendation: standardize on `Authorization: Token <token>`** (matches
upstream docs and is unambiguous).

### 5.1. Setting / rotating the API token

The token lives in `<workspace>/conf/conf.json` at key `api.token`. It is
set via the **Settings → About → API token** UI (regenerate button) or by
editing `conf.json` while the service is stopped. On this machine the
workspace is `${SIYUAN_WORKSPACE_DIR}` = `/Users/s-a-c/Documents/the-hub--spoke/.local/siyuan`
(per `infra/siyuan/.env`). Rotation invalidates all agent tokens instantly
and simultaneously.

---

## 6. Credential storage — Infisical secrets under `SAMPLES_SIYUAN_*`

Mirroring the chinook 6-secret shape (`CHINOOK_SAGE_*`), store these in the
Infisical `samples-20260717` project, `dev` env. The values below are
**names**; actual values are written into Infisical, never into git.

| Secret name                    | Value (this project)                           | Why                                                                                                                                                     |
| ------------------------------ | ---------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `SAMPLES_SIYUAN_API_TOKEN`     | the 16-char `api.token` from `conf.json`       | The single auth credential for every API call                                                                                                           |
| `SAMPLES_SIYUAN_NOTEBOOK_ID`   | `20260719071714-xyhlut0`                       | Captured from `createNotebook` response; the project's notebook handle                                                                                  |
| `SAMPLES_SIYUAN_NOTEBOOK_NAME` | `samples-20260717`                             | Human-readable; used for idempotent lookup-by-name                                                                                                      |
| `SAMPLES_SIYUAN_API_URL`       | `http://127.0.0.1:6806`                        | Raw loopback for programmatic calls — avoids Caddy TLS trust issues for non-browser clients (mirrors chinook's `SAGE_URL=http://127.0.0.1:7243` choice) |
| `SAMPLES_SIYUAN_NAMED_URL`     | `https://siyuan.stands-macbook-pro.local:7244` | Named HTTPS route for browser/GUI agent contexts that trust the Caddy CA                                                                                |
| `SAMPLES_SIYUAN_HEALTH_URL`    | `http://127.0.0.1:6806/api/system/version`     | Liveness probe target (returns `{"code":0,"data":"3.7.2"}`)                                                                                             |

### 6.1. Pattern (mirrors chinook)

1. Create a project-scoped Infisical service token in the
   `samples-20260717` project (NOT a workspace token — workspace tokens
   dump all 88+ workspace secrets and ignore `--projectId`).
2. Store at `~/.config/infisical/samples-token` (`umask 077`, `chmod 600`).
3. `.envrc` fetches via `infisical export --env=dev --token="$(cat …)"`,
   caches to `.env.siyuan` (git-ignored), re-exports under the general
   `SIYUAN_*` names so MCP/agent configs are project-agnostic.
4. Marker test (chinook §10.5) confirms the secrets are NOT also sitting in
   the main workspace project.
5. Add `~/Herd/samples-20260717` to `direnv.toml` `[whitelist].exact`.

`.gitignore` additions: `.env.siyuan`, `.local/siyuan/` (bootstrap evidence).

---

## 7. Open questions / risks (need a human decision)

1. **Token-isolation gap is fundamental.** Siyuan cannot scope a token to
   one notebook. Options for the creating-ticket to choose:
   - **(a) Accept it.** Single instance, single token, rely on agent
     discipline + notebook-ID convention. Lowest operational cost. The
     `samples-20260717` agent can technically read/write the
     `Control Plane Golden Path` notebook — it just won't.
   - **(b) Second container per project.** Genuine isolation; heavy on
     backups, ports, Caddy routes. Probably overkill for an R&D sample repo.
   - **(c) Encrypted notebooks** for sensitive content. Adds a per-notebook
     password; still no token scoping. Doesn't solve the agent-trust
     question.
   **Recommendation: (a)** for this first-of-kind, revisit if a second
   project needs Siyuan.

2. **MCP server adoption.** Defer until the direct-HTTP bootstrap works.
   When revisiting, `@porkll/siyuan-mcp` is the leading candidate but its
   README explicitly warns the code is AI-generated and unreviewed. A
   security read of the source is a precondition for adoption.

3. **Write governance.** The infra guide (`201516-siyuan-guide.html`)
   states: *"writes require human approval and flow through the Control
   Plane Anti-Corruption Layer — never directly from a Spoke."* The
   `samples-20260717` repo is **not** a registered Spoke, so this constraint
   doesn't bind — but decide explicitly whether the project agent gets write
   access to its notebook, or read-only + a human-in-the-loop for writes.

4. **Token rotation coordination.** Because the token is global, rotating it
   invalidates every agent and every script at once. Document a rotation
   runbook before adding a second consumer.

5. **Notebook deletion is destructive.** `removeNotebook` deletes the
   notebook and its history. The bootstrap script should never call it
   automatically; provide it as a manual-only documented command.

6. **Backup inclusion.** The existing `com.s-a-c.infra-siyuan-backup`
   LaunchAgent (03:00 daily, 14-archive retention) already covers this
   notebook — it tars the whole workspace. No project-specific backup
   needed. Confirm restore-test cadence with the operator.

---

## 8. References

- Infra guide: `/Users/s-a-c/docs/20-operations/2015-infra/201516-siyuan-guide.html`
- Chinook worked example (secret pattern): `/Users/s-a-c/docs/20-operations/2005-memory/200517-chinook-laravel-integration-example.md`
- Upstream kernel API source: <https://github.com/siyuan-note/siyuan/tree/master/kernel/api>
- Auth middleware source: <https://github.com/siyuan-note/siyuan/blob/master/kernel/model/session.go> (`CheckAuth`)
- MCP issue (open): <https://github.com/siyuan-note/siyuan/issues/13795>
- Community MCP (leading): <https://github.com/porkll/siyuan-mcp>
