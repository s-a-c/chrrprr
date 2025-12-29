# Profile Switching Helpers

Compliant with [.ai/AI-GUIDELINES.md](../../.ai/AI-GUIDELINES.md) v3b99cda02934ad7cdc87310613fb7faac37a49f19d9620106e96e73cacb6bb8e

This directory contains shell scripts that configure environment variables and `.env.*` files for the Base Platform bootstrap workflow.

## Responsibilities

- Load the correct `.env.<profile>` file into `.env` before running `platform:bootstrap`
- Export `BASE_PLATFORM_PROFILE` for downstream artisan commands and shell wrappers
- Validate profile prerequisites (Podman Desktop preferred for container, Docker Desktop fallback, Herd/Homebrew PHP for native)
- Provide human-friendly error messaging when required secrets are missing

## Integration Points

| Script | Consumed By | Purpose |
|--------|-------------|---------|
| `use-native.sh` | `composer setup -- --profile=native` | Symlink `.env.native`, prepare native smoke tests |
| `use-container.sh` | `composer setup -- --profile=container` | Symlink `.env.container`, ensure Sail/Podman ready (Docker fallback) |
| Future profiles | `platform:validate-profiles` | Extend parity coverage without rewriting bootstrap logic |

## Usage

```bash
./scripts/profile/use-native.sh
php artisan platform:bootstrap --profile=native
```

Scripts must be executable (`chmod +x`) and idempotent so developers can re-run them safely.
