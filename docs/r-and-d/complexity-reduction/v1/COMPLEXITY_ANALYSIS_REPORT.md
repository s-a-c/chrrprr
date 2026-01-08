# Cyclomatic Complexity Analysis & Remediation Report

**Generated:** 2025-12-29  
**Analysis Tool:** Mago/phpmetrics architecture testing  
**Thresholds:** Complexity > 15, Methods > 10, Kan Defect > 1.6

---

## Executive Summary

This report analyzes 4 high-complexity code units identified by architecture testing:

1. **HasTeamHierarchy** trait - Complexity: 19, Kan Defect: 2.04
2. **TeamNameValidator** class - Complexity: 22, Methods: 13, Kan Defect: 1.68
3. **TeamMoveService** class - Complexity: 21, Methods: 15
4. **User** model - Methods: 12

**Total Issues:** 8 errors (4 complexity, 3 method count, 1 Kan defect)

---

## 1. HasTeamHierarchy Trait

**Location:** `app/Models/Concerns/HasTeamHierarchy.php`  
**Current Metrics:**
- **Cyclomatic Complexity:** 19 (threshold: 15) ⚠️ **+27% over limit**
- **Kan Defect Score:** 2.04 (threshold: 1.6) ⚠️ **+28% over limit**
- **Lines of Code:** ~200
- **Methods:** 8

### Complexity Breakdown

The trait handles team hierarchy validation and tenant propagation. High complexity comes from:

1. **`validateHierarchy()`** - Main validation orchestrator (complexity ~8)
   - Multiple conditional branches for type checking
   - Early returns for Enterprise type
   - Parent resolution and validation chain

2. **`isDescendantOf()`** - Recursive traversal (complexity ~3)
   - While loop with conditional checks
   - Database queries in loop

3. **`getDepth()`** - Depth calculation (complexity ~2)
   - Similar while loop pattern

4. **`validateParentType()`** - Match expression with multiple cases (complexity ~4)
   - Type matching logic
   - Validation exception throwing

5. **`validateNoCycles()`** - Cycle detection (complexity ~4)
   - Multiple conditional checks
   - Recursive descendant checking

### Remediation Options

#### Option A: Extract Validation Strategy Pattern ⭐ **95% Recommended**

**Approach:** Split validation into separate strategy classes per validation concern.

**Implementation:**
```php
// app/Support/Validation/TeamHierarchy/HierarchyValidator.php
interface HierarchyValidatorInterface {
    public function validate(Team $team): void;
}

// app/Support/Validation/TeamHierarchy/EnterpriseParentValidator.php
final class EnterpriseParentValidator implements HierarchyValidatorInterface {
    public function validate(Team $team): void {
        if ($team->type === TeamType::ENTERPRISE && $team->parent_id !== null) {
            throw ValidationException::withMessages([
                'parent_id' => ['Enterprises cannot have a parent team.'],
            ]);
        }
    }
}

// app/Support/Validation/TeamHierarchy/ParentTypeValidator.php
final class ParentTypeValidator implements HierarchyValidatorInterface {
    public function validate(Team $team, Team $parent): void {
        $validParentType = match ($team->type) {
            TeamType::ORGANISATION => TeamType::ENTERPRISE,
            TeamType::DIVISION => TeamType::ORGANISATION,
            TeamType::DEPARTMENT => TeamType::DIVISION,
            TeamType::PROJECT => TeamType::DEPARTMENT,
            default => null,
        };
        
        if ($validParentType && $parent->type !== $validParentType) {
            throw ValidationException::withMessages([
                'parent_id' => ["{$team->type->value} must belong to a {$validParentType->value}."],
            ]);
        }
    }
}

// app/Support/Validation/TeamHierarchy/CycleValidator.php
final class CycleValidator implements HierarchyValidatorInterface {
    public function validate(Team $team, Team $parent): void {
        if ($team->id && (int) $team->parent_id === (int) $team->id) {
            throw ValidationException::withMessages([
                'parent_id' => ['A team cannot be its own parent.'],
            ]);
        }
        
        if ($parent->isDescendantOf($team)) {
            throw ValidationException::withMessages([
                'parent_id' => ['A team cannot be moved into its own descendant.'],
            ]);
        }
    }
}

// app/Support/Validation/TeamHierarchy/DepthValidator.php
final class DepthValidator implements HierarchyValidatorInterface {
    public function validate(Team $parent): void {
        if ($parent->getDepth() >= 10) {
            throw ValidationException::withMessages([
                'parent_id' => ['Team hierarchy depth cannot exceed 10 levels.'],
            ]);
        }
    }
}
```

**Refactored `validateHierarchy()`:**
```php
public function validateHierarchy(): void
{
    $type = $this->normalizeType();
    if (! $type) {
        return;
    }

    $validators = $this->getValidators();
    foreach ($validators as $validator) {
        $validator->validate($this);
    }
}

private function getValidators(): array
{
    $validators = [new EnterpriseParentValidator()];
    
    if ($this->type === TeamType::ENTERPRISE) {
        return $validators;
    }
    
    $parent = $this->resolveParent();
    if (! $parent) {
        return $validators;
    }
    
    $validators[] = new ParentTypeValidator();
    $validators[] = new CycleValidator();
    $validators[] = new DepthValidator();
    
    return $validators;
}
```

**Benefits:**
- ✅ Reduces `validateHierarchy()` complexity from ~8 to ~3
- ✅ Each validator is single-responsibility (complexity ~2-3 each)
- ✅ Easy to test individual validators
- ✅ Follows Open/Closed Principle
- ✅ Overall trait complexity drops to ~12

**Estimated Complexity Reduction:** 19 → 12 (-37%)

---

#### Option B: Extract Traversal Logic ⭐ **85% Recommended**

**Approach:** Move `isDescendantOf()` and `getDepth()` to a separate service.

**Implementation:**
```php
// app/Services/TeamHierarchyTraversalService.php
final readonly class TeamHierarchyTraversalService
{
    public function isDescendantOf(Team $team, Team $ancestor): bool
    {
        $currentParentId = $team->parent_id;
        
        while ($currentParentId) {
            if ((int) $currentParentId === (int) $ancestor->id) {
                return true;
            }
            
            $currentParentId = DB::table('teams')
                ->where('id', $currentParentId)
                ->value('parent_id');
        }
        
        return false;
    }
    
    public function getDepth(Team $team): int
    {
        $depth = 1;
        $currentParentId = $team->parent_id;
        
        while ($currentParentId) {
            $depth++;
            $currentParentId = DB::table('teams')
                ->where('id', $currentParentId)
                ->value('parent_id');
        }
        
        return $depth;
    }
}
```

**Benefits:**
- ✅ Removes ~5 complexity points from trait
- ✅ Reusable across codebase
- ✅ Easier to optimize (e.g., caching, eager loading)
- ✅ Better testability

**Estimated Complexity Reduction:** 19 → 14 (-26%)

---

#### Option C: Combine Options A + B ⭐⭐⭐ **100% Recommended**

**Approach:** Apply both strategy pattern and traversal extraction.

**Benefits:**
- ✅ Maximum complexity reduction
- ✅ Best separation of concerns
- ✅ Most maintainable solution

**Estimated Complexity Reduction:** 19 → 10 (-47%)

**Effort:** Medium (4-6 hours)  
**Risk:** Low (well-isolated changes)

---

## 2. TeamNameValidator Class

**Location:** `app/Support/Validation/TeamNameValidator.php`  
**Current Metrics:**
- **Cyclomatic Complexity:** 22 (threshold: 15) ⚠️ **+47% over limit**
- **Kan Defect Score:** 1.68 (threshold: 1.6) ⚠️ **+5% over limit**
- **Methods:** 13 (threshold: 10) ⚠️ **+30% over limit**
- **Lines of Code:** ~220

### Complexity Breakdown

The validator handles complex name uniqueness checking across:
- Multiple team types (Enterprise, Organisation, Division, Department, Project)
- Translatable names (JSON structure with locale keys)
- String names (legacy format)
- Sibling scope validation

**High Complexity Methods:**

1. **`validateUnique()`** - Main entry point (complexity ~5)
   - Type resolution
   - Query building
   - Name normalization branching
   - Array vs string handling

2. **`normalizeNameForValidation()`** - Name processing (complexity ~4)
   - Multiple conditional branches
   - JSON decoding logic
   - Fallback handling

3. **`processStringName()`** - String processing (complexity ~3)
   - JSON decode attempt
   - Type checking

4. **`applyArrayNameConstraints()`** - Array query building (complexity ~3)
   - Loop with conditionals
   - Null/empty checks

5. **`applyStringValueConstraints()`** - String query building (complexity ~4)
   - Loop over locales
   - JSON contains check

6. **`getTeamType()`** - Type resolution (complexity ~4)
   - Multiple type checks
   - Model class mapping

### Remediation Options

#### Option A: Extract Name Normalization Service ⭐ **90% Recommended**

**Approach:** Separate name normalization from validation logic.

**Implementation:**
```php
// app/Services/TeamNameNormalizationService.php
final readonly class TeamNameNormalizationService
{
    /**
     * @return array<string, string>|string
     */
    public function normalize(Team $team): array|string
    {
        $nameRaw = $team->attributes['name'] ?? null;
        
        if ($this->isValidStringName($nameRaw)) {
            return $this->processStringName($nameRaw);
        }
        
        return $this->getTeamNameFallback($team);
    }
    
    private function isValidStringName(mixed $nameRaw): bool
    {
        return is_string($nameRaw) && $nameRaw !== '';
    }
    
    private function processStringName(string $nameRaw): array|string
    {
        $decoded = json_decode($nameRaw, true);
        
        return is_array($decoded) 
            ? $this->extractStringPairs($decoded)
            : $nameRaw;
    }
    
    private function extractStringPairs(array $decoded): array
    {
        $result = [];
        foreach ($decoded as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }
    
    private function getTeamNameFallback(Team $team): string
    {
        $name = $team->name;
        return is_string($name) ? $name : '';
    }
}
```

**Benefits:**
- ✅ Removes ~7 complexity points from validator
- ✅ Single responsibility for name processing
- ✅ Reusable for other name operations
- ✅ Easier to test normalization logic separately

**Estimated Complexity Reduction:** 22 → 15 (-32%)

---

#### Option B: Extract Query Builder Strategy ⭐ **85% Recommended**

**Approach:** Separate query building logic into strategy classes.

**Implementation:**
```php
// app/Support/Validation/TeamName/NameQueryBuilderInterface.php
interface NameQueryBuilderInterface {
    public function applyConstraints(Builder $query, mixed $name, Team $team): void;
}

// app/Support/Validation/TeamName/ArrayNameQueryBuilder.php
final class ArrayNameQueryBuilder implements NameQueryBuilderInterface {
    public function applyConstraints(Builder $query, array $name, Team $team): void {
        $query->where(static function (Builder $q) use ($name): void {
            foreach ($name as $locale => $value) {
                if ($value !== null && $value !== '') {
                    $q->orWhere("name->{$locale}", $value);
                }
            }
        });
    }
}

// app/Support/Validation/TeamName/StringNameQueryBuilder.php
final class StringNameQueryBuilder implements NameQueryBuilderInterface {
    public function applyConstraints(Builder $query, string $name, Team $team): void {
        if ($name === '') {
            return;
        }
        
        $query->where(static function (Builder $q) use ($name): void {
            foreach (['en', 'es', 'fr', 'de'] as $locale) {
                $q->orWhere("name->{$locale}", $name);
            }
            $q->orWhereJsonContains('name', $name);
        });
    }
}
```

**Refactored `validateUnique()`:**
```php
public function validateUnique(Team $team): void
{
    $type = $this->getTeamType($team);
    $query = $this->buildSiblingQuery($team, $type);
    $nameToCheck = $this->normalizationService->normalize($team);
    
    $builder = is_array($nameToCheck)
        ? new ArrayNameQueryBuilder()
        : new StringNameQueryBuilder();
    
    $builder->applyConstraints($query, $nameToCheck, $team);
    
    if ($query->exists()) {
        throw ValidationException::withMessages([
            'name' => ['The team name has already been taken within this scope.'],
        ]);
    }
}
```

**Benefits:**
- ✅ Removes ~7 complexity points
- ✅ Clear separation: normalization vs query building
- ✅ Easy to add new name formats
- ✅ Better testability

**Estimated Complexity Reduction:** 22 → 15 (-32%)

---

#### Option C: Extract Type Resolution Service ⭐ **75% Recommended**

**Approach:** Move type resolution to dedicated service.

**Implementation:**
```php
// app/Services/TeamTypeResolutionService.php
final readonly class TeamTypeResolutionService
{
    public function resolve(Team $team): ?string
    {
        $type = $team->type ?? $team->getAttribute('type');
        
        if ($type instanceof TeamType) {
            return $type->value;
        }
        
        if (is_string($type)) {
            return $type;
        }
        
        return $this->getTypeFromModel($team);
    }
    
    private function getTypeFromModel(Team $team): ?string
    {
        $typeMap = [
            Enterprise::class => 'enterprise',
            Organisation::class => 'organisation',
            Division::class => 'division',
            Department::class => 'department',
            Project::class => 'project',
        ];
        
        return $typeMap[$team::class] ?? null;
    }
}
```

**Benefits:**
- ✅ Removes ~4 complexity points
- ✅ Reusable across codebase
- ✅ Single responsibility

**Estimated Complexity Reduction:** 22 → 18 (-18%)

---

#### Option D: Combine All Options ⭐⭐⭐ **100% Recommended**

**Approach:** Apply normalization service + query builder strategy + type resolution.

**Benefits:**
- ✅ Maximum complexity reduction
- ✅ Best separation of concerns
- ✅ Most maintainable and testable

**Estimated Complexity Reduction:** 22 → 10 (-55%)

**Effort:** Medium-High (6-8 hours)  
**Risk:** Low-Medium (well-isolated, but touches core validation)

---

## 3. TeamMoveService Class

**Location:** `app/Services/TeamMoveService.php`  
**Current Metrics:**
- **Cyclomatic Complexity:** 21 (threshold: 15) ⚠️ **+40% over limit**
- **Methods:** 15 (threshold: 10) ⚠️ **+50% over limit**
- **Lines of Code:** ~280

### Complexity Breakdown

The service orchestrates team move approval workflows with:
- Approval requirement checking
- Approver resolution
- Approval/rejection handling
- Move execution

**High Complexity Methods:**

1. **`requestMove()`** - Main entry point (complexity ~5)
   - Approval requirement checking
   - Direct move vs approval request branching
   - Approver resolution

2. **`requiresApproval()`** - Rule evaluation (complexity ~4)
   - Multiple rule checks
   - Enterprise validation

3. **`determineRequiredApprovers()`** - Approver resolution (complexity ~3)
   - Resolver selection
   - Cross-organisation detection

4. **`isCrossOrganisationMove()`** - Move type detection (complexity ~3)
   - Organisation finding logic
   - Comparison logic

5. **`findOrganisation()`** - Traversal logic (complexity ~3)
   - While loop with type checking

### Remediation Options

#### Option A: Extract Approval Rule Engine ⭐ **90% Recommended**

**Approach:** The service already uses rule interfaces, but the orchestration is complex. Extract approval decision logic.

**Implementation:**
```php
// app/Services/TeamMove/ApprovalDecisionEngine.php
final readonly class ApprovalDecisionEngine
{
    public function __construct(
        private array $rules,
    ) {}
    
    public function requiresApproval(Team $team, ?Team $newParent, ?Team $enterprise): bool
    {
        if (! $enterprise instanceof Team) {
            return false;
        }
        
        foreach ($this->rules as $rule) {
            if ($rule->requiresApproval($team, $newParent, $enterprise)) {
                return true;
            }
        }
        
        return false;
    }
}
```

**Benefits:**
- ✅ Removes ~4 complexity points from service
- ✅ Single responsibility for approval decisions
- ✅ Easier to test approval logic

**Estimated Complexity Reduction:** 21 → 17 (-19%)

---

#### Option B: Extract Organisation Finder Service ⭐ **85% Recommended**

**Approach:** Move organisation traversal to dedicated service.

**Implementation:**
```php
// app/Services/TeamOrganisationFinderService.php
final readonly class TeamOrganisationFinderService
{
    public function findOrganisation(Team $team): ?Team
    {
        $current = $team;
        
        while ($current) {
            if ($current->type === TeamType::ORGANISATION) {
                return $current;
            }
            
            $current = $current->parent;
        }
        
        return null;
    }
    
    public function isCrossOrganisationMove(Team $team, ?Team $newParent): bool
    {
        $sourceOrg = $this->findOrganisation($team);
        $targetOrg = $newParent ? $this->findOrganisation($newParent) : null;
        
        if (! $sourceOrg || ! $targetOrg) {
            return false;
        }
        
        return $sourceOrg->id !== $targetOrg->id;
    }
}
```

**Benefits:**
- ✅ Removes ~6 complexity points
- ✅ Reusable across codebase
- ✅ Better testability

**Estimated Complexity Reduction:** 21 → 15 (-29%)

---

#### Option C: Split into Request/Approval Services ⭐ **80% Recommended**

**Approach:** Separate move request handling from approval processing.

**Implementation:**
```php
// app/Services/TeamMove/TeamMoveRequestService.php
final readonly class TeamMoveRequestService
{
    public function __construct(
        private MoveTeam $moveTeamAction,
        private ApprovalDecisionEngine $approvalEngine,
        private ApproverResolverFactory $resolverFactory,
    ) {}
    
    public function requestMove(Team $team, ?int $newParentId, User $requestedBy, ?string $reason = null): TeamMoveApproval|Team
    {
        // Simplified request logic
    }
}

// app/Services/TeamMove/TeamMoveApprovalService.php
final readonly class TeamMoveApprovalService
{
    public function approve(TeamMoveApproval $approval, User $approver): void
    {
        // Approval logic
    }
    
    public function reject(TeamMoveApproval $approval, User $rejector, string $reason): void
    {
        // Rejection logic
    }
}
```

**Benefits:**
- ✅ Reduces methods per class (15 → ~8 each)
- ✅ Clear separation of concerns
- ✅ Better single responsibility

**Estimated Complexity Reduction:** 21 → 12 per service (-43%)

---

#### Option D: Combine Options A + B + C ⭐⭐⭐ **100% Recommended**

**Approach:** Apply all extraction strategies.

**Benefits:**
- ✅ Maximum complexity reduction
- ✅ Best separation of concerns
- ✅ Most maintainable

**Estimated Complexity Reduction:** 21 → 10 (-52%)

**Effort:** Medium-High (6-8 hours)  
**Risk:** Medium (touches core business logic)

---

## 4. User Model

**Location:** `app/Models/User.php`  
**Current Metrics:**
- **Methods:** 12 (threshold: 10) ⚠️ **+20% over limit**
- **Lines of Code:** ~265

### Method Breakdown

The User model has 12 methods:
1. `initials()` - String manipulation
2. `isProtectable()` - Role checking (complexity ~4)
3. `tenant()` - Relationship
4. `currentContext()` - Relationship
5. `enterprises()` - Relationship
6. `accessibleOrganisations()` - Relationship
7. `switchContext()` - Context switching
8. `validateContext()` - Context validation (complexity ~3)
9. `newEloquentBuilder()` - Builder override
10. `booted()` - Lifecycle hook
11. `casts()` - Cast definitions
12. `getBioHtmlAttribute()` - Accessor

### Remediation Options

#### Option A: Extract Context Management ⭐ **90% Recommended**

**Approach:** Move context-related methods to a trait or service.

**Implementation:**
```php
// app/Models/Concerns/ManagesUserContext.php
trait ManagesUserContext
{
    public function switchContext(Organisation $organisation): bool
    {
        if (! $this->accessibleOrganisations()->where('organisation_id', $organisation->id)->exists()) {
            return false;
        }
        
        return $this->update(['current_context_id' => $organisation->id]);
    }
    
    public function validateContext(): void
    {
        if (
            ! $this->current_context_id
            || ! $this->accessibleOrganisations()->where('organisation_id', $this->current_context_id)->exists()
        ) {
            $firstOrg = $this->accessibleOrganisations()->first();
            if ($firstOrg) {
                $this->update(['current_context_id' => $firstOrg->id]);
                
                return;
            }
            
            $this->update(['current_context_id' => null]);
        }
    }
}
```

**Benefits:**
- ✅ Reduces User model methods from 12 → 10
- ✅ Groups related functionality
- ✅ Reusable if needed elsewhere

**Estimated Method Reduction:** 12 → 10 (-17%)

---

#### Option B: Extract Protection Logic ⭐ **75% Recommended**

**Approach:** Move `isProtectable()` to a service (already has `ProtectsKeyRoles` trait).

**Implementation:**
```php
// app/Services/UserProtectionService.php
final readonly class UserProtectionService
{
    public function isProtectable(User $user): bool
    {
        // Move isProtectable logic here
    }
}
```

**Benefits:**
- ✅ Reduces User model methods
- ✅ Better testability
- ✅ Single responsibility

**Estimated Method Reduction:** 12 → 11 (-8%)

---

#### Option C: Combine Options A + B ⭐⭐⭐ **100% Recommended**

**Approach:** Extract both context management and protection logic.

**Benefits:**
- ✅ Maximum method reduction
- ✅ Cleaner model
- ✅ Better separation of concerns

**Estimated Method Reduction:** 12 → 9 (-25%)

**Effort:** Low-Medium (2-4 hours)  
**Risk:** Low (well-isolated changes)

---

## Summary & Recommendations

### Priority Matrix

| Component | Current Complexity | Target Complexity | Reduction | Effort | Risk | Priority |
|-----------|-------------------|-------------------|-----------|--------|------|----------|
| **HasTeamHierarchy** | 19 | 10 | -47% | Medium | Low | **HIGH** ⭐⭐⭐ |
| **TeamNameValidator** | 22 | 10 | -55% | Medium-High | Low-Medium | **HIGH** ⭐⭐⭐ |
| **TeamMoveService** | 21 | 10 | -52% | Medium-High | Medium | **MEDIUM** ⭐⭐ |
| **User Model** | 12 methods | 9 methods | -25% | Low-Medium | Low | **LOW** ⭐ |

### Recommended Implementation Order

1. **Week 1: HasTeamHierarchy** (Option C - Strategy + Traversal)
   - Highest complexity reduction
   - Low risk
   - Foundation for other improvements

2. **Week 2: TeamNameValidator** (Option D - All Extractions)
   - Highest complexity (22)
   - Well-isolated changes
   - Good test coverage opportunity

3. **Week 3: User Model** (Option C - Context + Protection)
   - Quick win
   - Low risk
   - Improves model clarity

4. **Week 4: TeamMoveService** (Option D - All Extractions)
   - Medium complexity
   - Touches core business logic
   - Requires careful testing

### Expected Outcomes

After implementing all recommendations:

- **HasTeamHierarchy:** 19 → 10 complexity (-47%)
- **TeamNameValidator:** 22 → 10 complexity, 13 → 8 methods (-55%, -38%)
- **TeamMoveService:** 21 → 10 complexity, 15 → 8 methods (-52%, -47%)
- **User Model:** 12 → 9 methods (-25%)

**Overall Architecture Health Improvement:**
- ✅ All components under complexity threshold
- ✅ All components under method count threshold
- ✅ Better separation of concerns
- ✅ Improved testability
- ✅ Easier maintenance

### Risk Mitigation

1. **Comprehensive Testing:** Write tests before refactoring
2. **Incremental Changes:** One component at a time
3. **Feature Flags:** Consider feature flags for gradual rollout
4. **Code Review:** Thorough review of extracted services
5. **Performance Testing:** Ensure no performance regressions

---

## Appendix: Complexity Metrics Explained

### Cyclomatic Complexity
Measures the number of linearly independent paths through code. Higher complexity = more branches, loops, and conditionals = harder to test and maintain.

**Formula:** Complexity = 1 + (number of decision points)

### Kan Defect Score
Heuristic estimating defect-proneness based on control-flow statements. Higher score = higher likelihood of bugs.

**Formula:** Based on cyclomatic complexity, lines of code, and control structures.

### Method Count
Number of methods in a class/trait. More methods = more responsibilities = harder to understand.

---

**Report End**
