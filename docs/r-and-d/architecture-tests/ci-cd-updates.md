# CI/CD Workflow Updates for Architecture Testing

**Date:** 2025-12-31  
**Status:** Complete

## Summary

Updated CI/CD workflows to prioritize **Mago Guard** as the primary architecture validation tool and made **Pest Arch** optional due to performance issues.

## Changes Made

### 1. Composer Scripts (`composer.json`)

#### Changed `test:core` to use Mago Guard only
- **Before:** `"@test:architecture"` (undefined script)
- **After:** `"@test:arch:mago"` (Mago Guard - fast, reliable)

#### Updated `test:arch` command
- **Before:** `["@test:arch:mago", "@test:arch:pest"]` (ran both, Pest Arch often timed out)
- **After:** `["@test:arch:mago"]` (Mago Guard only - primary tool)

#### Added `test:arch:full` command
- **New:** `["@test:arch:mago", "@test:arch:pest"]` (runs both tools when explicitly requested)

### 2. GitHub Actions Workflows

#### Updated `nightly-heavy.yml`
- Added optional Pest Arch test step with:
  - `continue-on-error: true` - won't fail the workflow if Pest Arch times out
  - `timeout-minutes: 20` - extended timeout (may still timeout on large codebases)
  - Runs after the heavy tier workflow completes

#### `tests.yml` (Standard CI)
- Unchanged - continues to use `composer test:all`
- `test:all` → `test:core` → `test:arch:mago` (Mago Guard only)
- Fast, reliable architecture validation for every PR/push

### 3. Documentation Updates

#### `ARCHITECTURE_TESTING_GUIDE.md`
- Updated CI/CD integration examples
- Added status indicators showing:
  - ✅ Mago Guard as primary tool
  - ⚠️ Pest Arch as optional (may timeout)
  - 📝 How to run both tools when needed

## Workflow Behavior

### Standard CI/CD (`tests.yml`)
```
test:all
  └── test:core
      └── test:arch:mago  ✅ Fast, reliable
```

### Nightly Heavy Suite (`nightly-heavy.yml`)
```
test:heavy
  └── (browser, coverage, mutation tests)

test:arch:pest  ⚠️ Optional, continue-on-error
  └── May timeout but won't fail workflow
```

### Manual Execution
```bash
# Run Mago Guard only (fast, recommended)
composer test:arch

# Run both tools (use when needed)
composer test:arch:full

# Run Pest Arch only (if needed)
composer test:arch:pest
```

## Benefits

1. ✅ **Faster CI/CD**: Mago Guard is fast and reliable
2. ✅ **No Failed Workflows**: Pest Arch won't block PRs/pushes
3. ✅ **Still Available**: Pest Arch can be run manually or in nightly builds
4. ✅ **Clear Separation**: Primary tool (Mago Guard) vs optional validation (Pest Arch)

## Migration Notes

- Existing workflows continue to work
- No breaking changes to local development
- Developers can still run Pest Arch manually if needed
- Nightly builds will attempt Pest Arch but won't fail if it times out

## Related Documentation

- [Pest Arch Plugin Investigation](./pest-arch-plugin-investigation.md)
- [Architecture Testing Guide](./ARCHITECTURE_TESTING_GUIDE.md)
- [Architecture Tests Optimization](./architecture-tests-optimization.md)
