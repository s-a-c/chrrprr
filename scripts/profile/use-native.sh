#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
ENV_SOURCE="${ROOT_DIR}/.env.native"
ENV_TARGET="${ROOT_DIR}/.env"

if [[ ! -f "${ENV_SOURCE}" ]]; then
  echo "Missing ${ENV_SOURCE}. Copy .env.example to .env.native and configure native credentials." >&2
  exit 1
fi

ln -sf "${ENV_SOURCE}" "${ENV_TARGET}"
export BASE_PLATFORM_PROFILE="native"

echo "Switched to the native profile. BASE_PLATFORM_PROFILE=native"
