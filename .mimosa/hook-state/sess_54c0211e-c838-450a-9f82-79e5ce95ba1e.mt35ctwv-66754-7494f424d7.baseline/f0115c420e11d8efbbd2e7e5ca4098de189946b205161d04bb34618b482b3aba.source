#!/usr/bin/env bash
# scripts/verify-sage.sh — samples-20260717 SAGE integration verification.
#
# Layer 1+2: generic readiness + workspace assertion. Reuses the generalized
#            home readiness script, pointing it at this repo's evidence file.
# Layer 3:   identity cross-check — the loaded bearer token must map (active)
#            to SAGE_MCP_AGENT_ID. Under v11 this is the NODE OPERATOR
#            (f61638ab...), not the bootstrap project agent — both are correct.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "== samples-20260717 SAGE verification =="

# Layer 1+2: readiness + workspace assertion (generalized home script).
# Point it at this repo's evidence file via the SAGE_EVIDENCE_PATH override.
export SAGE_EVIDENCE_PATH="$ROOT/.local/sage/bootstrap-evidence.json"
~/scripts/verify-sage-agent-readiness.sh

# Layer 3: identity cross-check (requires docker access to the substrate).
# Confirms the loaded SAGE_MCP_TOKEN_ID maps (active) to SAGE_MCP_AGENT_ID.
if [[ -z "${SAGE_MCP_TOKEN_ID:-}" || -z "${SAGE_MCP_AGENT_ID:-}" ]]; then
  echo "FAIL identity — SAGE_MCP_TOKEN_ID / SAGE_MCP_AGENT_ID unset (.env.sage / Infisical not populated?)" >&2
  exit 1
fi

_row="$(docker exec sage-substrate-sage-1 sage-gui mcp-token list \
  | awk -v id="${SAGE_MCP_TOKEN_ID}" '$1==id && $NF=="active" {print $3}')"

if [[ "${_row}" == "${SAGE_MCP_AGENT_ID}" ]]; then
  echo "OK  identity (token ${SAGE_MCP_TOKEN_ID:0:8}… → agent ${SAGE_MCP_AGENT_ID:0:12}…)"
else
  echo "FAIL identity — loaded token is not the project agent" >&2
  echo "     expected agent ${SAGE_MCP_AGENT_ID:0:12}…, got '${_row:-<no active row for token>}'" >&2
  exit 1
fi

echo "samples-20260717 SAGE verification passed."
