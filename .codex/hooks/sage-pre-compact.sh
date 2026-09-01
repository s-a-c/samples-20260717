#!/bin/bash
# SAGE PreCompact hook — fires right before Claude Code compacts the
# conversation. Compaction discards turn-level detail; this is the last
# chance to crystallise what was learned this session.
#
# If recall-backed compaction is enabled (run: sage-gui nevercompact enable, or
# set SAGE_NEVERCOMPACT=1 for headless/centrally-managed hosts), the evicted turns
# are ALSO captured verbatim as governed memories so a later session can restore
# them. Capture is DEFAULT-OFF, reads the PreCompact payload from stdin, is fully
# silent, soft-fails, and never blocks compaction; the reflection nudge below
# always fires regardless.
SAGE_HOME="${SAGE_HOME:-$HOME/.sage}"
MODE=$(cat "$SAGE_HOME/memory_mode" 2>/dev/null || echo "full")
SAGE_GUI_BIN="${SAGE_GUI_BIN:-/Applications/SAGE.app/Contents/MacOS/sage-gui}"
SAGE_PROVIDER="codex"
SAGE_IDENTITY_PATH="/Users/s-a-c/.sage/agents/samples-20260717-codex-932d9718/agent.key"
export SAGE_PROVIDER SAGE_IDENTITY_PATH
if [ "$MODE" = "on-demand" ]; then
    exit 0
fi
if [ -x "$SAGE_GUI_BIN" ]; then
    "$SAGE_GUI_BIN" hook pre-compact >/dev/null 2>&1 || true
fi
echo "MANDATORY before compaction: Call sage_reflect with a concise summary of (dos, don'ts) from this session, then sage_remember for any durable facts you want to keep. Once the context compacts, the per-turn detail is gone — only what you've committed to SAGE will survive."
