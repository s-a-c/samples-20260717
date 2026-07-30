---
title: "SiYuan Integration (samples-20260717)"
description: "Integration contract for the samples-20260717 Siyuan notebook and API access."
type: guide
tags: \[guide, agents, siyuan, integration]
updated: 2026-07-30
---

# SiYuan Integration (samples-20260717)

## 1. Notebook

`samples-20260717` (ID `20260719071714-xyhlut0`) on the `siyuan-workspaces`
multi-tenant container.

## 2. Endpoint

- Loopback (preferred for agents): `http://127.0.0.1:6807`
- Named HTTPS (Caddy-fronted): `https://siyuan-workspaces.stands-macbook-pro.local`
- Health: `GET /api/system/version` → `{"code":0,"data":"3.x.x"}`
- Auth header: `Authorization: Token ${SIYUAN_API_TOKEN}`

## 3. Access path

Direct HTTP JSON. SiYuan exposes a kernel API at `/api/...` (e.g.
`/api/notebook/lsNotebooks`, `/api/filetree/createDocWithMd`,
`/api/query/apiQuery` for SQL-style block queries). No SiYuan MCP server
is wired here.

direnv (`.envrc`) hydrates `.env.siyuan` and exports `SIYUAN_API_TOKEN`,
`SIYUAN_API_URL`, `SIYUAN_NAMED_URL`, `SIYUAN_HEALTH_URL`,
`SIYUAN_WORKSPACE_ID`, `SIYUAN_NOTEBOOK_ID`, `SIYUAN_NOTEBOOK_NAME`.

## 4. Shared multi-tenant container — read this before any write

The `siyuan-workspaces` container hosts four notebooks: `home`,
`samples-20260717`, `pgaak`, `chinook-laravel`. The single API token
is admin over all four.

Discipline-based isolation within this container:

1. **Touch ONLY the `samples-20260717` notebook**. Never read or write
   `home`, `pgaak`, `chinook-laravel`, or any other notebook on this
   container.
2. The Control Plane / `the-hub--spoke` notebook lives on a SEPARATE
   container and is unreachable from this token.
3. AGENTS.md repeats this guardrail at the project header.

If a write lands in the wrong notebook, undo it via the SiYuan UI.

## 5. When to use

- Samples application design notes (Chinook / Northwind / Pagila sample
  products, cross-product shared capabilities), research dumps,
  reference material that belongs in a PKM rather than git.
- Long-form context that is too large or unstructured for a SAGE memory.

## 6. When not to use

- Tasks / issues → bd (beads). See [`issue-tracker.md`](issue-tracker.md).
- Governed agent memory / citable substrate refs → SAGE. See
  [`sage.md`](sage.md).
- Domain glossary → [`../../CONTEXT.md`](../../CONTEXT.md).
- Secrets, credentials, bearer tokens → Infisical (never SiYuan).

## 7. Fail closed

If `SIYUAN_API_TOKEN` is unset, do not attempt writes. A 401 from the API
means the token is wrong or unset — reload direnv.

## 8. Verification

```bash
curl -s -X POST -H "Authorization: Token ${SIYUAN_API_TOKEN}" \
  "${SIYUAN_API_URL}/api/notebook/lsNotebooks" \
  | grep -q '"name":"samples-20260717"' && echo OK || echo FAIL
```

## 9. References

- Infra deployment guide: `~/docs/20-operations/2015-infra/201516-siyuan-guide.html`
- Infra repo: `~/infra/siyuan`
- Plan: `~/docs/superpowers/plans/2026-07-20-siyuan-workspaces-container.md`
