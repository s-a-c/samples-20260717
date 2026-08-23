#!/bin/bash
# SAGE UserPromptSubmit hook — fires when the user submits a new prompt.
# Always surface payload-free coordination in automated modes. Memory cadence
# remains mode-specific, but bookend must not hide newly delivered work.
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
    if ! "$SAGE_GUI_BIN" hook inbox-status 2>/dev/null; then
        echo "SAGE inbox check unavailable — do not treat this as zero messages. Call sage_inbox directly."
    fi
fi
if [ "$MODE" = "bookend" ]; then
    exit 0
fi
echo "Reminder: call sage_turn early in your response with the topic + an observation of what just happened. Memories you don't store don't survive."
