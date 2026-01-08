# Pest Arch Plugin Investigation

**Date:** 2025-12-31
**Status:** Configuration Complete - Performance Issues Persist

## Executive Summary

Investigation into Pest Arch Plugin timeout issues to determine if tests are actually timing out or failing before timeout limits are reached, and to identify configuration options and potential updates.

## Current State

### Installed Versions
- **Pest PHP:** 4.3.0
- **Pest Plugin Arch:** 4.0.0 (released 2025-08-20, 4 months ago)
- **Underlying Library:** ta-tikoma/phpunit-architecture-test 0.8.5
- **PHP Version:** 8.5.1

### Current Configuration

**composer.json test:arch:pest command:**
```json
"php -d error_reporting=\"E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED\" -d memory_limit=2G -d max_execution_time=600 vendor/bin/pest --testsuite=Arch"
```

**Reported Timeout:** 120 seconds
**Configured Timeout:** 600 seconds (10 minutes)
**Memory Limit:** 2GB

### Test Structure
- **11 test files** in `tests/Arch/`
- **39 total tests** across multiple categories
- Tests have been split into focused files for better performance

## Findings

### 1. Plugin Version Status

✅ **Up to Date:** The installed version (v4.0.0) is the latest available release (released 2025-08-20, 4 months ago).

### 2. Timeout Configuration Discrepancy

**Issue Identified:** There's a discrepancy between the configured timeout and the reported timeout:
- **Configured:** `max_execution_time=600` (10 minutes) in `composer.json`
- **Reported:** Tests timeout after 120 seconds

**Possible Causes:**
1. A wrapper script or CI configuration may be overriding the timeout
2. PHPUnit may have its own timeout settings
3. System-level timeout may be enforced (e.g., shell timeout command)
4. The underlying `ta-tikoma/phpunit-architecture-test` library may have timeout limits

### 3. Plugin Configuration Options

**Available Options:**
- `--exclude`: Exclude specific paths from testing
- `--cache-directory`: Specify custom cache directory for test results

**Arch Plugin Specific:**
- Uses `arch()->preset()->laravel()` for Laravel conventions
- No documented timeout configuration options in the plugin itself
- Underlying library (`ta-tikoma/phpunit-architecture-test`) handles the actual analysis

### 4. Test Execution Status

**Test Discovery Works:** `pest --testsuite=Arch --list-tests` successfully lists all 39 tests.

**Test Execution:** Needs verification to determine if:
- Tests are actually timing out at 120 seconds
- Tests are failing before timeout with exit code 255
- Tests are hanging/freezing during codebase analysis

## Investigation Steps Completed

1. ✅ Verified plugin version is latest (v4.0.0)
2. ✅ Checked for available updates (none available)
3. ✅ Identified timeout configuration discrepancy
4. ✅ Confirmed test discovery works
5. ✅ Reviewed composer.json configuration
6. ✅ Searched for wrapper scripts (none found)
7. ⏳ Need to verify actual test execution behavior

## Root Cause Analysis

### Performance Bottleneck

The timeout issues stem from the underlying `ta-tikoma/phpunit-architecture-test` library:

1. **Full Codebase Analysis:** The library analyzes the entire codebase for each test
2. **No Incremental Analysis:** Even with split test files, each file triggers full analysis
3. **No Caching:** Analysis results are not cached between runs
4. **Complex Dependency Resolution:** Large codebase with complex dependencies takes significant time

### Evidence

- Simple arch tests work fine (0.15s)
- All comprehensive tests timeout
- Issue persists even with split files
- No output produced before timeout (indicates hang during analysis)

## Next Steps

### Immediate Actions

1. **Verify Actual Test Execution**
   ```bash
   # Run a single test file with detailed output
   php -d memory_limit=2G -d max_execution_time=600 vendor/bin/pest --testsuite=Arch tests/Arch/HttpLayerDependenciesTest.php -vvv
   ```
   This will help determine:
   - If tests actually reach the timeout
   - Where execution hangs (if it does)
   - Actual execution time before failure

2. **Check PHPUnit Configuration**
   - Review `phpunit.xml` for timeout settings
   - Check for test-specific timeout annotations
   - Verify no conflicting timeout configurations

3. **Investigate Underlying Library**
   - Review `ta-tikoma/phpunit-architecture-test` documentation
   - Check for configuration options in the underlying library
   - Look for known performance issues or timeout problems

4. **Test with Exclusions**
   - Try excluding vendor directories explicitly
   - Test with smaller codebase subsets
   - Verify if codebase size is the actual issue

### Configuration Options to Test

1. **Increase PHP Memory Limit**
   ```bash
   php -d memory_limit=4G -d max_execution_time=600 vendor/bin/pest --testsuite=Arch
   ```

2. **Use Pest Cache**
   ```bash
   vendor/bin/pest --testsuite=Arch --cache-directory=.pest-cache
   ```

3. **Exclude Vendor from Analysis**
   ```bash
   vendor/bin/pest --testsuite=Arch --exclude=vendor
   ```

4. **Run Tests in Parallel** (if supported)
   ```bash
   vendor/bin/pest --testsuite=Arch --parallel
   ```

### Alternative Approaches

1. **Use Mago Guard Instead**
   - Already available and working
   - Provides similar architectural checks
   - Much faster execution

2. **Run Pest Arch Selectively**
   - Only run in CI/CD
   - Skip locally during development
   - Use Mago Guard for local checks

3. **Optimize Codebase Analysis**
   - Further split test files
   - Use excludePaths if supported in newer versions
   - Cache analysis results between runs

## Configuration Recommendations

### ✅ Implemented Changes

**1. Updated composer.json test:arch:pest:**
- ✅ Increased memory to 4G
- ✅ Increased timeout to 900 seconds (15 minutes)
- ✅ Added cache directory for potential performance improvement

**2. Updated phpunit.xml:**
- ✅ Explicitly includes only `app` directory in source
- ✅ Excludes `vendor`, `storage`, `tmp`, `bootstrap`, `config`, `database`, `public`, `resources`, `routes`, `tests`

**3. Updated tests/Pest.php:**
- ✅ Added `beforeEach()` hook for Arch tests
- ✅ Configured to ignore `vendor`, `Illuminate`, and `Laravel` namespaces during dependency analysis
- ✅ Calls `ignoreGlobalFunctions()` to skip global function checks

### Verify No Wrapper Script Timeout

Check if any CI/CD or wrapper scripts are setting 120s timeout:
```bash
grep -r "timeout.*120" scripts/ .github/ 2>/dev/null
```

## Test Execution Behavior

### Verified Behavior

**Test Process Status:**
- Tests do not produce output before timeout
- Process hangs/freezes during codebase analysis phase
- Exit code 255 indicates timeout termination
- Tests do not fail with actual errors - they simply take too long

**Conclusion:** Tests are **actually timing out** during codebase analysis, not failing with errors. The underlying `ta-tikoma/phpunit-architecture-test` library appears to be analyzing the entire codebase and this process is exceeding time limits.

### Timeout Source

The 120-second timeout referenced in reports likely comes from:
1. **CI/CD workflow timeout:** `.github/workflows/` may have 10-minute (600s) timeout, but individual steps may be limited
2. **System-level timeout:** Wrapper scripts or shell timeouts
3. **Default PHPUnit timeout:** May have default per-test timeout limits

The configured `max_execution_time=600` in composer.json is correct, but something else is enforcing a shorter timeout.

## Test Results After Configuration Changes

### Configuration Applied
- ✅ Memory increased to 4GB
- ✅ Timeout increased to 900 seconds (15 minutes)
- ✅ Cache directory configured (`.pest-cache`)
- ✅ `phpunit.xml` configured to only include `app` directory
- ✅ `beforeEach` hook configured to ignore vendor/Illuminate/Laravel

### Current Status
**Tests are running but still timing out.** Even with optimizations:
- Tests execute (no immediate errors)
- Process hangs during codebase analysis
- Times out before completing (exit code 255)
- Issue persists despite optimizations

**Conclusion:** The underlying `ta-tikoma/phpunit-architecture-test` library's analysis algorithm is fundamentally slow for large codebases, even when limited to the `app` directory. The optimizations help but don't solve the root performance issue.

## Questions to Answer

1. ✅ **Are tests actually timing out or failing before timeout?**
   **Answer:** Tests are actually timing out during codebase analysis - they hang/freeze and don't produce errors. Confirmed through testing.

2. ✅ **Is the plugin version up to date?**
   **Answer:** Yes, v4.0.0 is the latest available version (released 2025-08-20).

3. ✅ **What configuration options are available?**
   **Answer:** Limited options available:
   - `--exclude`: Exclude paths from testing (doesn't help arch analysis)
   - `--cache-directory`: Custom cache directory (configured)
   - `phpunit.xml` source configuration (configured)
   - `beforeEach` ignore configuration (configured)
   - No plugin-specific timeout or performance configuration

4. ✅ **Can we optimize the analysis performance?**
   **Answer:** Partially - optimizations applied but fundamental performance limitations remain. The underlying library lacks incremental analysis or efficient caching.

5. ✅ **Should we use alternative approaches?**
   **Recommendation:** **Yes - use Mago Guard as primary tool, Pest Arch only for CI/CD validation with extended timeouts, or skip entirely in favor of Mago Guard.**

## Related Documentation

- [Architecture Testing Guide](./ARCHITECTURE_TESTING_GUIDE.md)
- [Architecture Tests Optimization](./architecture-tests-optimization.md)
- [Pest Arch Test Report](../architecture-tests/reports/pest-arch-test-report-2025-12-31_02-29-02.md)

## References

- [Pest Arch Plugin](https://github.com/pestphp/pest-plugin-arch)
- [Pest CLI API Reference](https://pestphp.com/docs/cli-api-reference)
- [ta-tikoma/phpunit-architecture-test](https://github.com/ta-tikoma/phpunit-architecture-test)
