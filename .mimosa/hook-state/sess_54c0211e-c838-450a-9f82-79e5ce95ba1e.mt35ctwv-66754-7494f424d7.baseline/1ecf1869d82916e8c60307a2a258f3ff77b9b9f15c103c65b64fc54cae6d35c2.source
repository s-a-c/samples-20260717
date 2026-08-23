#!/bin/bash
# SAGE Stop / SubagentStop hook. Per-turn memory commits remain the agent's
# responsibility (via sage_turn); this hook only asks whether the turn should
# end while durable unclaimed work is pending.
#
# An MCP server cannot wake a session that has already stopped, so the check is
# the inverse: decline the stop once, so the agent handles the work in-session.
# Default-on for Claude Code and Codex, opt-in for other hosts, and silent unless it decides
# to decline. SAGE_STOP_NUDGE=0 explicitly disables it.
#
# Fail-open is deliberate and belt-and-braces: the subcommand allows the stop on
# every internal error, and the || true means even a crashed or missing binary
# ends the turn normally. Only exit code 2 or a deny document blocks a stop, and
# neither can be produced by a failure here.
SAGE_GUI_BIN="${SAGE_GUI_BIN:-/Applications/SAGE.app/Contents/MacOS/sage-gui}"
SAGE_PROVIDER="claude-code"
SAGE_IDENTITY_PATH="/Users/s-a-c/.sage/agents/samples-20260717-claude-code-55429b43/agent.key"
export SAGE_PROVIDER SAGE_IDENTITY_PATH
if [ -x "$SAGE_GUI_BIN" ]; then
    "$SAGE_GUI_BIN" hook stop-check 2>/dev/null || true
fi
exit 0
