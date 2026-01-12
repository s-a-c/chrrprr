#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PROFILE="${BASE_PLATFORM_PROFILE:-native}"
FORCE_CLEAN="${BASE_PLATFORM_FORCE_CLEAN:-0}"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --profile)
      PROFILE="$2"
      shift 2
      ;;
    --force-clean)
      FORCE_CLEAN=1
      shift 1
      ;;
    *)
      shift 1
      ;;
  esac
done

REQUIRED_SECRETS=("FLUX_API_TOKEN")

for secret in "${REQUIRED_SECRETS[@]}"; do
  if [[ -z "${!secret:-}" ]]; then
    printf "Missing required secret: %s\n" "${secret}" >&2
    printf "Consult docs/base-platform/credential-onboarding.md for recovery steps.\n" >&2
    exit 2
  fi
done

if [[ "${FORCE_CLEAN}" == "1" ]]; then
  rm -rf "${ROOT_DIR}/vendor" "${ROOT_DIR}/node_modules" "${ROOT_DIR}/bootstrap/cache" || true
fi

printf "Running Base Platform bootstrap for profile: %s\n" "${PROFILE}"

composer install --working-dir "${ROOT_DIR}" --no-interaction --ansi >/dev/null
php "${ROOT_DIR}/artisan" migrate --force --ansi >/dev/null
bun install --cwd "${ROOT_DIR}" >/dev/null

printf "Bootstrap tasks completed for %s\n" "${PROFILE}"
