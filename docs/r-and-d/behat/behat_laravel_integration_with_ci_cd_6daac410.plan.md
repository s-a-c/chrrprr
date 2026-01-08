---
name: Behat Laravel Integration with CI/CD
overview: "Implement Behat improvements with full CI/CD integration: optimize database performance, eliminate bootstrapping conflicts, use constructor injection, and update GitHub workflows to properly run Behat suites with environment setup and artifact handling."
todos:
  - id: "1"
    content: Update LaravelContext.php to use LaravelAwareContext trait and implement transaction-based database cleanup
    status: completed
  - id: "2"
    content: Update behat.yml to configure suites with proper database strategies (transactions for unit/api, migrate:fresh for browser)
    status: completed
  - id: "3"
    content: Refactor FeatureContext.php to use constructor injection instead of gatherContexts
    status: completed
  - id: "4"
    content: Add composer scripts for test setup, browser server, and Behat suite execution
    status: completed
  - id: "5"
    content: Update .github/workflows/tests.yml to run Behat suites with proper environment setup
    status: completed
  - id: "6"
    content: Update .github/workflows/browser-tests.yml to integrate Behat browser tests
    status: completed
  - id: "7"
    content: Update .github/workflows/pre-commit.yml to include fast Behat suites
    status: completed
  - id: "8"
    content: Update .github/workflows/nightly-heavy.yml to include comprehensive Behat execution
    status: completed
---

# Behat Laravel Integration with CI/CD Improvements

## Overview

This plan implements comprehensive Behat improvements including architectural fixes, performance optimizations, and full CI/CD integration through updated GitHub workflows.

## Key Improvements

1. **Eliminate Double Bootstrapping**: Remove manual Laravel bootstrapping and use `LaravelAwareContext` trait
2. **Database Performance**: Use transactions for `@unit`/`@api` suites, `migrate:fresh` for `@browser` suite
3. **Context Communication**: Replace `gatherContexts` with constructor injection
4. **CI/CD Integration**: Update GitHub workflows to properly run Behat suites with environment setup
5. **Composer Scripts**: Add automation scripts for local and CI execution

## Implementation Details

### 1. Update `LaravelContext.php`

**File**: `features/bootstrap/LaravelContext.php`

- Implement `LaravelAwareContext` interface with `setApp()` method
- Add constructor parameter `$use_transactions` (default: false)
- Implement `@BeforeScenario` hook:
- `DB::beginTransaction()` when `$use_transactions` is true
- `Artisan::call('migrate:fresh', ['--env' => 'testing'])` when false
- Implement `@AfterScenario` hook to rollback transactions
- Remove all manual bootstrapping code

### 2. Update `behat.yml`

**File**: `behat.yml`

- Update `Cevinio\Behat` extension:
- Set `env_path: '.env.testing'`
- Ensure `bootstrap_path: 'bootstrap/app.php'`
- Configure suites:
- `unit`: Add `LaravelContext` with `use_transactions: true`
- `api`: Add `LaravelContext` with `use_transactions: true`
- `browser`: Keep `LaravelContext` without transactions parameter
- Ensure MinkExtension sessions are properly configured for each suite

### 3. Update `FeatureContext.php`

**File**: `features/bootstrap/FeatureContext.php`

- Remove `gatherContexts()` method and related properties
- Add constructor with dependencies:
- `TenantContext $tenantContext`
- `CqrsContext $cqrsContext` (if needed)
- Implement `LaravelAwareContext` with `setApp()` method
- Update authentication to use `$this->app['auth']->login($user)`
- Replace all context references to use injected properties

### 4. Update `composer.json` Scripts

**File**: `composer.json`

Add scripts:

- `test:setup`: Create `.env.testing` from `.env.example` and generate key
- `test:browser-server`: Start Chrome with remote debugging (macOS: `open`, Linux: `google-chrome`)
- `behat:unit`: Run Behat unit suite
- `behat:api`: Run Behat API suite
- `behat:browser`: Run Behat browser suite
- `behat:all`: Run all Behat suites sequentially

### 5. Update GitHub Workflows

#### 5.1 Update `tests.yml`

**File**: `.github/workflows/tests.yml`

- Add step to create `.env.testing` from `.env.example`
- Add step to generate application key for testing environment
- Update "Setup Test Database" step to use `.env.testing`
- Replace single "Run Behat Tests" step with separate steps:
- Run `composer behat:unit` (fast, transaction-based)
- Run `composer behat:api` (fast, transaction-based)
- Run `composer behat:browser` (requires browser setup, uses migrate:fresh)
- Add conditional browser server setup for browser tests
- Improve artifact upload to include all Behat output

#### 5.2 Update `browser-tests.yml`

**File**: `.github/workflows/browser-tests.yml`

- Add Behat browser suite execution alongside Pest browser tests
- Setup `.env.testing` environment
- Configure browser server for Behat (Chrome with remote debugging)
- Run `composer behat:browser` in addition to Pest browser tests
- Upload Behat screenshots and artifacts on failure

#### 5.3 Update `pre-commit.yml`

**File**: `.github/workflows/pre-commit.yml`

- Add fast Behat suite execution (unit and api only)
- Skip browser tests in pre-commit (too slow)
- Ensure `.env.testing` is set up
- Run `composer behat:unit` and `composer behat:api`

#### 5.4 Update `nightly-heavy.yml`

**File**: `.github/workflows/nightly-heavy.yml`

- Add comprehensive Behat test execution
- Run all Behat suites including browser tests
- Setup proper environment and browser server
- Upload Behat artifacts on failure

### 6. Environment Configuration

- Ensure `.env.testing` template exists or is created from `.env.example`
- Verify PostgreSQL configuration with schema `chrrprr-testing`
- Document required environment variables for Behat execution

## Files to Modify

1. `features/bootstrap/LaravelContext.php` - Complete rewrite
2. `behat.yml` - Suite configurations and extension settings
3. `features/bootstrap/FeatureContext.php` - Constructor injection refactor
4. `composer.json` - Add test automation scripts
5. `.github/workflows/tests.yml` - Add Behat suite execution
6. `.github/workflows/browser-tests.yml` - Integrate Behat browser tests
7. `.github/workflows/pre-commit.yml` - Add fast Behat suites
8. `.github/workflows/nightly-heavy.yml` - Add comprehensive Behat execution

## CI/CD Strategy

### Workflow Execution Order

1. **Pre-commit** (fast feedback):

- Run `behat:unit` and `behat:api` (transaction-based, fast)
- Skip browser tests

2. **Tests** (standard CI):

- Run all Pest tests
- Run `behat:unit` and `behat:api`
- Run `behat:browser` with proper browser server setup

3. **Browser Tests** (dedicated workflow):

- Run Pest browser tests
- Run Behat browser suite
- Upload screenshots and artifacts

4. **Nightly Heavy** (comprehensive):

- Run all test suites including Behat
- Full browser test execution
- Mutation testing and coverage

### Browser Server Setup

For CI environments:

- Use headless Chrome with remote debugging port 9222
- Start browser server before Behat browser suite
- Ensure proper cleanup after tests

### Database Strategy in CI

- **Unit/API suites**: Use transactions (fast, no migration needed)
- **Browser suite**: Use `migrate:fresh` (required for separate browser process)
- Ensure `.env.testing` has correct database credentials

## Testing Strategy

After implementation:

1. Local: Run `composer test:setup` then individual Behat suites
2. CI: Verify workflows run Behat suites correctly
3. Performance: Confirm unit/api suites are significantly faster
4. Artifacts: Verify screenshots and logs are uploaded on failure

## Notes

- Transaction-based cleanup makes unit/api suites 10-100x faster
- Browser suite must use `migrate:fresh` due to process isolation
- Constructor injection improves testability and IDE support
- CI workflows should handle both success and failure scenarios
- Artifact uploads help debug failures in CI environment
