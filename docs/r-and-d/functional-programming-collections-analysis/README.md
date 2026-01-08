# Functional Programming & Collections Analysis

## Executive Summary

This document provides a comprehensive analysis of opportunities to increase the use of Laravel Collections and functional programming idioms throughout the application. The analysis covers both application code and test suites, identifying candidates for refactoring and providing weighted recommendations.

**Key Findings:**
- **Current State**: Mixed adoption - some services already use collections effectively (TeamHierarchyTraversalService, ApprovalDecisionEngine), while others use imperative loops
- **Opportunity**: ~35-40% of foreach loops and array manipulations could benefit from collection pipelines
- **Priority Areas**: Services (high), Models (medium), Controllers (medium), Tests (low-medium)

---

## Table of Contents

1. [Current State Assessment](#current-state-assessment)
2. [Subsystem Analysis](#subsystem-analysis)
3. [Recommendations by Priority](#recommendations-by-priority)
4. [Implementation Guidelines](#implementation-guidelines)
5. [Examples & Patterns](#examples--patterns)

---

## Current State Assessment

### Collection Usage Patterns Found

**Already Using Collections Well:**
- `TeamHierarchyTraversalService` - Uses `flatMap`, `merge`, `prepend` for recursive operations
- `ApprovalDecisionEngine` - Uses `contains` with closures
- `TeamMoveApprovalService` - Uses `collect`, `pluck`, `intersect` for approval tracking
- `TeamNameNormalizationService` - Uses `filter`, `map`, `first` pipelines
- `TeamTypeResolutionService` - Uses collection pipeline for type resolution
- `ArrayNameQueryBuilder` - Uses `filter`, `each` for query building

**Opportunities for Improvement:**
- `BulkTeamController` - Uses `foreach` with manual array building
- `DescendantCountRule` - Uses recursive `foreach` instead of collection recursion
- `User::isProtectable()` - Uses `foreach` with early return pattern
- `CrossOrganisationApproverResolver` - Uses `array_merge`, `array_unique` instead of collections
- `ManagesTeamRoles` - Uses `foreach` for role removal
- `TeamOrganisationFinderService` - Uses `while` loop instead of collection pipeline

### Metrics

- **Total foreach loops found**: 41 files
- **Total while loops found**: 16 files
- **Array manipulation functions**: 6 files using `array_*` functions
- **Collection usage**: 25 files already using collections
- **Estimated conversion potential**: ~35-40% of loops could be converted

---

## Subsystem Analysis

### 1. Services Layer (Priority: **HIGH** - 85%)

**Current State:**
- Mixed adoption - some services are excellent examples, others use imperative patterns
- Services handle business logic and data transformations

**Candidates Identified:**

#### High Priority
1. **`DescendantCountRule::getDescendantCount()`** (95% weight)
   - **Current**: Recursive foreach with manual counting
   - **Proposed**: Collection-based recursive counting
   - **Pros**:
     - More declarative and readable
     - Better testability
     - Consistent with `TeamHierarchyTraversalService` patterns
   - **Cons**:
     - Slight performance overhead (negligible for typical hierarchies)
     - Learning curve for team members unfamiliar with collection recursion

2. **`CrossOrganisationApproverResolver::resolve()`** (90% weight)
   - **Current**: `array_merge`, `array_unique` with imperative logic
   - **Proposed**: Collection `merge`, `unique`, `flatten` pipeline
   - **Pros**:
     - More readable pipeline
     - Better null handling with `filter()`
     - Consistent with other services
   - **Cons**:
     - Minimal - collections are already used elsewhere in this service

3. **`TeamOrganisationFinderService::getAncestry()`** (85% weight)
   - **Current**: `while` loop building collection imperatively
   - **Proposed**: Functional collection pipeline (similar to `TeamHierarchyTraversalService`)
   - **Pros**:
     - Eliminates imperative state management
     - More testable
     - Consistent with existing patterns
   - **Cons**:
     - Requires refactoring to recursive approach
     - Slight complexity increase

#### Medium Priority
4. **`TeamMoveApprovalService::recordApproval()`** (60% weight)
   - **Current**: Manual array manipulation
   - **Proposed**: Collection `push` or `prepend` with `toArray()`
   - **Pros**: More consistent with collection usage elsewhere
   - **Cons**: Minimal benefit, current code is clear

**Recommendation**: Focus on high-priority candidates first. Services are the core business logic layer and benefit most from functional patterns.

---

### 2. Models Layer (Priority: **MEDIUM** - 70%)

**Current State:**
- Models primarily use Eloquent relationships (already functional)
- Some model methods use imperative patterns for complex logic

**Candidates Identified:**

#### High Priority
1. **`User::isProtectable()`** (85% weight)
   - **Current**: `foreach` with early return pattern
   - **Proposed**: Collection `contains` or `first` with closure
   - **Pros**:
     - More declarative intent ("contains any protected role")
     - Eliminates early return complexity
     - Better readability
   - **Cons**:
     - Slight performance consideration (but query is already executed)
     - Need to ensure query result is converted to collection

2. **`ManagesTeamRoles::removeExecutive()`** (75% weight)
   - **Current**: `foreach` to remove roles
   - **Proposed**: Collection `each` or `map` with `removeRole`
   - **Pros**:
     - More functional approach
     - Consistent with collection usage in `deputies()` method
   - **Cons**:
     - Current code is already clear and simple
     - Minimal benefit

#### Low Priority
3. **`ManagesTeamRoles::checkRoleConflict()`** (40% weight)
   - **Current**: Conditional logic with queries
   - **Proposed**: Could use collection `contains` but current approach is fine
   - **Pros**: Minimal
   - **Cons**: Current code is clear, refactoring adds little value

**Recommendation**: Focus on `User::isProtectable()` as it has the highest impact. Other methods are lower priority.

---

### 3. Controllers Layer (Priority: **MEDIUM** - 65%)

**Current State:**
- Controllers are thin, mostly delegating to actions
- Bulk operations controller has opportunities

**Candidates Identified:**

1. **`BulkTeamController::store()`** (80% weight)
   - **Current**: `foreach` with manual result array building and counters
   - **Proposed**: Collection `map` with `partition` for success/failure
   - **Pros**:
     - Eliminates manual counter variables
     - More declarative result building
     - Better separation of concerns
     - Easier to test
   - **Cons**:
     - Need to handle exceptions within map (use `mapWithKeys` or try-catch)
     - Slight refactoring complexity
     - Performance consideration for large batches (but already limited by batch size)

**Example Refactoring:**
```php
// Current
$results = [];
$successCount = 0;
$failureCount = 0;
foreach ($teams as $index => $teamData) {
    try {
        $result = $this->processTeam($teamData, $index);
        $results[] = $result;
        $successCount++;
    } catch (Exception $e) {
        $results[] = ['index' => $index, 'success' => false, ...];
        $failureCount++;
    }
}

// Proposed
$results = collect($teams)
    ->mapWithKeys(fn ($teamData, $index) => [
        $index => $this->processTeamSafely($teamData, $index)
    ])
    ->values()
    ->all();

[$successes, $failures] = collect($results)->partition(fn ($r) => $r['success']);
$successCount = $successes->count();
$failureCount = $failures->count();
```

**Recommendation**: Refactor `BulkTeamController` - it's a good candidate and will improve maintainability.

---

### 4. Tests Layer (Priority: **LOW-MEDIUM** - 50%)

**Current State:**
- Tests already use some collection methods (`pluck`, `contains`, `first`)
- Some test data setup could benefit from collections

**Candidates Identified:**

1. **Test Data Setup** (55% weight)
   - **Current**: Manual array building in some tests
   - **Proposed**: Collection factories for test data
   - **Pros**:
     - More readable test data generation
     - Reusable patterns
   - **Cons**:
     - Tests are already clear
     - Low priority - tests work fine as-is

2. **Assertions** (45% weight)
   - **Current**: Some tests use `first(fn ...)` which is good
   - **Proposed**: More consistent use of collection assertions
   - **Pros**: Slight improvement in readability
   - **Cons**: Current assertions are fine

**Recommendation**: Low priority. Focus on application code first. Test improvements can be done incrementally.

---

### 5. Actions Layer (Priority: **LOW** - 30%)

**Current State:**
- Actions are thin wrappers around business logic
- Most actions delegate to services or models

**Candidates Identified:**

1. **`CreateTeam::handle()`** (35% weight)
   - **Current**: Uses array spread operator, which is fine
   - **Proposed**: Could use collection for data transformation, but minimal benefit
   - **Pros**: Minimal
   - **Cons**: Current code is clear, no significant benefit

**Recommendation**: Low priority. Actions are already clean and functional.

---

### 6. Support/Validation Layer (Priority: **LOW** - 25%)

**Current State:**
- `ArrayNameQueryBuilder` already uses collections well
- Other validators are straightforward

**Candidates Identified:**

None significant - this layer is already well-designed.

**Recommendation**: No changes needed.

---

## Recommendations by Priority

### Phase 1: High-Impact Services (Weight: 85-95%)

1. **`DescendantCountRule::getDescendantCount()`** (95%)
   - Impact: High - Core business logic, used frequently
   - Effort: Medium - Requires recursive collection pattern
   - Risk: Low - Well-tested, isolated method

2. **`CrossOrganisationApproverResolver::resolve()`** (90%)
   - Impact: High - Business logic for approvals
   - Effort: Low - Simple array to collection conversion
   - Risk: Low - Straightforward refactoring

3. **`TeamOrganisationFinderService::getAncestry()`** (85%)
   - Impact: Medium-High - Used in move operations
   - Effort: Medium - Requires refactoring while loop
   - Risk: Low - Similar pattern exists in codebase

### Phase 2: Models & Controllers (Weight: 65-85%)

4. **`User::isProtectable()`** (85%)
   - Impact: Medium - User deletion protection logic
   - Effort: Low - Simple foreach to collection conversion
   - Risk: Low - Well-tested method

5. **`BulkTeamController::store()`** (80%)
   - Impact: Medium - Bulk operations endpoint
   - Effort: Medium - Requires exception handling in map
   - Risk: Medium - Core API endpoint, needs careful testing

6. **`ManagesTeamRoles::removeExecutive()`** (75%)
   - Impact: Low-Medium - Role management
   - Effort: Low - Simple conversion
   - Risk: Low - Isolated method

### Phase 3: Incremental Improvements (Weight: 30-60%)

7. **`TeamMoveApprovalService::recordApproval()`** (60%)
   - Impact: Low - Internal method
   - Effort: Low - Simple conversion
   - Risk: Low

8. **Test data setup improvements** (55%)
   - Impact: Low - Developer experience
   - Effort: Low - Incremental
   - Risk: None

---

## Implementation Guidelines

### Collection Patterns to Use

1. **Recursive Operations**
   ```php
   // Instead of foreach recursion
   return $children->flatMap(fn ($child) =>
       collect([$child])->merge($this->getDescendants($child))
   );
   ```

2. **Filtering & Mapping**
   ```php
   // Instead of foreach with conditions
   return collect($items)
       ->filter(fn ($item) => $item->isValid())
       ->map(fn ($item) => $item->transform())
       ->values();
   ```

3. **Partitioning**
   ```php
   // Instead of manual success/failure tracking
   [$successes, $failures] = $results->partition(fn ($r) => $r['success']);
   ```

4. **Early Returns**
   ```php
   // Instead of foreach with early return
   return $items->contains(fn ($item) => $item->isProtected());
   ```

5. **Array Merging**
   ```php
   // Instead of array_merge, array_unique
   return collect($source)
       ->merge($target)
       ->unique()
       ->values()
       ->all();
   ```

### When NOT to Use Collections

1. **Simple iterations** - If a foreach is clearer, keep it
2. **Performance-critical paths** - Collections have overhead
3. **Single-item operations** - No need to wrap in collection
4. **Database queries** - Eloquent already returns collections

### Testing Considerations

- Collections are well-tested by Laravel
- Focus tests on business logic, not collection mechanics
- Use collection assertions in tests: `expect($collection)->toHaveCount(5)`

---

## Examples & Patterns

### Example 1: Recursive Counting (DescendantCountRule)

**Before:**
```php
private function getDescendantCount(Team $team): int
{
    $count = 0;
    $children = $team->children()->withoutGlobalScopes()->get();

    foreach ($children as $child) {
        $count++;
        $count += $this->getDescendantCount($child);
    }

    return $count;
}
```

**After:**
```php
private function getDescendantCount(Team $team): int
{
    return $team->children()
        ->withoutGlobalScopes()
        ->get()
        ->sum(fn (Team $child): int =>
            1 + $this->getDescendantCount($child)
        );
}
```

**Benefits:**
- Eliminates mutable `$count` variable
- More declarative ("sum of 1 + descendants")
- Functional style

### Example 2: Array Merging (CrossOrganisationApproverResolver)

**Before:**
```php
$approvers = [];
if ($sourceOrg instanceof Team) {
    $sourceAdmins = $this->getOrganisationAdmins($sourceOrg);
    $approvers = array_merge($approvers, $sourceAdmins);
}
if ($targetOrg && $targetOrg->id !== $sourceOrg?->id) {
    $targetAdmins = $this->getOrganisationAdmins($targetOrg);
    $approvers = array_merge($approvers, $targetAdmins);
}
return array_unique($approvers);
```

**After:**
```php
return collect()
    ->when($sourceOrg instanceof Team, fn ($c) =>
        $c->merge($this->getOrganisationAdmins($sourceOrg))
    )
    ->when(
        $targetOrg && $targetOrg->id !== $sourceOrg?->id,
        fn ($c) => $c->merge($this->getOrganisationAdmins($targetOrg))
    )
    ->unique()
    ->values()
    ->all();
```

**Benefits:**
- Eliminates mutable array
- More declarative conditional merging
- Better null handling

### Example 3: Bulk Processing (BulkTeamController)

**Before:**
```php
$results = [];
$successCount = 0;
$failureCount = 0;

foreach ($teams as $index => $teamData) {
    try {
        $result = $this->processTeam($teamData, $index);
        $results[] = $result;
        $successCount++;
    } catch (Exception $e) {
        $results[] = ['index' => $index, 'success' => false, ...];
        $failureCount++;
    }
}
```

**After:**
```php
$results = collect($teams)
    ->mapWithKeys(fn ($teamData, $index) => [
        $index => $this->processTeamSafely($teamData, $index)
    ])
    ->values()
    ->all();

[$successes, $failures] = collect($results)->partition(
    fn ($result) => $result['success'] ?? false
);

$successCount = $successes->count();
$failureCount = $failures->count();
```

**Benefits:**
- Eliminates manual counters
- Clear separation of success/failure
- More testable (can test partition logic separately)

---

## Summary

### Overall Recommendation: **Proceed with Phased Approach**

**Phase 1 (Immediate - 2-3 weeks):**
- Refactor high-priority services (DescendantCountRule, CrossOrganisationApproverResolver, TeamOrganisationFinderService)
- Expected impact: Improved code consistency, better maintainability

**Phase 2 (Short-term - 1-2 months):**
- Refactor models and controllers (User::isProtectable, BulkTeamController)
- Expected impact: Better readability, reduced complexity

**Phase 3 (Ongoing):**
- Incremental improvements in tests and other layers
- Expected impact: Continuous improvement, developer experience

### Success Metrics

- **Code Quality**: Reduced complexity scores, improved readability
- **Consistency**: More uniform patterns across codebase
- **Maintainability**: Easier to understand and modify code
- **Performance**: No significant degradation (collections are optimized)

### Risks & Mitigations

1. **Performance Concerns**: Monitor query performance, use eager loading where needed
2. **Learning Curve**: Provide examples and documentation for team
3. **Over-Engineering**: Don't convert simple foreach loops unnecessarily
4. **Testing**: Ensure comprehensive test coverage for refactored code

---

**Document Version**: 1.0
**Last Updated**: 2025-01-27
**Author**: Code Analysis
