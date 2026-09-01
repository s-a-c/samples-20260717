#!/bin/bash
# SAGE UserPromptSubmit hook — fires when the user submits a new prompt.
# Always surface payload-free coordination. Memory cadence remains mode-specific,
# but neither bookend nor on-demand may hide newly delivered work.
SAGE_HOME="${SAGE_HOME:-$HOME/.sage}"
MODE=$(cat "$SAGE_HOME/memory_mode" 2>/dev/null || echo "full")
SAGE_GUI_BIN="${SAGE_GUI_BIN:-/Applications/SAGE.app/Contents/MacOS/sage-gui}"
SAGE_PROVIDER="codex"
SAGE_IDENTITY_PATH="/Users/s-a-c/.sage/agents/samples-20260717-codex-932d9718/agent.key"
export SAGE_PROVIDER SAGE_IDENTITY_PATH
if [ -x "$SAGE_GUI_BIN" ]; then
    if ! "$SAGE_GUI_BIN" hook inbox-status 2>/dev/null; then
        echo "SAGE inbox check unavailable — do not treat this as zero messages. Call sage_inbox directly."
    fi
fi
if [ "$MODE" = "bookend" ] || [ "$MODE" = "on-demand" ]; then
    exit 0
fi
echo "Reminder: call sage_turn early in your response with the topic + an observation of what just happened. Memories you don't store don't survive."
