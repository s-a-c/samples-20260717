# Siyuan Integration

## 1. Notebook

`samples-20260717` (id `20260719071714-xyhlut0`) on the central Siyuan instance.

## 2. Endpoint

- Loopback (preferred for agents): `http://127.0.0.1:6806`
- Named HTTPS (Caddy-fronted): `https://siyuan.stands-macbook-pro.local:7244`
- Health: `GET /api/system/version` → `{"code":0,"data":"3.7.2"}`
- Auth header: `Authorization: Token ${SAMPLES_SIYUAN_API_TOKEN}`

## 3. Access path

**Direct HTTP JSON calls.** Siyuan exposes a kernel API at `/api/...` (e.g.
`/api/notebook/lsNotebooks`, `/api/filetree/createDocWithMd`,
`/api/query/apiQuery` for SQL-style block queries). There is no Siyuan MCP
server wired here — the community `@porkll/siyuan-mcp` is AI-generated and
unreviewed; deferred until vetted.

direnv (`.envrc`) exports `SAMPLES_SIYUAN_API_TOKEN`, `SAMPLES_SIYUAN_NOTEBOOK_ID`,
`SAMPLES_SIYUAN_NOTEBOOK_NAME`, `SAMPLES_SIYUAN_API_URL`,
`SAMPLES_SIYUAN_NAMED_URL`, `SAMPLES_SIYUAN_HEALTH_URL`. Infisical is the source
of truth; `.env.sage` is the offline cache.

## 4. ⚠️ Global-token guardrail (read this before any write)

Siyuan's API token is **global** — a single 16-char token in
`/siyuan/workspace/conf/conf.json` (`api.token`) that grants
`RoleAdministrator` over **every notebook on the instance**. Per-notebook token
isolation is not achievable in upstream Siyuan without a second container.

This project chose **discipline-based isolation** (single-operator trade-off).
The mitigations are:

1. The token is stored only in this repo's Infisical project + direnv scope, not
   machine-global.
2. **Touch ONLY the `samples-20260717` notebook.** Never read or write
   `Control Plane Golden Path` or any other notebook.
3. AGENTS.md repeats this guardrail at the project header.

If a write lands in the wrong notebook, undo it via the Siyuan UI before
continuing. A misbehaving agent that ignores this rule must be reported.

If hard isolation is ever required, spin up a second Siyuan container
(`infra/siyuan-samples`) with its own data volume and auth code.

## 5. When to use

- Project notes, design scratch, research dumps that belong in a PKM rather than git
- Reference material an agent should be able to find across sessions
- Long-form context that is too large or unstructured for a SAGE memory

## 6. When not to use

- Tasks / issues → **bd** (beads). See [`issue-tracker.md`](issue-tracker.md).
- Governed agent memory / citable substrate refs → **SAGE**. See [`sage.md`](sage.md).
- Domain glossary → [`../../CONTEXT.md`](../../CONTEXT.md).
- Secrets, credentials, bearer tokens → Infisical (never Siyuan).

## 7. Fail closed

If `SAMPLES_SIYUAN_API_TOKEN` is unset (`.env.sage` absent or Infisical
unreachable), do not attempt writes. A 401 from the API means the token is wrong
or unset — do not paper over it; reload direnv.

## 8. Verification

```bash
curl -s -X POST -H "Authorization: Token ${SAMPLES_SIYUAN_API_TOKEN}" \
  "${SAMPLES_SIYUAN_API_URL}/api/notebook/lsNotebooks" \
  | grep -q samples-20260717 && echo OK || echo FAIL
```

## 9. References

- Infra deployment guide: `~/docs/20-operations/2015-infra/201516-siyuan-guide.html`
- Research findings (first-of-kind): [`../siyuan-research.md`](../siyuan-research.md)
- Infra repo: `~/infra/siyuan`
