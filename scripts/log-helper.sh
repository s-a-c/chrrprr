#!/bin/bash
# Helper script to generate timestamped log file paths
# Usage: LOGFILE=$(./scripts/log-helper.sh script-name [timestamp])
#        LOGFILE=$(LOG_TIMESTAMP=2025-12-01_01-00-00 LOG_SCRIPT_NAME=test ./scripts/log-helper.sh)
# Then: command 2>&1 | tee "$LOGFILE"
#
# AI assistants can set LOG_TIMESTAMP and LOG_SCRIPT_NAME env vars to control
# the log file name, allowing them to monitor progress with tail -f

# Use env vars if set, otherwise use arguments or defaults
LOG_SCRIPT_NAME="${LOG_SCRIPT_NAME:-${1:-unknown}}"
LOG_TIMESTAMP="${LOG_TIMESTAMP:-${2:-$(date -u +"%Y-%m-%d_%H-%M-%S")}}"
LOG_DIR="tmp"
mkdir -p "$LOG_DIR"
printf "%s/%s_%s.log\n" "${LOG_DIR}" "${LOG_SCRIPT_NAME}" "${LOG_TIMESTAMP}"
