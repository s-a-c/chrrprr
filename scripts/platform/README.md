# Platform Automation Wrappers

Compliant with [.ai/AI-GUIDELINES.md](../../.ai/AI-GUIDELINES.md) v3b99cda02934ad7cdc87310613fb7faac37a49f19d9620106e96e73cacb6bb8e

Shell scripts in this directory wrap Laravel artisan commands to provide a consistent CLI experience for engineers who may not remember the exact command syntax.

## Responsibilities

- Invoke the matching artisan command (`platform:bootstrap`, `platform:parity-check`, etc.)
- Pre-check required tooling (Bun, Podman/Herd, database availability)
- Capture logs into `storage/logs/` with profile-aware filenames
- Surface recovery guidance when commands fail (linking to docs in `docs/base-platform/`)

## Current & Planned Scripts

| Script | Command | Notes |
|--------|---------|-------|
| `bootstrap.sh` | `php artisan platform:bootstrap` | Detects missing secrets and links to recovery docs |
| `policy-checksum.sh` | `php artisan policy:checksum-monitor` | Used in CI and local validation |
| `dependency-review.sh` | `php artisan platform:dependency-review` | Generates monthly catalog reports |

## Operational Expectations

- Scripts must exit non-zero when the underlying artisan command fails
- All scripts should support `--profile=<value>` when applicable
- Logs should include timestamps and profile context for QA evidence
- Keep environment variable usage limited to documented keys (see `docs/base-platform/README.md`)
