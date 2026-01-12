# Complexity Analysis & Dependency Reduction Report

**Generated:** 2025-12-31 07:11:53  
**Analysis Tool:** Manual Codebase Review + Static Analysis  
**Codebase:** 110 PHP files in `app/` directory

---

## Executive Summary

This report analyzes the codebase for complexity reduction opportunities and class dependency optimization. The analysis identifies:

- **High Dependency Classes:** 8 classes with 15+ import statements
- **Large Files:** 5 files exceeding 150 lines
- **Complex Methods:** Areas with potential for extraction/refactoring
- **Dependency Reduction Opportunities:** 12 specific reduction targets

### Key Metrics

| Metric | Value |
|--------|-------|
| Total PHP Files | 110 |
| Average Imports per File | 5.3 |
| Files with 10+ Imports | 12 |
| Files with 15+ Imports | 8 |
| Largest File (lines) | User.php (207) |
| Files > 150 lines | 5 |

---

## 1. High Dependency Classes

### Critical Priority (>20 dependencies)

#### 1.1 TenancyServiceProvider (49 imports)
**Location:** `app/Providers/TenancyServiceProvider.php`  
**Lines:** 177  
**Dependencies:** 49

**Analysis:**
- 40+ imports from `Stancl\Tenancy` package (vendor dependencies)
- Most are event classes used for event listener registration
- This is **acceptable complexity** - vendor package requires many event imports
- However, the `events()` method could be split

**Recommendation:**
- ✅ **Keep as-is** - Vendor package pattern, unavoidable
- Consider extracting event mapping to separate method/class if `events()` grows
- Current structure is maintainable

#### 1.2 Team Model (21 imports)
**Location:** `app/Models/Team.php`  
**Lines:** 184  
**Dependencies:** 21

**Breakdown:**
- 6 traits used (HasChildren, HasFactory, HasStates, HasTeamHierarchy, HasTranslatableAttributes, HasTranslatableSlug, HasUlid, ManagesTeamRoles, SoftDeletes)
- 15 class/interface imports
- Model has many concerns mixed together

**Opportunities:**
1. **Extract `getBioHtmlAttribute()` to Value Object or Accessor Class**
   - Currently mixes markdown rendering, HTML sanitization, error handling
   - Create `TeamBioHtmlAccessor` class
   - Reduces cognitive complexity of model

2. **Consider splitting traits**
   - Model uses 9 traits - consider grouping related traits
   - Most are well-organized concerns, but could be reviewed

**Impact:** Medium - Model is central to application but has high cognitive load

#### 1.3 User Model (20 imports)
**Location:** `app/Models/User.php`  
**Lines:** 207  
**Dependencies:** 20

**Breakdown:**
- 6 traits used
- 14 class/interface imports
- Contains complex `isProtectable()` method with raw query building

**Opportunities:**
1. **Extract `isProtectable()` to Service**
   - Complex query logic with joins and pivots (lines 85-113)
   - Create `UserProtectionService::isProtectable(User $user): bool`
   - Improves testability and reduces model complexity

2. **Extract relationship definitions**
   - Multiple complex relationships could be in a separate trait
   - But relationships are core model functionality, so this is optional

**Impact:** High - Central model with complex business logic

### Medium Priority (15-20 dependencies)

#### 1.4 TenantPanelProvider (20 imports)
**Location:** `app/Providers/Filament/TenantPanelProvider.php`  
**Lines:** 71  
**Dependencies:** 20

**Analysis:**
- Filament panel provider - many imports required for configuration
- Well-structured, appropriate for Filament pattern
- No reduction opportunities without breaking Filament conventions

**Recommendation:** ✅ Keep as-is

#### 1.5 AdminPanelProvider (17 imports)
**Location:** `app/Providers/Filament/AdminPanelProvider.php`  
**Dependencies:** 17

**Analysis:**
- Similar to TenantPanelProvider
- Appropriate Filament structure

**Recommendation:** ✅ Keep as-is

#### 1.6 FortifyServiceProvider (15 imports)
**Location:** `app/Providers/FortifyServiceProvider.php`  
**Lines:** 97  
**Dependencies:** 15

**Analysis:**
- Fortify service provider with feature configuration
- Appropriate number of dependencies for service provider
- Well-organized

**Recommendation:** ✅ Keep as-is

#### 1.7 TeamResource (15 imports)
**Location:** `app/Filament/Tenant/Resources/Teams/TeamResource.php`  
**Lines:** 81  
**Dependencies:** 15

**Analysis:**
- Filament resource - standard dependencies
- Delegates form/table configuration to separate classes (good practice)
- No reduction opportunities

**Recommendation:** ✅ Keep as-is

---

## 2. Complexity Reduction Opportunities

### 2.1 User Model - `isProtectable()` Method

**Location:** `app/Models/User.php:85-113`  
**Complexity:** High (28 lines, complex query logic)

**Current Implementation:**
- Raw database queries with joins
- Nested closures
- Complex business logic in model

**Refactoring:**
```php
// Create: app/Services/UserProtectionService.php
final readonly class UserProtectionService
{
    public function isProtectable(User $user): bool
    {
        $userKeyRoles = $this->getUserKeyRoles($user);
        return $userKeyRoles->contains(fn ($row) => 
            $this->isUniqueRoleAssignment($user, $row)
        );
    }
    
    private function getUserKeyRoles(User $user): Collection
    {
        // Extract query logic
    }
    
    private function isUniqueRoleAssignment(User $user, object $role): bool
    {
        // Extract count logic
    }
}
```

**Benefits:**
- ✅ Improved testability (service can be mocked)
- ✅ Reduced model complexity
- ✅ Single Responsibility Principle
- ✅ Better error handling and logging opportunities

**Estimated Effort:** Low (2-3 hours)  
**Priority:** High

### 2.2 Team Model - `getBioHtmlAttribute()` Accessor

**Location:** `app/Models/Team.php:159-183`  
**Complexity:** Medium (24 lines, multiple responsibilities)

**Current Implementation:**
- Markdown rendering
- HTML sanitization (Purify)
- Error handling for mutation testing
- Multiple concerns mixed together

**Refactoring:**
```php
// Create: app/Support/Html/TeamBioRenderer.php
final readonly class TeamBioRenderer
{
    public function __construct(
        private MarkdownRenderer $markdownRenderer,
        private Purify $purify,
    ) {}
    
    public function render(?string $bio, ?string $locale): ?string
    {
        if (empty($bio)) {
            return null;
        }
        
        try {
            $html = $this->markdownRenderer->toHtml($bio);
            return $this->purify->clean($html);
        } catch (BindingResolutionException $e) {
            // Handle gracefully
            return null;
        }
    }
}
```

**Benefits:**
- ✅ Single Responsibility Principle
- ✅ Improved testability
- ✅ Reusable for other models
- ✅ Cleaner model code

**Estimated Effort:** Low (1-2 hours)  
**Priority:** Medium

### 2.3 BulkTeamController - `processTeam()` Method

**Location:** `app/Http/Controllers/Teams/BulkTeamController.php:100-126`  
**Complexity:** Medium (26 lines, conditional logic)

**Current Implementation:**
- Handles both create and update in single method
- Could benefit from command pattern or separate handlers

**Refactoring:**
```php
// Extract to separate handler classes
final readonly class BulkTeamCreateHandler
{
    public function handle(array $teamData): array { /* ... */ }
}

final readonly class BulkTeamUpdateHandler
{
    public function handle(array $teamData): array { /* ... */ }
}
```

**Benefits:**
- ✅ Clearer separation of concerns
- ✅ Easier to test create vs update independently
- ✅ Follows Open/Closed Principle

**Estimated Effort:** Medium (3-4 hours)  
**Priority:** Low

### 2.4 ManagesTeamRoles Trait - `checkRoleConflict()` Method

**Location:** `app/Models/Concerns/ManagesTeamRoles.php:140-167`  
**Complexity:** Medium (27 lines, nested conditionals)

**Current Implementation:**
- Asymmetric logic for executive vs deputy
- Could be simplified with unified approach

**Refactoring:**
```php
private function checkRoleConflict(User $user, string $role): void
{
    $conflictingRole = $role === 'executive' ? 'deputy' : 'executive';
    
    if ($this->userHasRole($user, $conflictingRole)) {
        throw ValidationException::withMessages([
            $role => ["A user cannot be both executive and deputy of the same team."],
        ]);
    }
}

private function userHasRole(User $user, string $role): bool
{
    try {
        return User::query()->role($role)->where('id', $user->id)->exists();
    } catch (RoleDoesNotExist) {
        return false;
    }
}
```

**Benefits:**
- ✅ Eliminates code duplication
- ✅ Simpler, more maintainable logic
- ✅ Better error messages

**Estimated Effort:** Low (1 hour)  
**Priority:** Medium

### 2.5 TeamHierarchyTraversalService - Recursive Methods

**Location:** `app/Services/TeamHierarchyTraversalService.php`  
**Complexity:** Medium (100 lines)

**Current Implementation:**
- Uses DB queries in recursive calls (potential N+1)
- Could benefit from caching or batch loading

**Recommendation:**
- ✅ Current implementation is functional and clear
- Consider adding caching layer if performance becomes issue
- Current approach is appropriate for hierarchy depth

**Estimated Effort:** N/A (no changes needed)  
**Priority:** N/A

---

## 3. Dependency Reduction Opportunities

### 3.1 Team Model - Reduce Trait Dependencies

**Current:** 9 traits  
**Opportunity:** Review if all traits are necessary, group related functionality

**Action Items:**
- Review trait usage patterns
- Consider if any traits can be combined
- Most traits are well-scoped concerns, so changes minimal

**Estimated Impact:** Low  
**Priority:** Low

### 3.2 User Model - Extract Service Dependencies

**Current:** 20 imports  
**Opportunity:** Extract `isProtectable()` to service (reduces direct query builder usage)

**Action Items:**
- Extract `isProtectable()` to `UserProtectionService`
- Model will depend on service instead of raw DB query building
- Reduces model's direct dependency on query builder patterns

**Estimated Impact:** Medium  
**Priority:** High

### 3.3 Controllers - Use Dependency Injection More

**Current Analysis:**
- `BulkTeamController` - Good DI usage ✅
- `MoveTeam` (Livewire) - Uses `resolve()` instead of constructor injection

**Opportunities:**
```php
// Current (Livewire/Teams/MoveTeam.php:46)
$service = resolve(TeamMoveRequestService::class);

// Better
final class MoveTeam extends Component
{
    public function __construct(
        private TeamMoveRequestService $requestService,
    ) {}
}
```

**Note:** Livewire components have different lifecycle - verify if constructor injection works  
**Estimated Impact:** Low  
**Priority:** Low

### 3.4 Services - Reduce Facade Usage

**Analysis:**
- `TeamMoveRequestService` uses `DB::transaction()` facade
- Could inject transaction manager (but DB facade is Laravel convention)

**Recommendation:**
- ✅ Keep DB facade usage - it's Laravel convention for transactions
- No reduction needed

### 3.5 Filament Resources - Dependency Count

**Analysis:**
- Filament resources (TeamResource, UserResource) have 12-16 dependencies
- This is **normal and expected** for Filament resources
- Resources properly delegate to Schema/Table classes

**Recommendation:**
- ✅ Current structure is optimal
- No reduction opportunities without breaking Filament patterns

### 3.6 Providers - Event Mapping

**Opportunity:**
- `TenancyServiceProvider::events()` returns large array
- Could extract to `TenancyEventMapping` class
- But this adds abstraction without clear benefit

**Recommendation:**
- ✅ Keep current structure
- Array mapping is clear and maintainable

---

## 4. Large Files (>150 lines)

### 4.1 User Model (207 lines)

**Opportunities:**
1. Extract `isProtectable()` to service (reduces ~28 lines)
2. Extract relationship documentation to separate concern (optional)

**Estimated Reduction:** 28-40 lines  
**Priority:** High

### 4.2 Team Model (184 lines)

**Opportunities:**
1. Extract `getBioHtmlAttribute()` to renderer (reduces ~24 lines)
2. Review trait organization (no line reduction, but clarity improvement)

**Estimated Reduction:** 24-30 lines  
**Priority:** Medium

### 4.3 TenancyServiceProvider (177 lines)

**Analysis:**
- Large event mapping array (67-126) is appropriate
- Well-structured, no reduction needed

**Recommendation:** ✅ Keep as-is

### 4.4 ManagesTeamRoles Trait (168 lines)

**Opportunities:**
1. Simplify `checkRoleConflict()` (reduces complexity, minimal line reduction)
2. Extract role conflict logic to separate concern

**Estimated Reduction:** 10-15 lines  
**Priority:** Medium

### 4.5 BulkTeamController (143 lines)

**Opportunities:**
1. Extract create/update handlers (adds files but reduces controller size)
2. Extract error handling to separate concern

**Estimated Reduction:** 30-40 lines  
**Priority:** Low

---

## 5. Dependency Reduction Strategy

### Strategy 1: Service Extraction

**Target:** Business logic in models  
**Examples:**
- `User::isProtectable()` → `UserProtectionService`
- `Team::getBioHtmlAttribute()` → `TeamBioRenderer`

**Benefits:**
- Reduces model dependencies on query builders
- Improves testability
- Follows Single Responsibility Principle

### Strategy 2: Interface Segregation

**Current:** Services directly depend on concrete classes  
**Opportunity:** Introduce interfaces where appropriate

**Examples:**
- `TeamMoveRequestService` depends on concrete `MoveTeam` action
- Could introduce `TeamMoveActionInterface` if multiple implementations needed
- **But:** YAGNI principle applies - only if multiple implementations emerge

**Recommendation:** ✅ Wait for need before abstracting

### Strategy 3: Value Objects

**Current:** Primitive obsession in some areas  
**Opportunity:** Create value objects for complex data

**Examples:**
- Team hierarchy paths
- Approval request data
- Bulk operation results

**Estimated Impact:** Medium  
**Priority:** Low (premature optimization risk)

---

## 6. Recommendations by Priority

### High Priority (Implement Soon)

1. **Extract `User::isProtectable()` to Service**
   - Reduces model complexity
   - Improves testability
   - Clear business logic separation
   - **Effort:** Low (2-3 hours)

2. **Extract `Team::getBioHtmlAttribute()` to Renderer**
   - Single Responsibility Principle
   - Reusable component
   - **Effort:** Low (1-2 hours)

### Medium Priority (Consider for Next Sprint)

3. **Simplify `ManagesTeamRoles::checkRoleConflict()`**
   - Eliminates duplication
   - Clearer logic flow
   - **Effort:** Low (1 hour)

4. **Review Team Model Traits**
   - Ensure optimal trait organization
   - Document trait dependencies
   - **Effort:** Low (1-2 hours)

### Low Priority (Future Consideration)

5. **Extract BulkTeamController Handlers**
   - Only if controller grows further
   - Current size is acceptable
   - **Effort:** Medium (3-4 hours)

6. **Introduce Value Objects**
   - Only when patterns emerge
   - Avoid premature abstraction
   - **Effort:** Medium-High (varies)

---

## 7. Dependency Graph Analysis

### Most Connected Classes

1. **Team Model**
   - Used by: 25+ classes
   - Central to application domain
   - Appropriate coupling level

2. **User Model**
   - Used by: 20+ classes
   - Central to authentication/authorization
   - Appropriate coupling level

3. **TeamMoveRequestService**
   - Depends on: 3 services
   - Good separation of concerns
   - ✅ Well-designed

### Dependency Patterns

**Good Patterns:**
- ✅ Services depend on interfaces/contracts where appropriate
- ✅ Controllers use Actions (good separation)
- ✅ Validation extracted to separate classes
- ✅ Trait usage is appropriate and well-scoped

**Areas for Improvement:**
- ⚠️ Some Livewire components use `resolve()` instead of DI
- ⚠️ Model methods with complex query logic (can be extracted)

---

## 8. Complexity Metrics Summary

### Cyclomatic Complexity (Estimated)

| File | Estimated Complexity | Status |
|------|---------------------|--------|
| `User::isProtectable()` | ~8 | ⚠️ High |
| `Team::getBioHtmlAttribute()` | ~4 | ✅ Acceptable |
| `ManagesTeamRoles::checkRoleConflict()` | ~6 | ⚠️ Medium-High |
| `BulkTeamController::store()` | ~5 | ✅ Acceptable |
| `TeamMoveRequestService::requestMove()` | ~4 | ✅ Acceptable |

### Dependency Count by Category

| Category | Avg Dependencies | Max Dependencies | Notes |
|----------|------------------|------------------|-------|
| Models | 12.5 | 21 | Appropriate |
| Services | 5.2 | 8 | Good |
| Controllers | 8.0 | 9 | Good |
| Providers | 25.0 | 49 | Expected (vendor events) |
| Filament Resources | 14.0 | 16 | Expected (Filament pattern) |

---

## 9. Comparison with v1 Report

### Improvements Since v1

✅ **Completed:**
- HasTeamHierarchy trait complexity reduced via validator extraction
- TeamNameValidator complexity reduced via strategy pattern
- TeamMoveService deprecated in favor of focused services
- Collection-based patterns implemented for functional approach

### Remaining Opportunities

⚠️ **From v1 that still apply:**
- User model complexity (still 20 dependencies, 207 lines)
- Team model complexity (still 21 dependencies, 184 lines)
- These are now top priorities for v2

### New Findings in v2

- BulkTeamController complexity (manageable but could be improved)
- ManagesTeamRoles trait can be simplified
- Livewire component DI patterns

---

## 10. Action Plan

### Phase 1: High Priority (Week 1)

1. ✅ Extract `User::isProtectable()` to `UserProtectionService`
2. ✅ Extract `Team::getBioHtmlAttribute()` to `TeamBioRenderer`
3. ✅ Write tests for extracted services
4. ✅ Update model methods to use services

**Expected Outcome:**
- Reduced model complexity
- Improved testability
- Better separation of concerns

### Phase 2: Medium Priority (Week 2)

1. ✅ Simplify `ManagesTeamRoles::checkRoleConflict()`
2. ✅ Review and document trait dependencies
3. ✅ Consider Livewire DI improvements (if feasible)

**Expected Outcome:**
- Cleaner code
- Reduced cognitive load
- Better maintainability

### Phase 3: Monitoring (Ongoing)

1. Monitor file sizes as codebase grows
2. Watch for new complexity patterns
3. Refactor proactively when thresholds approached

---

## 11. Complexity Thresholds

### Recommended Thresholds

| Metric | Warning | Critical |
|--------|---------|----------|
| Lines per File | 200 | 300 |
| Dependencies per Class | 15 | 25 |
| Cyclomatic Complexity | 10 | 15 |
| Methods per Class | 12 | 20 |

### Current Status

| File | Lines | Dependencies | Status |
|------|-------|--------------|--------|
| User.php | 207 | 20 | ⚠️ Warning (lines) |
| Team.php | 184 | 21 | ⚠️ Warning (dependencies) |
| TenancyServiceProvider.php | 177 | 49 | ⚠️ Warning (dependencies, but acceptable) |
| ManagesTeamRoles.php | 168 | 5 | ✅ OK |
| BulkTeamController.php | 143 | 9 | ✅ OK |

---

## 12. Conclusion

The codebase demonstrates **good architectural practices** overall:

✅ **Strengths:**
- Services are well-separated
- Actions pattern used consistently
- Validation extracted to dedicated classes
- Trait usage is appropriate
- Collection-based functional patterns implemented

⚠️ **Opportunities:**
- Extract complex model methods to services (2 high-priority items)
- Simplify some trait methods
- Monitor file growth

**Overall Assessment:** The codebase is well-structured with clear opportunities for incremental improvement. The high-priority recommendations can be implemented quickly with significant benefits.

---

## Appendix: File Dependency Counts (Top 20)

| File | Dependencies | Lines | Priority |
|------|--------------|-------|----------|
| `TenancyServiceProvider.php` | 49 | 177 | ⚠️ Review |
| `Team.php` | 21 | 184 | ⚠️ High |
| `User.php` | 20 | 207 | ⚠️ High |
| `TenantPanelProvider.php` | 20 | 71 | ✅ OK |
| `AdminPanelProvider.php` | 17 | 63 | ✅ OK |
| `FortifyServiceProvider.php` | 15 | 97 | ✅ OK |
| `TeamResource.php` | 15 | 81 | ✅ OK |
| `AppServiceProvider.php` | 10 | 29 | ✅ OK |
| `MoveTeam.php` (Livewire) | 9 | 93 | ⚠️ Review DI |
| `EditTeam.php` | 9 | 74 | ✅ OK |
| `BulkTeamController.php` | 9 | 143 | ✅ OK |
| `HasTeamHierarchy.php` | 8 | 113 | ✅ OK |
| `TeamNameValidator.php` | 7 | 68 | ✅ OK |
| `TeamMoveService.php` | 6 | 77 | ⚠️ Deprecated |
| `UserResource.php` | 12 | 68 | ✅ OK |

---

**Report Generated:** 2025-12-31 07:11:53  
**Next Review:** After implementing high-priority recommendations
