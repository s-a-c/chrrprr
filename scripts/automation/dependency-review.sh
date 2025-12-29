#!/usr/bin/env bash

# Compliant with [.ai/AI-GUIDELINES.md](../../.ai/AI-GUIDELINES.md) v3b99cda02934ad7cdc87310613fb7faac37a49f19d9620106e96e73cacb6bb8e

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "${ROOT_DIR}"

STAMP="$(date +"%Y-%m")"
REPORT_PATH="base-platform/dependency-reports/${STAMP}-dependency-review.json"

echo "Running dependency review and writing report to storage/app/${REPORT_PATH}"

php artisan platform:dependency-review --output="${REPORT_PATH}"

php artisan platform:dependency-review-performance-report --report="${REPORT_PATH}"

echo "Dependency review automation complete. Attach storage/app/${REPORT_PATH} and storage/app/base-platform/dependency-performance.log to the monthly governance ticket."
