# Architecture Test Coverage Analysis

> **Note:** This document has been consolidated into the [Architecture Testing Guide](ARCHITECTURE_TESTING_GUIDE.md).  
> This file is kept for historical reference. Please refer to the comprehensive guide for the latest information.

## Pest Architecture Tests vs Mago Guard Rules

This document compares the Pest architecture tests with Mago Guard rules to ensure comprehensive coverage.

### Coverage Summary

| Category | Pest Tests | Mago Guard | Status |
|----------|-----------|------------|--------|
| **Namespace Dependencies (toOnlyUse)** | 18 tests | 20 perimeter rules | ✅ **Covered** |
| **Negative Dependencies (not->toUse)** | 5 tests | 0 rules | ❌ **Missing** |
| **Class Structure (toBeFinal, toExtend, toImplement)** | 5 tests | 0 rules | ❌ **Missing** |
| **Naming Conventions (toHaveSuffix)** | 7 tests | 0 rules | ❌ **Missing** |
| **Test Structure Rules** | 2 tests | 0 rules | ⚠️ **Not applicable** |
| **Specific Constraints** | 1 test | 0 rules | ⚠️ **Partial** |

### Detailed Comparison

#### 1. Namespace Dependency Rules (toOnlyUse)

**Pest Tests (18):**
1. Controllers → Models, Requests, Resources, Actions, framework
2. Form Requests → Enums, Models, framework
3. Models → Enums, Models, Support, States, Observers, Presenters, framework, third-party
4. Model Builders → Models, Enums, framework
5. Model Concerns → Enums, Models, Services, Support, framework
6. Actions → Enums, Models, Requests, Services, Support, Actions, framework
7. Services → Actions, Enums, Models, Services, Support, framework
8. Support Validation → Enums, Models, Services, framework
9. States → App, Spatie, framework
10. Livewire → Enums, Models, Requests, Services, Actions, Livewire, Filament, framework
11. Filament → Enums, Models, Requests, Services, Actions, Filament, framework
12. Policies → Models, framework
13. Observers → Models, Services, framework
14. Listeners → Models, framework
15. Presenters → Models, framework
16. Enums → framework only
17. Console Commands → Models, Services, Actions, Symfony, framework
18. Exceptions → Illuminate, Laravel, Exception
19. Providers → App, framework, third-party packages

**Mago Guard (20 perimeter rules):** ✅ **Fully Covered**
- All namespace dependencies are covered by `guard.perimeter.rules`

#### 2. Negative Dependency Rules (not->toUse)

**Pest Tests (5):**
1. Controllers should not use facades directly
2. Services should not use facades (with exceptions)
3. Models should not depend on HTTP layer
4. Enums should not have domain dependencies
5. Console Commands should not depend on HTTP layer

**Mago Guard:** ❌ **Not Covered**
- Mago Guard perimeter rules only enforce positive permissions
- Negative rules (what NOT to use) are not directly supported
- **Note:** These could potentially be enforced by omitting namespaces from `permit` arrays, but explicit negative rules would be clearer

#### 3. Class Structure Rules

**Pest Tests (5):**
1. All controllers should be instantiable classes (toBeClasses)
2. All models should extend Eloquent Model (toExtend)
3. Services should be final (toBeFinal)
4. Actions should be final (toBeFinal)
5. Enums should implement BackedEnum (toImplement)
6. Policies should be final classes (toBeFinal)

**Mago Guard:** ❌ **Not Covered (but supported!)**
- Mago Guard supports `guard.structural.rules` with:
  - `must-be-final = true`
  - `must-extend = "ClassName"`
  - `must-implement = "InterfaceName"`
- These rules need to be added to `mago.toml`

#### 4. Naming Convention Rules

**Pest Tests (7):**
1. Controllers should end with Controller suffix
2. Policies should end with Policy suffix
3. Observers should end with Observer suffix
4. Listeners should end with Listener suffix
5. Presenters should end with Presenter suffix
6. Exceptions should end with Exception suffix
7. Commands should end with Command suffix

**Mago Guard:** ❌ **Not Covered**
- Mago Guard does not appear to support naming convention rules
- These are best enforced by Pest architecture tests

#### 5. Test Structure Rules

**Pest Tests (2):**
1. Unit tests should extend TestCase
2. Feature tests should extend TestCase

**Mago Guard:** ⚠️ **Not Applicable**
- Tests are excluded from Mago Guard analysis (`excludes = ["tests"]`)
- These rules are specific to test files and should remain in Pest

#### 6. Specific Architectural Constraints

**Pest Tests (1):**
1. DB facade usage should be limited to Actions and Services

**Mago Guard:** ⚠️ **Partial Coverage**
- Could be enforced by perimeter rules, but explicit negative rule would be clearer
- Not directly supported

## Recommendations

### High Priority: Add Structural Rules

Mago Guard supports structural rules but they're not currently configured. Add:

1. **Services must be final**
2. **Actions must be final**
3. **Policies must be final**
4. **Models must extend Eloquent Model**
5. **Enums must implement BackedEnum**

### Medium Priority: Document Limitations

1. **Negative dependency rules** - Mago Guard doesn't directly support "not->toUse" rules
   - These should remain in Pest architecture tests
   - Perimeter rules can partially enforce by omission, but explicit negative rules are clearer

2. **Naming conventions** - Mago Guard doesn't support suffix rules
   - These should remain in Pest architecture tests

### Low Priority: Test Structure

- Test structure rules are correctly excluded from Mago Guard
- These should remain in Pest architecture tests

## Conclusion

**Current Coverage:**
- ✅ Namespace dependencies: **100% covered** by Mago Guard
- ❌ Class structure rules: **0% covered** (but can be added!)
- ❌ Negative dependencies: **0% covered** (not supported by Mago Guard)
- ❌ Naming conventions: **0% covered** (not supported by Mago Guard)

**Action Items:**
1. Add structural rules to `mago.toml` for final classes, extends, and implements
2. Keep negative dependency rules and naming conventions in Pest (Mago Guard limitation)
3. Document that Pest and Mago Guard complement each other
