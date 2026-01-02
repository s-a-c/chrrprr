# Detailed Subsystem Analysis

This document provides in-depth analysis of each subsystem with specific code examples, before/after comparisons, and detailed pros/cons.

---

## 1. Services Layer

### 1.1 DescendantCountRule

**File**: `app/Services/TeamMove/DescendantCountRule.php`

**Current Implementation:**
```php
private function getDescendantCount(Team $team): int
{
    $count = 0;
    $children = $team->children()->withoutGlobalScopes()->get();

    foreach ($children as $child) {
        $count++; // Count the direct child
        $count += $this->getDescendantCount($child); // Recursively count descendants
    }

    return $count;
}
```

**Proposed Implementation:**
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

**Analysis:**
- **Lines of Code**: 9 → 5 (44% reduction)
- **Cyclomatic Complexity**: 2 → 1 (50% reduction)
- **Mutability**: Uses mutable `$count` → Pure function
- **Readability**: Imperative → Declarative ("sum of 1 + descendants")

**Pros:**
- ✅ Eliminates mutable state
- ✅ More declarative and readable
- ✅ Consistent with `TeamHierarchyTraversalService` patterns
- ✅ Easier to test (pure function)
- ✅ Better aligns with functional programming principles

**Cons:**
- ⚠️ Slight performance overhead (negligible for typical hierarchies < 100 nodes)
- ⚠️ Requires understanding of collection recursion patterns
- ⚠️ May be less familiar to developers new to functional programming

**Recommendation Weight**: **95%** - High impact, core business logic

---

### 1.2 CrossOrganisationApproverResolver

**File**: `app/Services/TeamMove/CrossOrganisationApproverResolver.php`

**Current Implementation:**
```php
public function resolve(Team $team, ?Team $newParent): array
{
    $sourceOrg = $this->organisationFinder->findOrganisation($team);
    $targetOrg = $newParent instanceof Team ? $this->organisationFinder->findOrganisation($newParent) : null;

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
}
```

**Proposed Implementation:**
```php
public function resolve(Team $team, ?Team $newParent): array
{
    $sourceOrg = $this->organisationFinder->findOrganisation($team);
    $targetOrg = $newParent instanceof Team
        ? $this->organisationFinder->findOrganisation($newParent)
        : null;

    return collect()
        ->when(
            $sourceOrg instanceof Team,
            fn ($c) => $c->merge($this->getOrganisationAdmins($sourceOrg))
        )
        ->when(
            $targetOrg && $targetOrg->id !== $sourceOrg?->id,
            fn ($c) => $c->merge($this->getOrganisationAdmins($targetOrg))
        )
        ->unique()
        ->values()
        ->all();
}
```

**Alternative (More Explicit):**
```php
public function resolve(Team $team, ?Team $newParent): array
{
    $sourceOrg = $this->organisationFinder->findOrganisation($team);
    $targetOrg = $newParent instanceof Team
        ? $this->organisationFinder->findOrganisation($newParent)
        : null;

    $approvers = collect();

    if ($sourceOrg instanceof Team) {
        $approvers = $approvers->merge($this->getOrganisationAdmins($sourceOrg));
    }

    if ($targetOrg && $targetOrg->id !== $sourceOrg?->id) {
        $approvers = $approvers->merge($this->getOrganisationAdmins($targetOrg));
    }

    return $approvers->unique()->values()->all();
}
```

**Analysis:**
- **Lines of Code**: 15 → 12-15 (similar, but more declarative)
- **Mutability**: Mutable array → Immutable collection pipeline
- **Readability**: Imperative conditionals → Declarative `when()` calls

**Pros:**
- ✅ Eliminates mutable `$approvers` array
- ✅ More declarative conditional merging
- ✅ Better null handling with collection methods
- ✅ Consistent with other services using collections
- ✅ `values()` ensures sequential keys

**Cons:**
- ⚠️ `when()` method may be less familiar
- ⚠️ Alternative approach (explicit conditionals) is clearer but less "functional"
- ⚠️ Minimal performance difference

**Recommendation Weight**: **90%** - High impact, simple refactoring

---

### 1.3 TeamOrganisationFinderService

**File**: `app/Services/TeamOrganisationFinderService.php`

**Current Implementation:**
```php
private function getAncestry(Team $team): Collection
{
    $ancestors = collect();
    $current = $team->parent;

    // Build ancestry collection using functional approach
    while ($current) {
        $ancestors->push($current);

        if ($current->type === TeamType::ORGANISATION) {
            break;
        }

        $current = $current->parent;
    }

    return $ancestors;
}
```

**Proposed Implementation (Recursive):**
```php
private function getAncestry(Team $team): Collection
{
    return $this->buildAncestryUntilOrganisation($team->parent);
}

private function buildAncestryUntilOrganisation(?Team $current): Collection
{
    if (! $current) {
        return collect();
    }

    if ($current->type === TeamType::ORGANISATION) {
        return collect([$current]);
    }

    return $this->buildAncestryUntilOrganisation($current->parent)
        ->prepend($current);
}
```

**Alternative (Iterative with Collection Pipeline):**
```php
private function getAncestry(Team $team): Collection
{
    $ancestors = collect();
    $current = $team->parent;

    while ($current) {
        $ancestors = $ancestors->push($current);

        if ($current->type === TeamType::ORGANISATION) {
            break;
        }

        $current = $current->parent;
    }

    return $ancestors;
}
```

**Analysis:**
- **Current**: Uses `while` loop with mutable collection
- **Recursive**: Pure functional approach, similar to `TeamHierarchyTraversalService`
- **Iterative**: Minimal change, just ensures immutability

**Pros (Recursive):**
- ✅ Pure functional approach
- ✅ No mutable state
- ✅ Consistent with `TeamHierarchyTraversalService::buildAncestryCollection()`
- ✅ Easier to test

**Cons (Recursive):**
- ⚠️ More complex for developers unfamiliar with recursion
- ⚠️ Slightly more method calls (negligible performance impact)

**Pros (Iterative):**
- ✅ Minimal refactoring
- ✅ Still uses collection methods
- ✅ Easier to understand

**Cons (Iterative):**
- ⚠️ Still uses imperative `while` loop
- ⚠️ Less "functional" than recursive approach

**Recommendation Weight**: **85%** - Medium-high impact, choose approach based on team preference

**Recommendation**: Prefer recursive approach for consistency, but iterative is acceptable if team prefers.

---

### 1.4 TeamMoveApprovalService

**File**: `app/Services/TeamMove/TeamMoveApprovalService.php`

**Current Implementation:**
```php
private function recordApproval(TeamMoveApproval $approval, User $approver): void
{
    $existingApprovals = $approval->approvals ?? [];
    $existingApprovals[] = [
        'user_id' => $approver->id,
        'approved_at' => now()->toISOString(),
        'status' => 'approved',
    ];

    $approval->approvals = $existingApprovals;
}
```

**Proposed Implementation:**
```php
private function recordApproval(TeamMoveApproval $approval, User $approver): void
{
    $approval->approvals = collect($approval->approvals ?? [])
        ->push([
            'user_id' => $approver->id,
            'approved_at' => now()->toISOString(),
            'status' => 'approved',
        ])
        ->all();
}
```

**Analysis:**
- **Lines of Code**: 7 → 7 (similar)
- **Mutability**: Direct array manipulation → Collection pipeline

**Pros:**
- ✅ More consistent with collection usage elsewhere in class
- ✅ Slightly more declarative

**Cons:**
- ⚠️ Minimal benefit - current code is already clear
- ⚠️ Array push is idiomatic PHP

**Recommendation Weight**: **60%** - Low-medium priority, nice-to-have improvement

---

## 2. Models Layer

### 2.1 User::isProtectable()

**File**: `app/Models/User.php`

**Current Implementation:**
```php
public function isProtectable(): bool
{
    $pivotTable = config('permission.table_names.model_has_roles');
    $rolesTable = config('permission.table_names.roles');
    $teamKey = config('permission.column_names.team_foreign_key');

    // Get all key roles held by this user, including their team context
    $userKeyRoles = $this
        ->getConnection()
        ->table($pivotTable)
        ->join($rolesTable, "{$pivotTable}.role_id", '=', "{$rolesTable}.id")
        ->where("{$pivotTable}.model_id", $this->getKey())
        ->where("{$pivotTable}.model_type", $this->getMorphClass())
        ->where("{$rolesTable}.is_key", true)
        ->select("{$rolesTable}.id as role_id", "{$pivotTable}.{$teamKey} as team_id")
        ->get();

    foreach ($userKeyRoles as $row) {
        // Count users in this specific (role, team) combination
        $count = $this
            ->getConnection()
            ->table($pivotTable)
            ->where('role_id', $row->role_id)
            ->where($teamKey, $row->team_id)
            ->count();

        if ($count <= 1) {
            return true;
        }
    }

    return false;
}
```

**Proposed Implementation:**
```php
public function isProtectable(): bool
{
    $pivotTable = config('permission.table_names.model_has_roles');
    $rolesTable = config('permission.table_names.roles');
    $teamKey = config('permission.column_names.team_foreign_key');

    $userKeyRoles = $this
        ->getConnection()
        ->table($pivotTable)
        ->join($rolesTable, "{$pivotTable}.role_id", '=', "{$rolesTable}.id")
        ->where("{$pivotTable}.model_id", $this->getKey())
        ->where("{$pivotTable}.model_type", $this->getMorphClass())
        ->where("{$rolesTable}.is_key", true)
        ->select("{$rolesTable}.id as role_id", "{$pivotTable}.{$teamKey} as team_id")
        ->get();

    return $userKeyRoles->contains(function ($row) use ($pivotTable, $teamKey): bool {
        $count = $this
            ->getConnection()
            ->table($pivotTable)
            ->where('role_id', $row->role_id)
            ->where($teamKey, $row->team_id)
            ->count();

        return $count <= 1;
    });
}
```

**Analysis:**
- **Lines of Code**: 28 → 25 (slight reduction)
- **Cyclomatic Complexity**: 2 → 1 (early return eliminated)
- **Readability**: Imperative with early return → Declarative "contains any"

**Pros:**
- ✅ More declarative intent ("contains any protected role")
- ✅ Eliminates early return complexity
- ✅ Better readability
- ✅ Consistent with functional patterns

**Cons:**
- ⚠️ Query is already executed, so no performance benefit
- ⚠️ Need to ensure `->get()` returns a collection (it does)
- ⚠️ Closure captures `$this` and config values

**Recommendation Weight**: **85%** - High impact, simple refactoring

---

### 2.2 ManagesTeamRoles::removeExecutive()

**File**: `app/Models/Concerns/ManagesTeamRoles.php`

**Current Implementation:**
```php
public function removeExecutive(): void
{
    $this->withTeamContext(static function (): void {
        $executives = User::query()->role('executive')->get();
        foreach ($executives as $exec) {
            $exec->removeRole('executive');
        }
    });
}
```

**Proposed Implementation:**
```php
public function removeExecutive(): void
{
    $this->withTeamContext(static function (): void {
        User::query()
            ->role('executive')
            ->get()
            ->each(fn (User $exec) => $exec->removeRole('executive'));
    });
}
```

**Analysis:**
- **Lines of Code**: 6 → 5 (slight reduction)
- **Readability**: Imperative foreach → Declarative each

**Pros:**
- ✅ More functional approach
- ✅ Consistent with collection usage in `deputies()` method
- ✅ Slightly more concise

**Cons:**
- ⚠️ Current code is already clear and simple
- ⚠️ Minimal benefit

**Recommendation Weight**: **75%** - Medium priority, consistency improvement

---

### 2.3 ManagesTeamRoles::checkRoleConflict()

**File**: `app/Models/Concerns/ManagesTeamRoles.php`

**Current Implementation:**
```php
private function checkRoleConflict(User $user, string $role): void
{
    $conflictingRole = $role === 'executive' ? 'deputy' : 'executive';

    try {
        if ($role === 'executive') {
            $conflictingIds = User::query()->role($conflictingRole)->pluck('id');
            if ($conflictingIds->contains($user->id)) {
                throw ValidationException::withMessages([
                    'executive' => ['A user cannot be both executive and deputy of the same team.'],
                ]);
            }

            return;
        }

        $conflicting = User::query()->role($conflictingRole)->first();
        if ($conflicting && $conflicting->id === $user->id) {
            throw ValidationException::withMessages([
                'deputy' => ['A user cannot be both executive and deputy of the same team.'],
            ]);
        }
    } catch (RoleDoesNotExist $e) {
        // Role doesn't exist yet, so no conflict to check
        unset($e);
    }
}
```

**Analysis:**
- Already uses collections (`pluck`, `contains`)
- Logic is complex due to different handling for executive vs deputy
- Could be simplified but current approach is reasonable

**Potential Improvement:**
```php
private function checkRoleConflict(User $user, string $role): void
{
    $conflictingRole = $role === 'executive' ? 'deputy' : 'executive';

    try {
        $hasConflict = $role === 'executive'
            ? User::query()->role($conflictingRole)->pluck('id')->contains($user->id)
            : User::query()->role($conflictingRole)->where('id', $user->id)->exists();

        if ($hasConflict) {
            throw ValidationException::withMessages([
                $role => ['A user cannot be both executive and deputy of the same team.'],
            ]);
        }
    } catch (RoleDoesNotExist) {
        // Role doesn't exist yet, so no conflict to check
    }
}
```

**Pros:**
- ✅ Slightly more consistent
- ✅ Unified error message

**Cons:**
- ⚠️ Current code is clear
- ⚠️ Different logic paths are intentional

**Recommendation Weight**: **40%** - Low priority, current code is fine

---

## 3. Controllers Layer

### 3.1 BulkTeamController::store()

**File**: `app/Http/Controllers/Teams/BulkTeamController.php`

**Current Implementation:**
```php
public function store(BulkTeamRequest $request): JsonResponse
{
    $teams = $request->getTeams();
    $enterprise = $this->getEnterprise();

    // Validate batch size
    $maxBatchSize = $enterprise->bulk_operation_batch_size ?? 500;
    if (count($teams) > $maxBatchSize) {
        return response()->json([
            'success' => false,
            'error' => "Batch size exceeds maximum allowed ({$maxBatchSize}).",
        ], 422);
    }

    $results = [];
    $successCount = 0;
    $failureCount = 0;

    foreach ($teams as $index => $teamData) {
        try {
            $result = $this->processTeam($teamData, $index);
            $results[] = $result;
            $successCount++;
        } catch (Exception $e) {
            $results[] = [
                'index' => $index,
                'success' => false,
                'error' => $e->getMessage(),
                'action' => isset($teamData['id']) ? 'update' : 'create',
            ];
            $failureCount++;
        }
    }

    $statusCode = match (true) {
        $failureCount === 0 => 200, // All succeeded
        $successCount === 0 => 422, // All failed
        default => 207, // Partial success (Multi-Status)
    };

    $success = $successCount > 0;

    return response()->json([
        'success' => $success,
        'total' => count($teams),
        'success_count' => $successCount,
        'failure_count' => $failureCount,
        'results' => $results,
    ], $statusCode);
}
```

**Proposed Implementation:**
```php
public function store(BulkTeamRequest $request): JsonResponse
{
    $teams = collect($request->getTeams());
    $enterprise = $this->getEnterprise();

    // Validate batch size
    $maxBatchSize = $enterprise->bulk_operation_batch_size ?? 500;
    if ($teams->count() > $maxBatchSize) {
        return response()->json([
            'success' => false,
            'error' => "Batch size exceeds maximum allowed ({$maxBatchSize}).",
        ], 422);
    }

    $results = $teams->mapWithKeys(function ($teamData, $index) {
        try {
            $result = $this->processTeam($teamData, $index);
            return [$index => $result];
        } catch (Exception $e) {
            return [$index => [
                'index' => $index,
                'success' => false,
                'error' => $e->getMessage(),
                'action' => isset($teamData['id']) ? 'update' : 'create',
            ]];
        }
    })->values()->all();

    [$successes, $failures] = collect($results)->partition(
        fn ($result) => ($result['success'] ?? false) === true
    );

    $successCount = $successes->count();
    $failureCount = $failures->count();
    $total = $teams->count();

    $statusCode = match (true) {
        $failureCount === 0 => 200,
        $successCount === 0 => 422,
        default => 207,
    };

    return response()->json([
        'success' => $successCount > 0,
        'total' => $total,
        'success_count' => $successCount,
        'failure_count' => $failureCount,
        'results' => $results,
    ], $statusCode);
}
```

**Analysis:**
- **Lines of Code**: 50 → 45 (10% reduction)
- **Mutability**: Manual counters → Collection partition
- **Readability**: Imperative loop → Declarative pipeline

**Pros:**
- ✅ Eliminates manual counter variables
- ✅ More declarative result building
- ✅ Better separation of concerns (partition logic separate)
- ✅ Easier to test (can test partition separately)
- ✅ More consistent with functional patterns

**Cons:**
- ⚠️ Need to handle exceptions within map (done with try-catch)
- ⚠️ Slight refactoring complexity
- ⚠️ Performance consideration for large batches (but already limited by batch size)
- ⚠️ `mapWithKeys` then `values()` is slightly awkward

**Alternative (Cleaner Exception Handling):**
```php
private function processTeamSafely(array $teamData, int $index): array
{
    try {
        return $this->processTeam($teamData, $index);
    } catch (Exception $e) {
        return [
            'index' => $index,
            'success' => false,
            'error' => $e->getMessage(),
            'action' => isset($teamData['id']) ? 'update' : 'create',
        ];
    }
}

public function store(BulkTeamRequest $request): JsonResponse
{
    $teams = collect($request->getTeams());
    $enterprise = $this->getEnterprise();

    $maxBatchSize = $enterprise->bulk_operation_batch_size ?? 500;
    if ($teams->count() > $maxBatchSize) {
        return response()->json([
            'success' => false,
            'error' => "Batch size exceeds maximum allowed ({$maxBatchSize}).",
        ], 422);
    }

    $results = $teams
        ->mapWithKeys(fn ($teamData, $index) => [
            $index => $this->processTeamSafely($teamData, $index)
        ])
        ->values()
        ->all();

    [$successes, $failures] = collect($results)->partition(
        fn ($result) => ($result['success'] ?? false) === true
    );

    // ... rest of the method
}
```

**Recommendation Weight**: **80%** - High impact, improves maintainability

---

## 4. Tests Layer

### 4.1 Test Data Setup

**Current Pattern:**
```php
$teams = [
    ['name' => 'Team 1', 'type' => TeamType::ORGANISATION->value, 'parent_id' => $this->enterprise->id],
    ['name' => 'Team 2', 'type' => TeamType::ORGANISATION->value, 'parent_id' => $this->enterprise->id],
    ['name' => 'Team 3', 'type' => TeamType::ORGANISATION->value, 'parent_id' => $this->enterprise->id],
];
```

**Proposed Pattern:**
```php
$teams = collect(['Team 1', 'Team 2', 'Team 3'])
    ->map(fn ($name) => [
        'name' => $name,
        'type' => TeamType::ORGANISATION->value,
        'parent_id' => $this->enterprise->id,
    ])
    ->all();
```

**Analysis:**
- **Pros**: More DRY, easier to generate test data
- **Cons**: Current approach is clear, minimal benefit

**Recommendation Weight**: **55%** - Low-medium priority, nice-to-have

---

### 4.2 Test Assertions

**Current Pattern:**
```php
$validTeam = Organisation::query()
    ->where('parent_id', $this->enterprise->id)
    ->get()
    ->first(fn ($team): bool => $team->getTranslation('name', app()->getLocale()) === 'Valid Team');
```

**Analysis:**
- Already uses collections well (`first` with closure)
- Could use `where` collection method but current is fine

**Recommendation Weight**: **45%** - Low priority, current patterns are good

---

## Summary by Subsystem

| Subsystem | Priority | Weight | Candidates | Estimated Effort |
|-----------|----------|--------|-------------|------------------|
| Services | HIGH | 85-95% | 4 methods | 2-3 days |
| Models | MEDIUM | 70-85% | 3 methods | 1-2 days |
| Controllers | MEDIUM | 65-80% | 1 method | 1 day |
| Tests | LOW-MEDIUM | 45-55% | 2 patterns | 0.5-1 day |
| Actions | LOW | 30-35% | 1 method | 0.5 day |
| Support | LOW | 25% | 0 methods | 0 days |

**Total Estimated Effort**: 5-8 days for high/medium priority items

---

**Document Version**: 1.0
**Last Updated**: 2025-01-27
