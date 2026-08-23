#!/bin/bash
# SAGE SessionStart hook — pre-fetch recent committed memories from the local
# SAGE node and emit them as context. Falls back to a soft nudge if the node
# is unreachable, the agent key is missing, or sage-gui isn't executable.
SAGE_HOME="${SAGE_HOME:-$HOME/.sage}"
MODE=$(cat "$SAGE_HOME/memory_mode" 2>/dev/null || echo "full")
SAGE_GUI_BIN="${SAGE_GUI_BIN:-/Applications/SAGE.app/Contents/MacOS/sage-gui}"
SAGE_PROVIDER="codex"
SAGE_IDENTITY_PATH="/Users/s-a-c/.sage/agents/samples-20260717-codex-932d9718/agent.key"
export SAGE_PROVIDER SAGE_IDENTITY_PATH

if [ "$MODE" = "on-demand" ]; then
    echo "SAGE is in on-demand mode. Use sage_recall to retrieve memories and sage_reflect to save learnings. No automatic memory calls will be made."
    exit 0
fi

if [ -x "$SAGE_GUI_BIN" ] && "$SAGE_GUI_BIN" hook session-start 2>/dev/null; then
    if [ "$MODE" = "bookend" ]; then
        echo "SAGE MODE: bookend — Do NOT call sage_turn every turn. Only call sage_reflect at the end of significant tasks."
    fi
    exit 0
fi

# Direct-write failed — fall back to the soft nudge so the agent still boots.
echo "IMPORTANT: Call sage_inception now, before responding to the user. This is a new session — load your SAGE memory first. Do not skip this step, even if you have a context continuation summary. Those summaries do NOT replace your SAGE memory."
if [ "$MODE" = "bookend" ]; then
    echo "SAGE MODE: bookend — Do NOT call sage_turn every turn. Only call sage_reflect at the end of significant tasks."
fi
