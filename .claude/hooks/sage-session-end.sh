#!/bin/bash
# SAGE SessionEnd hook — post a lifecycle observation to the local SAGE node.
# Soft-fails silently if the node is unreachable. Never blocks agent exit.
SAGE_HOME="${SAGE_HOME:-$HOME/.sage}"
MODE=$(cat "$SAGE_HOME/memory_mode" 2>/dev/null || echo "full")
SAGE_GUI_BIN="${SAGE_GUI_BIN:-/Applications/SAGE.app/Contents/MacOS/sage-gui}"
SAGE_PROVIDER="claude-code"
SAGE_IDENTITY_PATH="/Users/s-a-c/.sage/agents/samples-20260717-claude-code-55429b43/agent.key"
export SAGE_PROVIDER SAGE_IDENTITY_PATH

if [ "$MODE" = "on-demand" ]; then
    exit 0
fi
if [ -x "$SAGE_GUI_BIN" ]; then
    "$SAGE_GUI_BIN" hook session-end 2>/dev/null
fi
exit 0
