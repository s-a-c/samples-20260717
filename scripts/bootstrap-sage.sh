#!/usr/bin/env bash
# Bootstrap SAGE project/samples-20260717 on the central substrate.
# Thin wrapper around the shared ~/scripts/sage_workspace_bootstrap.py.
#
# Provisions: org samples-20260717, domain project.samples-20260717,
# substrate_ref sage://project/samples-20260717/bootstrap-001.
# Idempotent: safe to re-run. Output written to .local/sage/ (git-ignored).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VENV="$ROOT/.local/sage-bootstrap-venv"
# Pin the SDK to the running container's version. Container and SDK ship
# lockstep (l33tdawg/sage). Bump this when the container is upgraded.
SAGE_SDK_VERSION="${SAGE_SDK_VERSION:-11.8.5}"

# Force the raw loopback endpoint: the SDK cannot trust the Caddy internal CA,
# so an inherited SAGE_URL pointing at the named HTTPS endpoint would fail SSL.
export SAGE_URL="http://127.0.0.1:7243"

mkdir -p "$ROOT/.local/sage/identities"

# (Re)build the venv if missing or if the installed SDK version drifts from the pin.
if [[ ! -x "$VENV/bin/python" ]] || ! "$VENV/bin/pip" show sage-agent-sdk 2>/dev/null \
     | grep -q "^Version: ${SAGE_SDK_VERSION}\$"; then
  python3 -m venv "$VENV"
  "$VENV/bin/pip" install -q "sage-agent-sdk==${SAGE_SDK_VERSION}" httpx
fi

exec "$VENV/bin/python" "$HOME/scripts/sage_workspace_bootstrap.py" \
  --workspace-id samples-20260717 \
  --workspace-type project \
  --org samples-20260717 \
  --org-description "samples-20260717 workspace agent namespace on central SAGE substrate" \
  --domain project.samples-20260717 \
  --memory "samples-20260717 workspace at /Users/s-a-c/Herd/samples-20260717 presents Chinook, Northwind, and Sakila as distinct sample products sharing application capabilities. SAGE owns governed recall; bd owns tasks." \
  --root "$ROOT" \
  "$@"
