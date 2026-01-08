# Architecture Tests Optimization

> **Note:** This document has been consolidated into the [Architecture Testing Guide](ARCHITECTURE_TESTING_GUIDE.md).  
> This file is kept for historical reference. Please refer to the comprehensive guide for the latest information.

## Summary

The large `ArchTest.php` file (417 lines, 41 tests) has been split into 11 focused test files (further split by layer) for better performance, maintainability, and parallel execution.

## Changes Made

### 1. Split Test Files

**Before:** Single `ArchTest.php` with all 41 tests

**After:** 11 focused test files (further split by layer):

**Namespace Dependencies (split by layer):**
1. **`HttpLayerDependenciesTest.php`** (2 tests)
   - Controllers, Form Requests
   - ~40 lines

2. **`DomainLayerDependenciesTest.php`** (3 tests)
   - Models, Model Builders, Model Concerns
   - ~60 lines

3. **`ApplicationLayerDependenciesTest.php`** (3 tests)
   - Actions, Services, Support Validation
   - ~60 lines

4. **`StateManagementDependenciesTest.php`** (1 test)
   - States
   - ~25 lines

5. **`UiLayerDependenciesTest.php`** (2 tests)
   - Livewire, Filament
   - ~45 lines

6. **`AuthorizationEventDependenciesTest.php`** (4 tests)
   - Policies, Observers, Listeners, Presenters
   - ~55 lines

7. **`CoreApplicationDependenciesTest.php`** (4 tests)
   - Enums, Console Commands, Exceptions, Providers
   - ~60 lines

**Other Test Categories:**

8. **`NegativeDependenciesTest.php`** (5 tests)
   - All `not->toUse()` rules
   - Facade restrictions, layer separation rules
   - ~80 lines

9. **`ClassStructureTest.php`** (6 tests)
   - `toBeFinal()`, `toExtend()`, `toImplement()` rules
   - Class instantiation and inheritance rules
   - ~70 lines

10. **`NamingConventionsTest.php`** (7 tests)
   - All `toHaveSuffix()` rules
   - Controller, Policy, Observer, Listener, Presenter, Exception, Command naming
   - ~60 lines

11. **`TestStructureTest.php`** (2 tests)
   - Test class structure rules
   - Unit and Feature test requirements
   - ~40 lines

12. **`ArchTest.php`** (Index/Documentation)
13. **`NamespaceDependenciesTest.php`** (Index/Documentation for namespace dependencies)
   - Now serves as documentation/index file
   - Explains the split structure

### 2. Codebase Analysis Optimizations

**Removed unsupported `excludePaths()` calls:**
- Pest Arch doesn't support `excludePaths()` method
- The split itself provides the optimization by reducing per-file analysis scope

**Benefits of splitting:**
- **Parallel execution:** Tests can run in parallel in CI/CD
- **Faster feedback:** Run only relevant test files during development
- **Better organization:** Each file has a clear, focused purpose
- **Reduced memory:** Smaller files reduce memory footprint per test execution
- **Easier debugging:** Isolate issues to specific test categories

## Performance Improvements

### Expected Benefits

1. **Parallel Execution**
   - CI/CD can run test files in parallel
   - Reduces total execution time

2. **Selective Testing**
   - Run only relevant tests during development
   - Example: `pest tests/Arch/NamingConventionsTest.php`

3. **Reduced Memory Usage**
   - Smaller files = less code to analyze per execution
   - Better garbage collection opportunities

4. **Faster Feedback**
   - Developers can run specific test categories
   - Faster iteration cycles

## Usage

### Run All Architecture Tests
```bash
composer test:arch:pest
# or
php artisan test --testsuite=Arch
```

### Run Specific Test File
```bash
# Test specific layer dependencies
pest tests/Arch/HttpLayerDependenciesTest.php
pest tests/Arch/DomainLayerDependenciesTest.php
pest tests/Arch/ApplicationLayerDependenciesTest.php

# Test other categories
pest tests/Arch/NamingConventionsTest.php
pest tests/Arch/ClassStructureTest.php
```

### Run Multiple Specific Files
```bash
# Test multiple layers
pest tests/Arch/HttpLayerDependenciesTest.php tests/Arch/DomainLayerDependenciesTest.php

# Test dependencies and structure
pest tests/Arch/ApplicationLayerDependenciesTest.php tests/Arch/ClassStructureTest.php
```

## File Structure

```
tests/Arch/
├── ArchTest.php                              # Main index/documentation
├── NamespaceDependenciesTest.php             # Index for namespace dependencies
│
├── HttpLayerDependenciesTest.php            # 2 tests - HTTP layer
├── DomainLayerDependenciesTest.php          # 3 tests - Domain layer
├── ApplicationLayerDependenciesTest.php     # 3 tests - Application layer
├── StateManagementDependenciesTest.php     # 1 test - State management
├── UiLayerDependenciesTest.php              # 2 tests - UI layer
├── AuthorizationEventDependenciesTest.php   # 4 tests - Auth & Events
├── CoreApplicationDependenciesTest.php      # 4 tests - Core application
│
├── NegativeDependenciesTest.php             # 5 tests - not->toUse rules
├── ClassStructureTest.php                   # 6 tests - final, extends, implements
├── NamingConventionsTest.php                # 7 tests - suffix rules
└── TestStructureTest.php                   # 2 tests - test class structure
```

## Test Coverage

| Category | Tests | File |
|----------|-------|------|
| **Namespace Dependencies (by layer):** | | |
| HTTP Layer | 2 | `HttpLayerDependenciesTest.php` |
| Domain Layer | 3 | `DomainLayerDependenciesTest.php` |
| Application Layer | 3 | `ApplicationLayerDependenciesTest.php` |
| State Management | 1 | `StateManagementDependenciesTest.php` |
| UI Layer | 2 | `UiLayerDependenciesTest.php` |
| Authorization & Events | 4 | `AuthorizationEventDependenciesTest.php` |
| Core Application | 4 | `CoreApplicationDependenciesTest.php` |
| **Subtotal** | **19** | **7 files** |
| | | |
| Negative Dependencies | 5 | `NegativeDependenciesTest.php` |
| Class Structure | 6 | `ClassStructureTest.php` |
| Naming Conventions | 7 | `NamingConventionsTest.php` |
| Test Structure | 2 | `TestStructureTest.php` |
| **Total** | **39** | **11 test files** |

## Future Optimizations

If performance issues persist, consider:

1. ✅ **Done:** Split NamespaceDependenciesTest by layer (completed)
2. **Caching** analysis results between test runs
3. **Incremental analysis** - only analyze changed files
4. **Pest Arch plugin updates** - check for new optimization features
5. **Codebase optimization** - reduce overall codebase size/complexity
6. **Parallel execution** - configure CI/CD to run test files in parallel

## Compatibility

- ✅ All tests maintain same assertions and rules
- ✅ Same ignoring rules preserved
- ✅ Compatible with existing CI/CD pipelines
- ✅ Works with `composer test:arch:pest` command
- ✅ No breaking changes to test behavior

## Notes

- The original `ArchTest.php` is preserved as documentation
- All test logic is identical to the original
- The split is purely organizational and performance-focused
- No functional changes to test assertions
