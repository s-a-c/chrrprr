# Architecture Testing Guide & Manifest

**Last Updated:** 2025-12-31  
**Version:** 2.0

## Table of Contents

1. [Overview](#overview)
2. [Architecture Testing Strategy](#architecture-testing-strategy)
3. [Test Suite Structure](#test-suite-structure)
4. [Tool Comparison](#tool-comparison)
5. [Test Coverage](#test-coverage)
6. [Usage Guide](#usage-guide)
7. [Performance Considerations](#performance-considerations)
8. [Best Practices](#best-practices)
9. [Future Enhancements](#future-enhancements)
10. [Troubleshooting](#troubleshooting)

---

## Overview

This guide provides comprehensive documentation for the architecture testing suite, which enforces proper layer separation, dependency management, and code structure across the application.

### Purpose

Architecture tests ensure:
- **Layer Separation:** Components only depend on appropriate layers
- **Dependency Management:** Proper namespace usage and restrictions
- **Code Structure:** Classes follow architectural patterns (final, extends, implements)
- **Naming Conventions:** Consistent naming patterns across the codebase
- **Test Quality:** Test classes follow proper structure

### Tools

The architecture testing suite uses two complementary tools:

1. **Mago Guard** - Fast, reliable perimeter and structural rule validation
2. **Pest Architecture Tests** - Comprehensive rule validation (performance issues)

---

## Architecture Testing Strategy

### Dual-Tool Approach

We use a **dual-tool strategy** to balance speed and comprehensiveness:

#### Mago Guard (Primary Tool)
- ✅ **Fast execution** (seconds)
- ✅ **Reliable** (no timeout issues)
- ✅ **CI/CD ready**
- ✅ **Development workflow friendly**
- ✅ Covers: Namespace dependencies, Class structure

#### Pest Architecture Tests (Comprehensive Validation)
- ⚠️ **Performance issues** (timeouts)
- ✅ **Comprehensive coverage** (all rule types)
- ✅ **Negative rules** (not->toUse)
- ✅ **Naming conventions**
- ✅ **Test structure rules**

### Recommended Workflow

1. **During Development:**
   - Use Mago Guard for real-time validation
   - Run `composer mago:guard` before commits
   - Fast feedback loop

2. **Pre-Commit:**
   - Run Mago Guard (fast, reliable)
   - Optionally run specific Pest arch tests if needed

3. **CI/CD Pipeline:**
   - Run Mago Guard (required)
   - Run Pest arch tests in nightly builds (when performance improves)

4. **Architecture Audits:**
   - Run full Pest arch test suite
   - Comprehensive validation
   - Document findings

---

## Test Suite Structure

### File Organization

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

### Test Categories

#### 1. Namespace Dependencies (19 tests across 7 files)

**HTTP Layer** (`HttpLayerDependenciesTest.php`)
- Controllers dependencies
- Form Requests dependencies

**Domain Layer** (`DomainLayerDependenciesTest.php`)
- Models dependencies
- Model Builders dependencies
- Model Concerns dependencies

**Application Layer** (`ApplicationLayerDependenciesTest.php`)
- Actions dependencies
- Services dependencies
- Support Validation dependencies

**State Management** (`StateManagementDependenciesTest.php`)
- States dependencies

**UI Layer** (`UiLayerDependenciesTest.php`)
- Livewire dependencies
- Filament dependencies

**Authorization & Events** (`AuthorizationEventDependenciesTest.php`)
- Policies dependencies
- Observers dependencies
- Listeners dependencies
- Presenters dependencies

**Core Application** (`CoreApplicationDependenciesTest.php`)
- Enums dependencies
- Console Commands dependencies
- Exceptions dependencies
- Providers dependencies

#### 2. Negative Dependencies (5 tests)

**File:** `NegativeDependenciesTest.php`
- Controllers should not use facades
- Services should not use facades
- Models should not depend on HTTP layer
- Enums should not have domain dependencies
- Console Commands should not depend on HTTP layer
- DB facade usage restrictions

#### 3. Class Structure (6 tests)

**File:** `ClassStructureTest.php`
- Controllers should be instantiable classes
- Models should extend Eloquent Model
- Services should be final
- Actions should be final
- Policies should be final
- Enums should implement BackedEnum

#### 4. Naming Conventions (7 tests)

**File:** `NamingConventionsTest.php`
- Controllers suffix
- Policies suffix
- Observers suffix
- Listeners suffix
- Presenters suffix
- Exceptions suffix
- Commands suffix

#### 5. Test Structure (2 tests)

**File:** `TestStructureTest.php`
- Unit tests should extend TestCase
- Feature tests should extend TestCase

---

## Tool Comparison

### Mago Guard vs Pest Architecture Tests

| Feature | Mago Guard | Pest Arch |
|---------|------------|-----------|
| **Execution Speed** | ✅ Seconds | ⚠️ Timeouts (>120s) |
| **Reliability** | ✅ Consistent | ⚠️ Performance issues |
| **Namespace Dependencies** | ✅ Full coverage | ✅ Full coverage |
| **Negative Dependencies** | ❌ Not supported | ✅ Supported |
| **Class Structure** | ✅ Supported | ✅ Supported |
| **Naming Conventions** | ❌ Not supported | ✅ Supported |
| **Test Structure** | ❌ Excluded | ✅ Supported |
| **CI/CD Ready** | ✅ Yes | ⚠️ Needs optimization |
| **Development Use** | ✅ Excellent | ⚠️ Too slow |
| **Configuration** | TOML file | PHP code |

### Coverage Matrix

| Rule Type | Mago Guard | Pest Arch | Status |
|-----------|------------|-----------|--------|
| Namespace Dependencies (toOnlyUse) | ✅ 20 rules | ✅ 19 tests | **Covered** |
| Negative Dependencies (not->toUse) | ❌ | ✅ 5 tests | **Pest only** |
| Class Structure (final, extends) | ✅ 4 rules | ✅ 6 tests | **Both** |
| Naming Conventions (suffix) | ❌ | ✅ 7 tests | **Pest only** |
| Test Structure | ❌ | ✅ 2 tests | **Pest only** |

---

## Test Coverage

### Total Test Count

- **11 test files**
- **39 total tests**
- **7 layer-specific dependency test files**
- **4 additional test category files**

### Detailed Breakdown

| Category | Tests | Files | Tool |
|----------|-------|-------|------|
| HTTP Layer Dependencies | 2 | 1 | Both |
| Domain Layer Dependencies | 3 | 1 | Both |
| Application Layer Dependencies | 3 | 1 | Both |
| State Management Dependencies | 1 | 1 | Both |
| UI Layer Dependencies | 2 | 1 | Both |
| Authorization & Event Dependencies | 4 | 1 | Both |
| Core Application Dependencies | 4 | 1 | Both |
| Negative Dependencies | 5 | 1 | Pest only |
| Class Structure | 6 | 1 | Both |
| Naming Conventions | 7 | 1 | Pest only |
| Test Structure | 2 | 1 | Pest only |
| **Total** | **39** | **11** | |

---

## Usage Guide

### Running Tests

#### Mago Guard (Recommended)

```bash
# Run Mago Guard
composer mago:guard

# Expected output: "INFO No issues found."
```

#### Pest Architecture Tests

```bash
# Run all architecture tests
composer test:arch:pest

# Run specific test file
pest tests/Arch/HttpLayerDependenciesTest.php
pest tests/Arch/DomainLayerDependenciesTest.php

# Run multiple specific files
pest tests/Arch/HttpLayerDependenciesTest.php tests/Arch/DomainLayerDependenciesTest.php
```

### Development Workflow

1. **Before Committing:**
   ```bash
   composer mago:guard
   ```

2. **When Working on Specific Layer:**
   ```bash
   # Example: Working on HTTP layer
   pest tests/Arch/HttpLayerDependenciesTest.php
   ```

3. **Full Validation:**
   ```bash
   composer test:arch:pest
   ```

### CI/CD Integration

**Recommended CI/CD Configuration:**

```yaml
# Fast validation (always run - primary tool)
- name: Mago Guard
  run: composer mago:guard

# Comprehensive validation (nightly builds only - optional, may timeout)
- name: Pest Architecture Tests
  continue-on-error: true
  timeout-minutes: 20
  run: composer test:arch:pest
```

**Current Status:**
- ✅ **Mago Guard** is the primary architecture validation tool (fast, reliable)
- ⚠️ **Pest Arch** is configured but may timeout on large codebases
- 📝 Pest Arch tests are available via `composer test:arch:pest` but excluded from standard workflows
- 🔄 Use `composer test:arch:full` to run both Mago Guard and Pest Arch

---

## Performance Considerations

### Current Performance Issues

**Pest Architecture Tests:**
- ⚠️ All test files timeout after 120 seconds
- ⚠️ Even split files experience timeouts
- ⚠️ Performance issues prevent regular use

**Root Causes:**
1. Large codebase analysis overhead
2. Complex dependency graphs
3. Inefficient analysis algorithm
4. Memory constraints

### Optimization Strategies

#### Completed Optimizations

1. ✅ **Split large test file** into focused files
2. ✅ **Split by architectural layer** for better organization
3. ✅ **Added Mago Guard** as primary validation tool

#### Recommended Future Optimizations

1. **Caching Strategy**
   - Cache analysis results between runs
   - Only re-analyze changed files
   - Use git diff to identify modifications

2. **Incremental Analysis**
   - Analyze only changed files
   - Skip unchanged files
   - Use file modification timestamps

3. **Timeout Adjustments**
   - Increase timeout to 300-600 seconds
   - Configure per-test-file timeouts
   - Add progress indicators

4. **Codebase Optimization**
   - Review and reduce codebase complexity
   - Optimize class dependencies
   - Consider excluding vendor from analysis

5. **Parallel Execution**
   - Run test files in parallel
   - Distribute across multiple workers
   - Use CI/CD parallelization features

---

## Best Practices

### Writing Architecture Tests

1. **Keep Tests Focused**
   - One architectural concern per test file
   - Group related rules together
   - Use descriptive test names

2. **Use Appropriate Tools**
   - Mago Guard for namespace dependencies
   - Pest Arch for comprehensive validation
   - Choose based on use case

3. **Document Ignoring Rules**
   - Clearly document why classes are ignored
   - Review ignored classes periodically
   - Minimize ignoring rules

4. **Maintain Test Suite**
   - Update tests when architecture changes
   - Review test coverage regularly
   - Remove obsolete tests

### Maintaining Architecture

1. **Regular Validation**
   - Run Mago Guard frequently
   - Fix violations immediately
   - Document architectural decisions

2. **Code Reviews**
   - Check architecture compliance
   - Review dependency changes
   - Validate new components

3. **Documentation**
   - Document architectural decisions
   - Update this guide when needed
   - Share knowledge with team

---

## Future Enhancements

### High Priority

1. **Fix Pest Arch Performance** 🔴
   - **Status:** Critical issue - all tests timing out
   - **Actions:**
     - Investigate timeout root causes
     - Implement caching mechanism for analysis results
     - Optimize Pest Arch plugin analysis algorithm
     - Consider alternative analysis approaches
   - **Target:** Reduce execution time to <60 seconds per test file
   - **Impact:** Enables regular use of comprehensive Pest Arch tests
   - **Estimated Effort:** High (requires deep investigation)

2. **Enhanced Mago Guard Rules** 🟡
   - **Status:** Partial coverage
   - **Actions:**
     - Monitor Mago Guard updates for negative dependency support
     - Add naming convention rules if supported
     - Expand structural rule coverage
     - Add custom rule support if available
   - **Target:** 100% rule coverage where supported
   - **Impact:** Reduces need for Pest Arch tests
   - **Estimated Effort:** Medium (depends on tool capabilities)

3. **CI/CD Optimization** 🟡
   - **Status:** Basic implementation exists
   - **Actions:**
     - Configure parallel test execution for split files
     - Implement test result caching
     - Add performance monitoring and alerting
     - Optimize CI/CD pipeline for architecture tests
   - **Target:** <5 minutes total execution time
   - **Impact:** Faster feedback in CI/CD
   - **Estimated Effort:** Medium

### Medium Priority

4. **Incremental Analysis** 🟢
   - **Status:** Not implemented
   - **Actions:**
     - Implement git-based change detection
     - Only analyze changed files between runs
     - Cache dependency graphs
     - Use file modification timestamps
   - **Target:** Analyze only changed files
   - **Impact:** Significant performance improvement
   - **Estimated Effort:** High (requires custom implementation)

5. **Reporting Enhancements** 🟢
   - **Status:** Basic markdown reports exist
   - **Actions:**
     - Generate HTML reports with visualizations
     - Track violations over time (trend analysis)
     - Visualize dependency graphs
     - Create dashboard for architecture health
   - **Target:** Rich, visual reporting
   - **Impact:** Better insights and tracking
   - **Estimated Effort:** Medium

6. **Developer Experience** 🟢
   - **Status:** Basic tooling exists
   - **Actions:**
     - IDE integration (VS Code, PhpStorm)
     - Real-time validation as you type
     - Better error messages with suggestions
     - Quick-fix capabilities
   - **Target:** Seamless developer experience
   - **Impact:** Faster development cycles
   - **Estimated Effort:** High (requires IDE plugin development)

### Low Priority

7. **Documentation** 🔵
   - **Status:** Comprehensive guide exists
   - **Actions:**
     - Create video tutorials
     - Document architecture decision records (ADRs)
     - Develop team training materials
     - Create interactive examples
   - **Target:** Complete documentation suite
   - **Impact:** Better team onboarding
   - **Estimated Effort:** Low-Medium

8. **Advanced Tooling** 🔵
   - **Status:** Standard tools in use
   - **Actions:**
     - Develop custom Pest Arch assertions
     - Create Mago Guard custom rules
     - Integrate with other static analysis tools
     - Build custom architecture validation tools
   - **Target:** Enhanced tooling ecosystem
   - **Impact:** More powerful validation
   - **Estimated Effort:** High (custom development)

### Implementation Roadmap

**Q1 2026:**
- Fix Pest Arch performance issues (High Priority)
- CI/CD optimization (High Priority)
- Incremental analysis proof of concept (Medium Priority)

**Q2 2026:**
- Enhanced Mago Guard rules (High Priority)
- Reporting enhancements (Medium Priority)
- Developer experience improvements (Medium Priority)

**Q3 2026:**
- Advanced tooling (Low Priority)
- Documentation enhancements (Low Priority)
- Performance monitoring and optimization

### Success Metrics

- **Pest Arch Execution Time:** <60 seconds per test file
- **Mago Guard Coverage:** 100% of supported rules
- **CI/CD Execution Time:** <5 minutes total
- **Developer Satisfaction:** Positive feedback on tooling
- **Architecture Violations:** Decreasing trend over time

---

## Troubleshooting

### Common Issues

#### Pest Arch Tests Timeout

**Symptoms:**
- Tests exit with code 255
- No output before timeout
- Even simple tests timeout

**Solutions:**
1. Increase timeout in `composer.json`
2. Run tests individually
3. Use Mago Guard as alternative
4. Check codebase size and complexity

#### Mago Guard Reports Issues

**Symptoms:**
- Errors reported in specific namespaces
- Structural rule violations

**Solutions:**
1. Review error messages carefully
2. Fix violations in code
3. Update ignoring rules if needed
4. Check `mago.toml` configuration

#### Test Failures After Code Changes

**Symptoms:**
- Previously passing tests now fail
- New dependency violations

**Solutions:**
1. Review architectural decision
2. Update test rules if architecture changed
3. Add ignoring rules if exception needed
4. Document architectural decision

### Getting Help

1. **Check Documentation**
   - Review this guide
   - Check tool documentation
   - Review test file comments

2. **Review Test Reports**
   - Check `docs/architecture-tests/reports/`
   - Review execution logs
   - Compare with previous runs

3. **Team Discussion**
   - Discuss with team
   - Review architectural decisions
   - Update documentation

---

## Appendix

### Test File Manifest

| File | Tests | Category | Tool |
|------|-------|----------|------|
| `HttpLayerDependenciesTest.php` | 2 | Namespace Dependencies | Both |
| `DomainLayerDependenciesTest.php` | 3 | Namespace Dependencies | Both |
| `ApplicationLayerDependenciesTest.php` | 3 | Namespace Dependencies | Both |
| `StateManagementDependenciesTest.php` | 1 | Namespace Dependencies | Both |
| `UiLayerDependenciesTest.php` | 2 | Namespace Dependencies | Both |
| `AuthorizationEventDependenciesTest.php` | 4 | Namespace Dependencies | Both |
| `CoreApplicationDependenciesTest.php` | 4 | Namespace Dependencies | Both |
| `NegativeDependenciesTest.php` | 5 | Negative Dependencies | Pest only |
| `ClassStructureTest.php` | 6 | Class Structure | Both |
| `NamingConventionsTest.php` | 7 | Naming Conventions | Pest only |
| `TestStructureTest.php` | 2 | Test Structure | Pest only |

### Command Reference

```bash
# Mago Guard
composer mago:guard

# Pest Architecture Tests
composer test:arch:pest
pest tests/Arch/[TestFile].php

# Both tools
composer test:arch
```

### Configuration Files

- `mago.toml` - Mago Guard configuration
- `tests/Arch/*.php` - Pest architecture test files
- `composer.json` - Test command definitions

---

**Document Version:** 2.0  
**Last Updated:** 2025-12-31  
**Maintainer:** Development Team
