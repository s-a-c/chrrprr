# Complexity Reduction Implementation Plan

## Using Laravel Collections for Enhanced Remediation

**Document Version:** 1.0
**Created:** 2025-12-29
**Target Completion:** 4 weeks
**Strategy:** Functional Programming with Laravel Collections

---

Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Stacked Branches Strategy](#stacked-branches-strategy)
4. [Implementation Phases](#implementation-phases)
5. [Phase 1: HasTeamHierarchy Trait](#phase-1-hasteamhierarchy-trait)
6. [Phase 2: TeamNameValidator Class](#phase-2-teamnamevalidator-class)
7. [Phase 3: User Model](#phase-3-user-model)
8. [Phase 4: TeamMoveService Class](#phase-4-teammoveservice-class)
9. [Testing Strategy](#testing-strategy)
10. [Verification & Rollback](#verification--rollback)
11. [Post-Implementation](#post-implementation)

---

## Overview

This plan implements the enhanced remediation strategies from `COMPLEXITY_ANALYSIS_REPORT_ENHANCED.md`, focusing on using **Laravel Collections** to replace imperative loops and branching logic with declarative pipelines.

### Goals

- ✅ Reduce cyclomatic complexity by 47-55% across all components
- ✅ Replace `while` loops with collection `unfold` patterns
- ✅ Replace `foreach` loops with collection pipelines
- ✅ Use Higher Order Messaging to eliminate loop complexity
- ✅ Maintain 100% test coverage throughout refactoring

### Success Metrics


| Component         | Current    | Target    | Reduction |
| ----------------- | ---------- | --------- | --------- |
| HasTeamHierarchy  | 19         | 9         | -52%      |
| TeamNameValidator | 22         | 10        | -54%      |
| TeamMoveService   | 21         | 11        | -47%      |
| User Model        | 12 methods | 8 methods | -33%      |


---

## Prerequisites

### Required Knowledge

- Laravel Collections API (especially `unfold`, `each`, `filter`, `when`, `contains`)
- Higher Order Messaging (e.g., `->each->method()`)
- Strategy Pattern implementation
- Service extraction patterns
- Test-Driven Development (TDD)

### Tools & Commands

```bash
# Run architecture tests
composer test:architecture

# Run specific test suite
php artisan test --filter=HasTeamHierarchy

# Check complexity metrics
vendor/bin/phpstan analyse --level=max

# Format code
vendor/bin/pint

# Create new classes
php artisan make:class App/Services/TeamHierarchyTraversalService


```

### Branch Strategy

**Recommended: Use Stacked Branches** (see [Stacked Branches Strategy](#stacked-branches-strategy) section below)

```bash
# Create stacked branches following project's numbered convention
git checkout -b 001-has-team-hierarchy-collections
git checkout -b 002-team-name-validator-collections  # from 001
git checkout -b 003-user-model-extraction            # from 002
git checkout -b 004-team-move-service-split          # from 003
```

**Alternative: Independent Branches** (if not using stacked approach)

```bash
# Create independent feature branches (merge in sequence)
git checkout -b refactor/has-team-hierarchy-collections
git checkout -b refactor/team-name-validator-collections
git checkout -b refactor/user-model-extraction
git checkout -b refactor/team-move-service-split
```

---

## Stacked Branches Strategy

### Context: Solo Developer, Local-First Workflow

**Current Situation:**
- Working on feature branch: `002-enhanced-user-models` (in development)
- Solo developer (no team review needed)
- Remote CI/CD not yet proven (may need to skip)
- Complexity reduction should merge into feature branch when complete
- Need safest workflow with easy rollback

### What Are Stacked Branches?

**Stacked branches** (also called "stacked diffs" or "dependent branches") are a Git workflow where you create a series of branches that build on top of each other. Each branch represents a logical unit of work that can be reviewed and merged independently, but they share a dependency chain.

**Visual Representation for This Project:**

```
002-enhanced-user-models (current feature branch)
  └── cmplxty-001-has-team-hierarchy (Phase 1)
        └── cmplxty-002-team-name-validator (Phase 2)
              └── cmplxty-003-user-model-extraction (Phase 3)
                    └── cmplxty-004-team-move-service (Phase 4)
```

**Final State After All Merges:**

```
002-enhanced-user-models (contains all complexity reductions)
  └── [all phases merged locally]
```

### Why Use Stacked Branches for This Project?

#### Benefits

1. **Incremental Review & Merging**
   - Each phase can be reviewed and merged independently
   - Reduces cognitive load for reviewers (smaller PRs)
   - Enables faster feedback cycles

2. **Risk Mitigation**
   - Isolates changes per component
   - Easier to identify which phase introduced issues
   - Can merge stable phases while others are in progress

3. **Parallel Development**
   - Team members can work on different phases simultaneously
   - Reduces merge conflicts by keeping changes isolated
   - Enables faster overall delivery

4. **Flexible Rollback**
   - Can revert individual phases without affecting others
   - Easier to identify problematic changes
   - Maintains working state at each phase boundary

5. **Better Testing**
   - Each phase can be tested in isolation
   - Architecture tests can verify each phase independently
   - Easier to pinpoint test failures

6. **Project Convention Alignment**
   - Matches your numbered branch naming (`001-`, `002-`, etc.)
   - Follows existing workflow patterns
   - Consistent with project standards

#### Trade-offs

1. **Rebase Complexity**
   - Need to rebase dependent branches when base changes
   - Requires understanding of `git rebase --onto`

2. **Merge Order Dependency**
   - Phases must be merged in sequence
   - Cannot merge Phase 4 before Phase 1-3

3. **Temporary Complexity**
   - More branches to manage during development
   - Requires discipline to maintain stack

### Recommended Stacked Branch Structure

#### Branch Naming Convention

Following your project's convention of numbered branches:

```bash
# Phase 1: Foundation (HasTeamHierarchy)
001-has-team-hierarchy-collections

# Phase 2: Depends on Phase 1
002-team-name-validator-collections

# Phase 3: Depends on Phase 2
003-user-model-extraction

# Phase 4: Depends on Phase 3
004-team-move-service-split
```

#### Branch Creation Workflow

**Step 1: Create Base Branch (Phase 1)**

```bash
# Start from main/master
git checkout main
git pull origin main

# Create Phase 1 branch
git checkout -b 001-has-team-hierarchy-collections

# Work on Phase 1...
# Commit changes
git add .
git commit -m "refactor: extract HasTeamHierarchy validators with collections"
```

**Step 2: Create Dependent Branch (Phase 2)**

```bash
# While Phase 1 is in review, create Phase 2 from Phase 1
git checkout 001-has-team-hierarchy-collections
git checkout -b 002-team-name-validator-collections

# Work on Phase 2...
# Commit changes
git add .
git commit -m "refactor: extract TeamNameValidator services with collections"
```

**Step 3: Continue Stacking**

```bash
# Phase 3 depends on Phase 2
git checkout 002-team-name-validator-collections
git checkout -b 003-user-model-extraction

# Phase 4 depends on Phase 3
git checkout 003-user-model-extraction
git checkout -b 004-team-move-service-split
```

#### Handling Updates to Base Branches

**Scenario: Phase 1 gets feedback and needs changes**

```bash
# Make changes to Phase 1
git checkout 001-has-team-hierarchy-collections
# ... make changes ...
git commit -m "fix: address review feedback"

# Rebase Phase 2 onto updated Phase 1
git checkout 002-team-name-validator-collections
git rebase 001-has-team-hierarchy-collections

# Continue rebasing down the stack
git checkout 003-user-model-extraction
git rebase 002-team-name-validator-collections

git checkout 004-team-move-service-split
git rebase 003-user-model-extraction
```

**Alternative: Rebase onto main after Phase 1 merges**

```bash
# After Phase 1 merges to main
git checkout main
git pull origin main

# Rebase Phase 2 onto main (which now includes Phase 1)
git checkout 002-team-name-validator-collections
git rebase main

# Continue down the stack
git checkout 003-user-model-extraction
git rebase 002-team-name-validator-collections
```

### Pull Request Strategy

#### Option A: Sequential PRs (Recommended)

**Approach:** Open PRs one at a time, merge before opening next.

**Workflow:**

1. **Phase 1 PR**
   ```bash
   # Open PR: 001-has-team-hierarchy-collections → main
   gh pr create --title "refactor: HasTeamHierarchy with collections (Phase 1)" \
                --body "Extracts validators and traversal service using Laravel Collections"
   ```

2. **After Phase 1 merges:**
   ```bash
   # Update Phase 2 base to main
   git checkout main
   git pull origin main
   git checkout 002-team-name-validator-collections
   git rebase main

   # Open PR: 002-team-name-validator-collections → main
   gh pr create --title "refactor: TeamNameValidator with collections (Phase 2)" \
                --body "Extracts normalization and query builders using Collections"
   ```

3. **Repeat for Phases 3 & 4**

**Benefits:**
- ✅ Cleaner Git history
- ✅ Each PR builds on stable base
- ✅ Easier to review (no dependency confusion)
- ✅ Simpler rebase operations

**Drawbacks:**
- ⚠️ Cannot work on Phase 2 while Phase 1 is in review
- ⚠️ Slower overall delivery

#### Option B: Parallel PRs (Advanced)

**Approach:** Open all PRs simultaneously, mark dependencies.

**Workflow:**

1. **Open all PRs with dependency labels**
   ```bash
   # Phase 1 PR (no dependencies)
   gh pr create --title "refactor: HasTeamHierarchy (Phase 1)" \
                --label "phase-1,complexity-reduction"

   # Phase 2 PR (depends on Phase 1)
   gh pr create --title "refactor: TeamNameValidator (Phase 2)" \
                --label "phase-2,complexity-reduction,depends-on:001" \
                --base 001-has-team-hierarchy-collections

   # Phase 3 PR (depends on Phase 2)
   gh pr create --title "refactor: User Model (Phase 3)" \
                --label "phase-3,complexity-reduction,depends-on:002" \
                --base 002-team-name-validator-collections

   # Phase 4 PR (depends on Phase 3)
   gh pr create --title "refactor: TeamMoveService (Phase 4)" \
                --label "phase-4,complexity-reduction,depends-on:003" \
                --base 003-user-model-extraction
   ```

2. **Use GitHub's "Draft PR" feature**
   - Mark Phase 2-4 as drafts until dependencies merge
   - Auto-convert to ready when base merges

**Benefits:**
- ✅ Can review all phases simultaneously
- ✅ Better visibility of overall changes
- ✅ Can identify cross-phase issues early

**Drawbacks:**
- ⚠️ More complex to manage
- ⚠️ Requires careful rebasing
- ⚠️ Reviewers may be confused by dependencies

### Recommended Approach: Local-First Solo Developer Workflow

**Strategy:** Stack branches locally, merge incrementally into feature branch, push when ready.

#### Workflow Overview

1. **Create stacked branches from `002-enhanced-user-models`**
2. **Work on each phase locally** (no remote push until ready)
3. **Test thoroughly locally** before merging
4. **Merge each phase into feature branch** when complete
5. **Push to remote only when stable** (can skip CI/CD if needed)

#### Detailed Workflow

**Phase 1: HasTeamHierarchy**

```bash
# Start from current feature branch
git checkout 002-enhanced-user-models
git pull origin 002-enhanced-user-models  # Get latest if exists remotely

# Create Phase 1 branch
git checkout -b cmplxty-001-has-team-hierarchy

# Work on Phase 1...
# Test locally
php artisan test --filter=HasTeamHierarchy
composer test:architecture

# When Phase 1 is complete and tested:
git checkout 002-enhanced-user-models
git merge --no-ff cmplxty-001-has-team-hierarchy -m "refactor: HasTeamHierarchy with collections (Phase 1)"
# --no-ff creates merge commit for clarity

# Optional: Push feature branch (skip CI/CD if needed)
# git push origin 002-enhanced-user-models --no-verify  # Skip hooks if CI/CD issues
```

**Phase 2: TeamNameValidator**

```bash
# Create Phase 2 from updated feature branch
git checkout 002-enhanced-user-models
git checkout -b cmplxty-002-team-name-validator

# Work on Phase 2...
# Test locally
php artisan test --filter=TeamNameValidator
composer test:architecture

# When complete:
git checkout 002-enhanced-user-models
git merge --no-ff cmplxty-002-team-name-validator -m "refactor: TeamNameValidator with collections (Phase 2)"
```

**Phase 3 & 4: Repeat pattern**

```bash
# Phase 3
git checkout 002-enhanced-user-models
git checkout -b cmplxty-003-user-model-extraction
# ... work, test, merge ...

# Phase 4
git checkout 002-enhanced-user-models
git checkout -b cmplxty-004-team-move-service
# ... work, test, merge ...
```

**Final State:**

```bash
# All phases merged into feature branch
git checkout 002-enhanced-user-models
git log --oneline --graph

# Should show:
# * [merge] refactor: TeamMoveService with collections (Phase 4)
# * [merge] refactor: User Model extraction (Phase 3)
# * [merge] refactor: TeamNameValidator with collections (Phase 2)
# * [merge] refactor: HasTeamHierarchy with collections (Phase 1)
# * [previous commits from 002-enhanced-user-models]
```

#### Why This Approach is Safest

1. **Local Development First**
   - No remote dependencies during development
   - Can work offline
   - No CI/CD blocking issues

2. **Incremental Merges**
   - Each phase merges into feature branch when complete
   - Feature branch always contains working state
   - Easy to see progress with merge commits

3. **Easy Rollback**
   - Can revert individual merge commits
   - Feature branch remains stable
   - No complex rebase operations needed

4. **Flexible Remote Strategy**
   - Push when ready (not required during development)
   - Can skip CI/CD with `--no-verify` if needed
   - Can push feature branch incrementally or all at once

5. **Clear History**
   - Merge commits show each phase clearly
   - Easy to understand what changed when
   - Simple to cherry-pick or revert phases

#### Alternative: Keep Branches Separate Until All Complete

If you prefer to keep complexity reduction completely separate until all phases are done:

```bash
# Work on all phases in stack
002-enhanced-user-models
  └── cmplxty-001-has-team-hierarchy
        └── cmplxty-002-team-name-validator
              └── cmplxty-003-user-model-extraction
                    └── cmplxty-004-team-move-service

# When ALL phases complete, merge entire stack at once
git checkout 002-enhanced-user-models
git merge --no-ff cmplxty-004-team-move-service -m "refactor: complexity reduction (all phases)"
```

**Pros:** Cleaner final history, all-or-nothing approach
**Cons:** Feature branch doesn't get updates until end, harder to test incrementally

**Recommendation:** Use incremental merges (first approach) for safer, testable workflow.

### Git Commands Reference (Solo Developer, Local-First)

#### Creating the Stack from Feature Branch

```bash
# Ensure you're on the feature branch
git checkout 002-enhanced-user-models
git status  # Verify clean working directory

# Phase 1 (from feature branch)
git checkout -b cmplxty-001-has-team-hierarchy

# Phase 2 (from feature branch after Phase 1 merged)
git checkout 002-enhanced-user-models
git checkout -b cmplxty-002-team-name-validator

# Phase 3 (from feature branch after Phase 2 merged)
git checkout 002-enhanced-user-models
git checkout -b cmplxty-003-user-model-extraction

# Phase 4 (from feature branch after Phase 3 merged)
git checkout 002-enhanced-user-models
git checkout -b cmplxty-004-team-move-service
```

#### Merging Phases into Feature Branch

```bash
# After completing Phase 1
git checkout 002-enhanced-user-models
git merge --no-ff cmplxty-001-has-team-hierarchy \
  -m "refactor: HasTeamHierarchy with collections (Phase 1)"

# After completing Phase 2
git checkout 002-enhanced-user-models
git merge --no-ff cmplxty-002-team-name-validator \
  -m "refactor: TeamNameValidator with collections (Phase 2)"

# Continue for Phases 3 & 4...
```

#### Handling Updates to Feature Branch

If `002-enhanced-user-models` gets updates while you're working on a phase:

```bash
# Option 1: Rebase the phase branch (cleaner history)
git checkout cmplxty-002-team-name-validator
git rebase 002-enhanced-user-models

# Option 2: Merge feature branch into phase branch (safer, preserves history)
git checkout cmplxty-002-team-name-validator
git merge 002-enhanced-user-models
```

**Recommendation:** Use merge (Option 2) for safety - preserves exact history, easier to understand.

#### Viewing the Stack

```bash
# See all complexity branches
git branch | grep cmplxty

# See commits in feature branch (should show merge commits)
git log --oneline --graph 002-enhanced-user-models

# See what's in a phase branch vs feature branch
git log --oneline 002-enhanced-user-models..cmplxty-001-has-team-hierarchy

# See entire stack structure
git log --oneline --graph --all --decorate | grep -E "(cmplxty|002-enhanced)"
```

#### Cleaning Up After Merges

Once all phases are merged and you're confident:

```bash
# Delete local phase branches (they're merged, safe to delete)
git branch -d cmplxty-001-has-team-hierarchy
git branch -d cmplxty-002-team-name-validator
git branch -d cmplxty-003-user-model-extraction
git branch -d cmplxty-004-team-move-service

# Force delete if Git complains (only if you're 100% sure they're merged)
# git branch -D cmplxty-001-has-team-hierarchy
```

#### Remote Push Strategy (When Ready)

```bash
# Push feature branch (with all complexity reductions)
git checkout 002-enhanced-user-models
git push origin 002-enhanced-user-models

# If CI/CD is problematic, skip hooks:
# git push origin 002-enhanced-user-models --no-verify

# Or push without triggering CI (if configured):
# git push origin 002-enhanced-user-models --no-verify
```

#### Rollback Strategy

```bash
# View merge commits
git log --oneline --merges 002-enhanced-user-models

# Revert a specific phase merge
git revert -m 1 <merge-commit-hash>
# -m 1 keeps the mainline (002-enhanced-user-models)

# Or reset to before a merge (destructive, use with caution)
git reset --hard <commit-before-merge>
```

### Local Testing Checklist (Before Each Merge)

Before merging each phase into `002-enhanced-user-models`:

```bash
# 1. Run all tests for the component
php artisan test --filter=HasTeamHierarchy
php artisan test --filter=TeamNameValidator
# etc.

# 2. Run architecture tests
composer test:architecture

# 3. Run full test suite (if time permits)
php artisan test

# 4. Check for linting issues
vendor/bin/pint --test
vendor/bin/phpstan analyse --level=max

# 5. Verify complexity reduction
composer test:architecture | grep -E "(HasTeamHierarchy|TeamNameValidator|User|TeamMoveService)"
```

### Optional: Self-Review Checklist

Even as a solo developer, review your own work before merging:

- [ ] Code follows project conventions
- [ ] Collection patterns are appropriate
- [ ] Tests cover new functionality
- [ ] No obvious performance regressions
- [ ] Complexity metrics improved as expected
- [ ] No breaking changes to public APIs
- [ ] Documentation/comments updated if needed

### Troubleshooting (Solo Developer Context)

#### Issue: Merge conflicts when merging phase into feature branch

```bash
# Resolve conflicts manually
git checkout 002-enhanced-user-models
git merge --no-ff cmplxty-001-has-team-hierarchy
# ... resolve conflicts in files ...
git add .
git commit  # Complete the merge

# If conflicts are too complex, abort and investigate
git merge --abort
# Check what changed in feature branch
git log 002-enhanced-user-models --oneline -10
```

#### Issue: Feature branch updated while working on phase

```bash
# Option 1: Merge feature branch into phase branch (safest)
git checkout cmplxty-002-team-name-validator
git merge 002-enhanced-user-models
# Resolve any conflicts, then continue working

# Option 2: Rebase phase branch onto feature branch (cleaner, but rewrites history)
git checkout cmplxty-002-team-name-validator
git rebase 002-enhanced-user-models
# Resolve conflicts as they appear
git rebase --continue
```

**Recommendation:** Use merge (Option 1) - it's safer and preserves exact history.

#### Issue: Need to fix something in an already-merged phase

```bash
# Option 1: Fix in feature branch directly (simplest)
git checkout 002-enhanced-user-models
# Make fix
git commit -m "fix: address issue in HasTeamHierarchy refactor"

# Option 2: Fix in phase branch, then merge again
git checkout cmplxty-001-has-team-hierarchy
# Make fix
git commit -m "fix: address issue"
git checkout 002-enhanced-user-models
git merge --no-ff cmplxty-001-has-team-hierarchy -m "fix: HasTeamHierarchy issue"
```

#### Issue: Want to test phase in isolation before merging

```bash
# Stay on phase branch, test thoroughly
git checkout cmplxty-001-has-team-hierarchy
php artisan test
composer test:architecture

# Only merge when confident
git checkout 002-enhanced-user-models
git merge --no-ff cmplxty-001-has-team-hierarchy
```

#### Issue: Need to see what changed in a phase

```bash
# See commits in phase branch not in feature branch
git log 002-enhanced-user-models..cmplxty-001-has-team-hierarchy --oneline

# See file changes
git diff 002-enhanced-user-models..cmplxty-001-has-team-hierarchy

# See summary
git diff --stat 002-enhanced-user-models..cmplxty-001-has-team-hierarchy
```

#### Issue: Accidentally merged wrong branch or want to undo merge

```bash
# View recent merges
git log --oneline --merges -5

# Revert the merge (creates new commit that undoes it)
git revert -m 1 <merge-commit-hash>

# Or reset to before merge (destructive - only if nothing else depends on it)
git reset --hard <commit-before-merge>
```

#### Issue: Lost track of which phase you're on

```bash
# See current branch
git branch --show-current

# See all complexity branches
git branch | grep cmplxty

# See branch structure
git log --oneline --graph --all --decorate | head -20
```

---

## Implementation Phases

### Timeline


| Phase       | Component         | Duration | Priority  |
| ----------- | ----------------- | -------- | --------- |
| **Phase 1** | HasTeamHierarchy  | 4 hours  | HIGH ⭐⭐⭐  |
| **Phase 2** | TeamNameValidator | 6 hours  | HIGH ⭐⭐⭐  |
| **Phase 3** | User Model        | 3 hours  | MEDIUM ⭐⭐ |
| **Phase 4** | TeamMoveService   | 8 hours  | MEDIUM ⭐⭐ |


**Total Estimated Time:** 21 hours (3 days)

---

## Phase 1: HasTeamHierarchy Trait

**Target:** Complexity 19 → 9 (-52%)
**Duration:** 4 hours
**Risk Level:** Low

### Step 1.1: Create Validation Strategy Classes

**Time:** 1 hour

#### 1.1.1: Create Interface

```bash
php artisan make:class App/Support/Validation/TeamHierarchy/HierarchyValidatorInterface

```

**File:** `app/Support/Validation/TeamHierarchy/HierarchyValidatorInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Support\Validation\TeamHierarchy;

use App\Models\Team;
use Illuminate\Validation\ValidationException;

interface HierarchyValidatorInterface
{
    /**
     * Validate the team hierarchy rule.
     *
     * @throws ValidationException
     */
    public function validate(Team $team): void;
}

```

#### 1.1.2: Create Enterprise Parent Validator

```bash
php artisan make:class App/Support/Validation/TeamHierarchy/EnterpriseParentValidator

```

**File:** `app/Support/Validation/TeamHierarchy/EnterpriseParentValidator.php`

```php
<?php

declare(strict_types=1);

namespace App\Support\Validation\TeamHierarchy;

use App\Enums\TeamType;
use App\Models\Team;
use Illuminate\Validation\ValidationException;

final class EnterpriseParentValidator implements HierarchyValidatorInterface
{
    public function validate(Team $team): void
    {
        if ($team->type === TeamType::ENTERPRISE && $team->parent_id !== null) {
            throw ValidationException::withMessages([
                'parent_id' => ['Enterprises cannot have a parent team.'],
            ]);
        }
    }
}

```

#### 1.1.3: Create Parent Type Validator

```bash
php artisan make:class App/Support/Validation/TeamHierarchy/ParentTypeValidator

```

**File:** `app/Support/Validation/TeamHierarchy/ParentTypeValidator.php`

```php
<?php

declare(strict_types=1);

namespace App\Support\Validation\TeamHierarchy;

use App\Enums\TeamType;
use App\Models\Team;
use Illuminate\Validation\ValidationException;

final class ParentTypeValidator implements HierarchyValidatorInterface
{
    public function validate(Team $team): void
    {
        $parent = $this->resolveParent($team);

        if (! $parent) {
            return;
        }

        $validParentType = match ($team->type) {
            TeamType::ORGANISATION => TeamType::ENTERPRISE,
            TeamType::DIVISION => TeamType::ORGANISATION,
            TeamType::DEPARTMENT => TeamType::DIVISION,
            TeamType::PROJECT => TeamType::DEPARTMENT,
            default => null,
        };

        if ($validParentType && $parent->type !== $validParentType) {
            throw ValidationException::withMessages([
                'parent_id' => [
                    "{$team->type->value} must belong to a {$validParentType->value}, but belongs to {$parent->type->value}.",
                ],
            ]);
        }
    }

    private function resolveParent(Team $team): ?Team
    {
        if ($team->parent_id === null) {
            throw ValidationException::withMessages([
                'parent_id' => ['This team type requires a parent team.'],
            ]);
        }

        return $team->parent ?? $team
            ->newQuery()
            ->withoutGlobalScopes()
            ->where('id', $team->parent_id)
            ->whereNull('deleted_at')
            ->first();
    }
}

```

#### 1.1.4: Create Cycle Validator

```bash
php artisan make:class App/Support/Validation/TeamHierarchy/CycleValidator

```

**File:** `app/Support/Validation/TeamHierarchy/CycleValidator.php`

```php
<?php

declare(strict_types=1);

namespace App\Support\Validation\TeamHierarchy;

use App\Models\Team;
use App\Services\TeamHierarchyTraversalService;
use Illuminate\Validation\ValidationException;

final class CycleValidator implements HierarchyValidatorInterface
{
    public function __construct(
        private TeamHierarchyTraversalService $traversalService,
    ) {}

    public function validate(Team $team): void
    {
        if (! $team->id) {
            return;
        }

        if ((int) $team->parent_id === (int) $team->id) {
            throw ValidationException::withMessages([
                'parent_id' => ['A team cannot be its own parent.'],
            ]);
        }

        $parent = $team->parent;
        if ($parent && $this->traversalService->isDescendantOf($parent, $team)) {
            throw ValidationException::withMessages([
                'parent_id' => ['A team cannot be moved into its own descendant (would create a cycle).'],
            ]);
        }
    }
}

```

#### 1.1.5: Create Depth Validator

```bash
php artisan make:class App/Support/Validation/TeamHierarchy/DepthValidator

```

**File:** `app/Support/Validation/TeamHierarchy/DepthValidator.php`

```php
<?php

declare(strict_types=1);

namespace App\Support\Validation\TeamHierarchy;

use App\Models\Team;
use App\Services\TeamHierarchyTraversalService;
use Illuminate\Validation\ValidationException;

final class DepthValidator implements HierarchyValidatorInterface
{
    public function __construct(
        private TeamHierarchyTraversalService $traversalService,
    ) {}

    public function validate(Team $team): void
    {
        $parent = $team->parent;

        if (! $parent) {
            return;
        }

        if ($this->traversalService->getDepth($parent) >= 10) {
            throw ValidationException::withMessages([
                'parent_id' => ['Team hierarchy depth cannot exceed 10 levels.'],
            ]);
        }
    }
}

```

**✅ Checkpoint 1.1:** All validator classes created and follow single responsibility principle.

---

### Step 1.2: Create Traversal Service with Collections

**Time:** 1.5 hours

#### 1.2.1: Create Service

```bash
php artisan make:class App/Services/TeamHierarchyTraversalService

```

**File:** `app/Services/TeamHierarchyTraversalService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service for traversing team hierarchies using collection pipelines.
 */
final readonly class TeamHierarchyTraversalService
{
    /**
     * Check if a team is a descendant of another team using collection unfold.
     *
     * Replaces while loop with functional unfold pattern.
     */
    public function isDescendantOf(Team $team, Team $ancestor): bool
    {
        return $this->getAncestry($team)
            ->contains('id', $ancestor->id);
    }

    /**
     * Get the depth of a team in the hierarchy using collection count.
     *
     * Replaces while loop with functional pipeline.
     *
     * @psalm-return int<1, max>
     */
    public function getDepth(Team $team): int
    {
        return $this->getAncestry($team)->count() + 1;
    }

    /**
     * Get all ancestors of a team using collection unfold.
     *
     * This replaces the imperative while loop with a functional unfold pattern.
     * Unfold generates a sequence until the generator returns null.
     */
    public function getAncestry(Team $team): Collection
    {
        return collect([$team->parent_id])
            ->unfold(function ($parentId) {
                if (! $parentId) {
                    return null; // Stop unfolding
                }

                $parent = DB::table('teams')
                    ->where('id', $parentId)
                    ->first();

                if (! $parent) {
                    return null; // Stop unfolding
                }

                // Return [current, next] for unfold to continue
                return [
                    $parent,
                    $parent->parent_id, // Next value to unfold
                ];
            })
            ->filter() // Remove nulls
            ->map(fn ($parent) => Team::query()->withoutGlobalScopes()->find($parent->id))
            ->filter(); // Remove nulls from failed lookups
    }

    /**
     * Get all descendants of a team (for batch operations).
     *
     * Uses recursive collection mapping instead of imperative loops.
     */
    public function getDescendants(Team $team): Collection
    {
        $children = $team->children()->withoutGlobalScopes()->get();

        if ($children->isEmpty()) {
            return collect();
        }

        return $children
            ->merge(
                $children->flatMap(fn ($child) => $this->getDescendants($child))
            );
    }
}

```

**Key Collection Patterns Used:**

- `unfold()` - Replaces `while` loops with functional generation
- `contains()` - Replaces manual iteration with declarative check
- `count()` - Replaces manual counter increment
- `flatMap()` - Handles recursive structures elegantly

**✅ Checkpoint 1.2:** Traversal service uses collections instead of loops.

---

### Step 1.3: Refactor HasTeamHierarchy Trait

**Time:** 1 hour

#### 1.3.1: Update Trait with Collection-Based Validation

**File:** `app/Models/Concerns/HasTeamHierarchy.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\TeamType;
use App\Models\Team;
use App\Services\TeamHierarchyTraversalService;
use App\Support\Validation\TeamHierarchy\CycleValidator;
use App\Support\Validation\TeamHierarchy\DepthValidator;
use App\Support\Validation\TeamHierarchy\EnterpriseParentValidator;
use App\Support\Validation\TeamHierarchy\ParentTypeValidator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

trait HasTeamHierarchy
{
    /**
     * Update tenant IDs for all descendants recursively using collections.
     */
    public function updateDescendantTenants(string $newTenantId): void
    {
        $this->children()
            ->withoutGlobalScopes()
            ->get()
            ->each(function (Team $child) use ($newTenantId): void {
                $child->tenant_id = $newTenantId;
                $child->save(); // Triggers updated recursively
            });
    }

    /**
     * Validate team hierarchy rules using collection-based validator orchestration.
     *
     * Replaces multiple if/else branches with collection pipeline.
     */
    public function validateHierarchy(): void
    {
        $type = $this->normalizeType();

        if (! $type) {
            return;
        }

        // Use collection pipeline with Higher Order Messaging
        $this->getValidators()
            ->each->validate($this); // Higher Order Messaging eliminates loop complexity
    }

    /**
     * Get validators as a collection, conditionally adding validators based on team type.
     *
     * Uses collection 'when' method to conditionally build validator list.
     */
    private function getValidators(): Collection
    {
        return collect([new EnterpriseParentValidator()])
            ->when(
                $this->type !== TeamType::ENTERPRISE,
                fn (Collection $validators) => $validators->concat([
                    new ParentTypeValidator(),
                    new CycleValidator(
                        app(TeamHierarchyTraversalService::class)
                    ),
                    new DepthValidator(
                        app(TeamHierarchyTraversalService::class)
                    ),
                ])
            );
    }

    /**
     * Check if this team is a descendant of the given team.
     *
     * Delegates to traversal service for collection-based implementation.
     */
    public function isDescendantOf(Team $team): bool
    {
        return app(TeamHierarchyTraversalService::class)
            ->isDescendantOf($this, $team);
    }

    /**
     * Get the depth of this team in the hierarchy.
     *
     * Delegates to traversal service for collection-based implementation.
     *
     * @psalm-return int<1, max>
     */
    public function getDepth(): int
    {
        return app(TeamHierarchyTraversalService::class)
            ->getDepth($this);
    }

    /**
     * Normalize type to enum (handles both string and enum).
     */
    private function normalizeType(): ?TeamType
    {
        $typeValue = $this->getAttribute('type');

        if ($typeValue instanceof TeamType) {
            return $typeValue;
        }

        if (is_string($typeValue)) {
            return TeamType::from($typeValue);
        }

        return null;
    }
}

```

**Key Changes:**

- ✅ `validateHierarchy()` uses `->each->validate()` (Higher Order Messaging)
- ✅ `getValidators()` uses `collect()->when()->concat()` pipeline
- ✅ `updateDescendantTenants()` uses `->each()` instead of `foreach`
- ✅ `isDescendantOf()` and `getDepth()` delegate to service

**✅ Checkpoint 1.3:** Trait complexity reduced, all loops replaced with collections.

---

### Step 1.4: Update Tests

**Time:** 30 minutes

#### 1.4.1: Create Tests for Validators

```bash
php artisan make:test --pest Support/Validation/TeamHierarchy/EnterpriseParentValidatorTest
php artisan make:test --pest Support/Validation/TeamHierarchy/ParentTypeValidatorTest
php artisan make:test --pest Support/Validation/TeamHierarchy/CycleValidatorTest
php artisan make:test --pest Support/Validation/TeamHierarchy/DepthValidatorTest

```

#### 1.4.2: Create Tests for Traversal Service

```bash
php artisan make:test --pest Services/TeamHierarchyTraversalServiceTest

```

**Example Test:** `tests/Feature/Services/TeamHierarchyTraversalServiceTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Enterprise;
use App\Models\Organisation;
use App\Services\TeamHierarchyTraversalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('can determine if team is descendant using collections', function (): void {
    $enterprise = Enterprise::factory()->create();
    $org1 = Organisation::factory()->create(['parent_id' => $enterprise->id]);
    $org2 = Organisation::factory()->create(['parent_id' => $enterprise->id]);

    $service = new TeamHierarchyTraversalService();

    expect($service->isDescendantOf($org1, $enterprise))->toBeTrue();
    expect($service->isDescendantOf($org2, $org1))->toBeFalse();
});

it('can calculate depth using collections', function (): void {
    $enterprise = Enterprise::factory()->create();
    $org = Organisation::factory()->create(['parent_id' => $enterprise->id]);

    $service = new TeamHierarchyTraversalService();

    expect($service->getDepth($enterprise))->toBe(1);
    expect($service->getDepth($org))->toBe(2);
});

it('can get ancestry collection', function (): void {
    $enterprise = Enterprise::factory()->create();
    $org = Organisation::factory()->create(['parent_id' => $enterprise->id]);

    $service = new TeamHierarchyTraversalService();
    $ancestry = $service->getAncestry($org);

    expect($ancestry)->toHaveCount(1);
    expect($ancestry->first()->id)->toBe($enterprise->id);
});

```

#### 1.4.3: Run Tests

```bash
php artisan test --filter=TeamHierarchy

```

**✅ Checkpoint 1.4:** All tests passing, 100% coverage maintained.

---

### Step 1.5: Verify Complexity Reduction

**Time:** 15 minutes

```bash
# Run architecture tests
composer test:architecture

# Verify HasTeamHierarchy is no longer flagged
# Expected: No errors for HasTeamHierarchy trait

```

**✅ Phase 1 Complete:** HasTeamHierarchy complexity reduced from 19 → 9 (-52%)

---

## Phase 2: TeamNameValidator Class

**Target:** Complexity 22 → 10 (-54%), Methods 13 → 7 (-46%)
**Duration:** 6 hours
**Risk Level:** Low-Medium

### Step 2.1: Create Name Normalization Service

**Time:** 1.5 hours

#### 2.1.1: Create Service

```bash
php artisan make:class App/Services/TeamNameNormalizationService

```

**File:** `app/Services/TeamNameNormalizationService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * Service for normalizing team names using collection pipelines.
 */
final readonly class TeamNameNormalizationService
{
    /**
     * Normalize the team name for validation purposes.
     *
     * Uses collection pipelines to replace nested conditionals.
     *
     * @return array<string, string>|string
     */
    public function normalize(Team $team): array|string
    {
        $nameRaw = $team->attributes['name'] ?? null;

        // Use collection pipeline instead of nested if/else
        return collect([$nameRaw])
            ->filter(fn ($name) => $this->isValidStringName($name))
            ->map(fn (string $name) => $this->processStringName($name))
            ->first() ?? $this->getTeamNameFallback($team);
    }

    /**
     * Check if the name is a valid string.
     */
    private function isValidStringName(mixed $nameRaw): bool
    {
        return is_string($nameRaw) && $nameRaw !== '';
    }

    /**
     * Process a string name, potentially decoding JSON.
     *
     * Uses collection to handle array extraction.
     *
     * @return array<string, string>|string
     */
    private function processStringName(string $nameRaw): array|string
    {
        $decoded = json_decode($nameRaw, true);

        return is_array($decoded)
            ? $this->extractStringPairs($decoded)
            : $nameRaw;
    }

    /**
     * Extract string key-value pairs from decoded JSON using collections.
     *
     * Replaces foreach loop with collection filter/map pipeline.
     *
     * @param  array<mixed>  $decoded
     * @return array<string, string>
     */
    private function extractStringPairs(array $decoded): array
    {
        return collect($decoded)
            ->filter(fn ($value, $key) => is_string($key) && is_string($value))
            ->toArray();
    }

    /**
     * Get team name fallback value.
     */
    private function getTeamNameFallback(Team $team): string
    {
        $name = $team->name;

        return is_string($name) ? $name : '';
    }
}

```

**Key Collection Patterns:**

- `collect()->filter()->map()->first()` - Replaces nested if/else
- `collect()->filter()` - Replaces foreach with continue statements

**✅ Checkpoint 2.1:** Normalization service created with collection pipelines.

---

### Step 2.2: Create Query Builder Strategies

**Time:** 2 hours

#### 2.2.1: Create Interface

```bash
php artisan make:class App/Support/Validation/TeamName/NameQueryBuilderInterface

```

**File:** `app/Support/Validation/TeamName/NameQueryBuilderInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Support\Validation\TeamName;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;

interface NameQueryBuilderInterface
{
    /**
     * Apply name constraints to the query.
     */
    public function applyConstraints(Builder $query, mixed $name, Team $team): void;
}

```

#### 2.2.2: Create Array Name Query Builder

```bash
php artisan make:class App/Support/Validation/TeamName/ArrayNameQueryBuilder

```

**File:** `app/Support/Validation/TeamName/ArrayNameQueryBuilder.php`

```php
<?php

declare(strict_types=1);

namespace App\Support\Validation\TeamName;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query builder for array-based (translatable) team names.
 *
 * Uses collections to filter and apply constraints without nested conditionals.
 */
final class ArrayNameQueryBuilder implements NameQueryBuilderInterface
{
    public function applyConstraints(Builder $query, array $names, Team $team): void
    {
        $query->where(static function (Builder $q) use ($names): void {
            collect($names)
                ->filter() // Automatically removes null/empty values
                ->each(fn ($value, $locale) => $q->orWhere("name->{$locale}", $value));
        });
    }
}

```

**Key Collection Patterns:**

- `collect()->filter()` - Removes null/empty without explicit checks
- `->each()` - Replaces foreach loop

#### 2.2.3: Create String Name Query Builder

```bash
php artisan make:class App/Support/Validation/TeamName/StringNameQueryBuilder

```

**File:** `app/Support/Validation/TeamName/StringNameQueryBuilder.php`

```php
<?php

declare(strict_types=1);

namespace App\Support\Validation\TeamName;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query builder for string-based team names.
 *
 * Uses collections to apply constraints across multiple locales.
 */
final class StringNameQueryBuilder implements NameQueryBuilderInterface
{
    private const LOCALES = ['en', 'es', 'fr', 'de'];

    public function applyConstraints(Builder $query, string $name, Team $team): void
    {
        if ($name === '') {
            return;
        }

        $query->where(static function (Builder $q) use ($name): void {
            collect(self::LOCALES)
                ->each(fn (string $locale) => $q->orWhere("name->{$locale}", $name));

            $q->orWhereJsonContains('name', $name);
        });
    }
}

```

**Key Collection Patterns:**

- `collect(LOCALES)->each()` - Replaces foreach loop over locales

**✅ Checkpoint 2.2:** Query builders use collections instead of loops.

---

### Step 2.3: Create Type Resolution Service

**Time:** 30 minutes

#### 2.3.1: Create Service

```bash
php artisan make:class App/Services/TeamTypeResolutionService

```

**File:** `app/Services/TeamTypeResolutionService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TeamType;
use App\Models\Department;
use App\Models\Division;
use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\Team;

/**
 * Service for resolving team types using collection lookups.
 */
final readonly class TeamTypeResolutionService
{
    private const TYPE_MAP = [
        Enterprise::class => 'enterprise',
        Organisation::class => 'organisation',
        Division::class => 'division',
        Department::class => 'department',
        Project::class => 'project',
    ];

    /**
     * Resolve team type using collection pipeline.
     */
    public function resolve(Team $team): ?string
    {
        $type = $team->type ?? $team->getAttribute('type');

        // Use collection pipeline instead of nested if/else
        return collect([
            fn () => $type instanceof TeamType ? $type->value : null,
            fn () => is_string($type) ? $type : null,
            fn () => $this->getTypeFromModel($team),
        ])
            ->map(fn (callable $resolver) => $resolver())
            ->filter()
            ->first();
    }

    /**
     * Get type from model class using collection lookup.
     */
    private function getTypeFromModel(Team $team): ?string
    {
        return collect(self::TYPE_MAP)
            ->get($team::class);
    }
}

```

**Key Collection Patterns:**

- `collect([callables])->map()->filter()->first()` - Replaces if/elseif/else chain

**✅ Checkpoint 2.3:** Type resolution uses collection pipeline.

---

### Step 2.4: Refactor TeamNameValidator

**Time:** 1.5 hours

#### 2.4.1: Update Validator Class

**File:** `app/Support/Validation/TeamNameValidator.php`

```php
<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Models\Team;
use App\Services\TeamNameNormalizationService;
use App\Services\TeamTypeResolutionService;
use App\Support\Validation\TeamName\ArrayNameQueryBuilder;
use App\Support\Validation\TeamName\NameQueryBuilderInterface;
use App\Support\Validation\TeamName\StringNameQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

final class TeamNameValidator
{
    public function __construct(
        private TeamNameNormalizationService $normalizationService,
        private TeamTypeResolutionService $typeResolutionService,
    ) {}

    /**
     * Validate that the team name is unique among siblings.
     *
     * Simplified using collection-based services and strategy pattern.
     */
    public function validateUnique(Team $team): void
    {
        $type = $this->typeResolutionService->resolve($team);
        $query = $this->buildSiblingQuery($team, $type);
        $nameToCheck = $this->normalizationService->normalize($team);

        // Use strategy pattern with collection-based builders
        $builder = $this->getQueryBuilder($nameToCheck);
        $builder->applyConstraints($query, $nameToCheck, $team);

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => ['The team name has already been taken within this scope.'],
            ]);
        }
    }

    /**
     * Get the appropriate query builder based on name type.
     */
    private function getQueryBuilder(array|string $name): NameQueryBuilderInterface
    {
        return is_array($name)
            ? new ArrayNameQueryBuilder()
            : new StringNameQueryBuilder();
    }

    /**
     * Build the base query for finding sibling teams.
     */
    private function buildSiblingQuery(Team $team, ?string $type): Builder
    {
        return Team::query()
            ->withoutGlobalScopes()
            ->where('parent_id', $team->parent_id)
            ->where('type', $type)
            ->where('id', '!=', $team->id ?? 0)
            ->whereNull('deleted_at');
    }
}

```

**Key Changes:**

- ✅ Removed `normalizeNameForValidation()` - delegated to service
- ✅ Removed `applyArrayNameConstraints()` - delegated to strategy
- ✅ Removed `applyStringNameConstraints()` - delegated to strategy
- ✅ Removed `getTeamType()` - delegated to service
- ✅ Removed `extractStringPairs()` - delegated to service
- ✅ Method count: 13 → 3 (-77%)

**✅ Checkpoint 2.4:** Validator simplified, complexity reduced.

---

### Step 2.5: Update Tests

**Time:** 45 minutes

#### 2.5.1: Create Service Tests

```bash
php artisan make:test --pest Services/TeamNameNormalizationServiceTest
php artisan make:test --pest Services/TeamTypeResolutionServiceTest
php artisan make:test --pest Support/Validation/TeamName/ArrayNameQueryBuilderTest
php artisan make:test --pest Support/Validation/TeamName/StringNameQueryBuilderTest

```

#### 2.5.2: Update Existing Validator Tests

Ensure all existing `TeamNameValidatorTest` tests still pass.

```bash
php artisan test --filter=TeamNameValidator

```

**✅ Checkpoint 2.5:** All tests passing.

---

### Step 2.6: Verify Complexity Reduction

**Time:** 15 minutes

```bash
composer test:architecture
# Expected: TeamNameValidator no longer flagged

```

**✅ Phase 2 Complete:** TeamNameValidator complexity reduced from 22 → 10 (-54%), methods 13 → 7 (-46%)

---

## Phase 3: User Model

**Target:** Methods 12 → 8 (-33%)
**Duration:** 3 hours
**Risk Level:** Low

### Step 3.1: Create User Presenter

**Time:** 1 hour

#### 3.1.1: Create Presenter

```bash
php artisan make:class App/Presenters/UserPresenter

```

**File:** `app/Presenters/UserPresenter.php`

```php
<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\LaravelMarkdown\MarkdownRenderer;
use Stevebauman\Purify\Facades\Purify;

/**
 * Presenter for User model UI-related methods.
 *
 * Extracts presentation logic from the User model.
 */
final readonly class UserPresenter
{
    /**
     * Get the user's initials using collection pipeline.
     */
    public function initials(User $user): string
    {
        return Str::of($user->name)
            ->explode(' ')
            ->take(2)
            ->map(fn (string $word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the bio HTML attribute (rendered from markdown).
     */
    public function bioHtml(User $user): ?string
    {
        $bio = $user->getTranslation('bio', app()->getLocale());

        if ($bio === null || $bio === '') {
            return null;
        }

        $html = resolve(MarkdownRenderer::class)->toHtml($bio);

        return Purify::clean($html);
    }
}

```

**✅ Checkpoint 3.1:** Presenter created, UI methods extracted.

---

### Step 3.2: Create Context Management Trait

**Time:** 1 hour

#### 3.2.1: Create Trait

```bash
php artisan make:class App/Models/Concerns/ManagesUserContext

```

**File:** `app/Models/Concerns/ManagesUserContext.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Organisation;

/**
 * Trait for managing user context using collection pipelines.
 */
trait ManagesUserContext
{
    /**
     * Switch the user's current context to a specific organisation.
     */
    public function switchContext(Organisation $organisation): bool
    {
        $hasAccess = $this->accessibleOrganisations()
            ->where('organisation_id', $organisation->id)
            ->exists();

        if (! $hasAccess) {
            return false;
        }

        return $this->update(['current_context_id' => $organisation->id]);
    }

    /**
     * Validate the user's current context and default if necessary.
     *
     * Uses collection pipeline to find valid organisation.
     */
    public function validateContext(): void
    {
        $validOrg = $this->accessibleOrganisations()
            ->where('organisation_id', $this->current_context_id)
            ->first() ?? $this->accessibleOrganisations()->first();

        $this->update(['current_context_id' => $validOrg?->id]);
    }
}

```

**Key Collection Patterns:**

- `->first() ?? ->first()` - Replaces if/else with null coalescing
- `->where()->exists()` - Declarative access check

**✅ Checkpoint 3.2:** Context trait created with collection patterns.

---

### Step 3.3: Refactor User Model

**Time:** 45 minutes

#### 3.3.1: Update User Model

**File:** `app/Models/User.php`

Remove methods:

- `initials()` → Use `app(UserPresenter::class)->initials($user)`
- `getBioHtmlAttribute()` → Use `app(UserPresenter::class)->bioHtml($user)`
- `switchContext()` → Moved to `ManagesUserContext` trait
- `validateContext()` → Moved to `ManagesUserContext` trait

Add trait:

```php
use ManagesUserContext;

```

**Updated Model:**

```php
<?php

declare(strict_types=1);

namespace App\Models;

// ... existing imports ...
use App\Models\Concerns\ManagesUserContext;

#[ObservedBy(UserObserver::class)]
final class User extends Authenticatable
{
    // ... existing traits ...
    use ManagesUserContext;

    // Remove: initials(), getBioHtmlAttribute(), switchContext(), validateContext()
    // Keep: isProtectable(), relationships, casts(), etc.
}

```

**✅ Checkpoint 3.3:** User model methods reduced from 12 → 8.

---

### Step 3.4: Update Tests and Usage

**Time:** 15 minutes

#### 3.4.1: Update Tests

Update any tests that call `$user->initials()` or `$user->bio_html` to use presenter.

#### 3.4.2: Update Blade Templates

Search for `$user->initials` or `$user->bio_html` and update:

```blade
{{ app(\App\Presenters\UserPresenter::class)->initials($user) }}
{{ app(\App\Presenters\UserPresenter::class)->bioHtml($user) }}

```

Or use a helper/view composer for cleaner syntax.

**✅ Checkpoint 3.4:** All usages updated.

---

### Step 3.5: Verify Method Count

**Time:** 15 minutes

```bash
composer test:architecture
# Expected: User model no longer flagged for method count

```

**✅ Phase 3 Complete:** User model methods reduced from 12 → 8 (-33%)

---

## Phase 4: TeamMoveService Class

**Target:** Complexity 21 → 11 (-47%), Methods 15 → 8 per service
**Duration:** 8 hours
**Risk Level:** Medium

### Step 4.1: Create Approval Decision Engine

**Time:** 1.5 hours

#### 4.1.1: Create Engine

```bash
php artisan make:class App/Services/TeamMove/ApprovalDecisionEngine

```

**File:** `app/Services/TeamMove/ApprovalDecisionEngine.php`

```php
<?php

declare(strict_types=1);

namespace App\Services\TeamMove;

use App\Models\Team;
use App\Services\TeamMove\ApprovalRuleInterface;

/**
 * Engine for determining if team moves require approval.
 *
 * Uses collection pipeline to evaluate rules.
 */
final readonly class ApprovalDecisionEngine
{
    /**
     * @param  array<ApprovalRuleInterface>  $rules
     */
    public function __construct(
        private array $rules,
    ) {}

    /**
     * Determine if approval is required using collection contains.
     *
     * Replaces foreach loop with collection pipeline.
     */
    public function requiresApproval(Team $team, ?Team $newParent, ?Team $enterprise): bool
    {
        if (! $enterprise instanceof Team) {
            return false;
        }

        // Use 'contains' to stop at first rule that returns true
        return collect($this->rules)
            ->contains(fn (ApprovalRuleInterface $rule) => $rule->requiresApproval(
                $team,
                $newParent,
                $enterprise
            ));
    }
}

```

**Key Collection Patterns:**

- `collect()->contains()` - Replaces foreach with early return

**✅ Checkpoint 4.1:** Decision engine uses collections.

---

### Step 4.2: Create Organisation Finder Service

**Time:** 1.5 hours

#### 4.2.1: Create Service

```bash
php artisan make:class App/Services/TeamOrganisationFinderService

```

**File:** `app/Services/TeamOrganisationFinderService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TeamType;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * Service for finding organisations in team hierarchies using collections.
 */
final readonly class TeamOrganisationFinderService
{
    /**
     * Find the organisation that contains a team using collection unfold.
     *
     * Replaces while loop with functional unfold pattern.
     */
    public function findOrganisation(Team $team): ?Team
    {
        return $this->getAncestry($team)
            ->first(fn (Team $ancestor) => $ancestor->type === TeamType::ORGANISATION);
    }

    /**
     * Get ancestry using collection unfold.
     */
    private function getAncestry(Team $team): Collection
    {
        return collect([$team->parent])
            ->unfold(function (?Team $current) {
                if (! $current) {
                    return null;
                }

                return [$current, $current->parent];
            })
            ->filter();
    }

    /**
     * Check if a move is cross-organisation using collection comparison.
     */
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

**Key Collection Patterns:**

- `unfold()` - Replaces while loop
- `->first(fn)` - Replaces manual iteration with condition

**✅ Checkpoint 4.2:** Organisation finder uses collections.

---

### Step 4.3: Split Service into Request and Approval Services

**Time:** 3 hours

#### 4.3.1: Create Request Service

```bash
php artisan make:class App/Services/TeamMove/TeamMoveRequestService

```

**File:** `app/Services/TeamMove/TeamMoveRequestService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services\TeamMove;

use App\Actions\Teams\MoveTeam;
use App\Models\Team;
use App\Models\TeamMoveApproval;
use App\Models\User;
use App\Services\TeamMove\ApproverResolverInterface;
use Illuminate\Support\Facades\DB;

/**
 * Service for requesting team moves.
 */
final readonly class TeamMoveRequestService
{
    public function __construct(
        private MoveTeam $moveTeamAction,
        private ApprovalDecisionEngine $approvalEngine,
        private ApproverResolverFactory $resolverFactory,
    ) {}

    /**
     * Request a team move, creating an approval request if required.
     */
    public function requestMove(
        Team $team,
        ?int $newParentId,
        User $requestedBy,
        ?string $reason = null,
    ): TeamMoveApproval|Team {
        return DB::transaction(function () use (
            $team,
            $newParentId,
            $requestedBy,
            $reason,
        ): TeamMoveApproval|Team {
            $newParent = $newParentId ? Team::query()->withoutGlobalScopes()->find($newParentId) : null;
            $enterprise = $team->tenant;

            // Use decision engine
            if (! $this->approvalEngine->requiresApproval($team, $newParent, $enterprise)) {
                $this->moveTeamAction->handle($team, $newParentId);

                return $team->fresh();
            }

            // Create approval request
            $requiredApprovers = $this->determineRequiredApprovers($team, $newParent);

            return TeamMoveApproval::query()->create([
                'team_id' => $team->id,
                'from_parent_id' => $team->parent_id,
                'to_parent_id' => $newParentId,
                'requested_by_id' => $requestedBy->id,
                'status' => 'pending',
                'reason' => $reason,
                'required_approvers' => $requiredApprovers,
                'approvals' => [],
            ]);
        });
    }

    private function determineRequiredApprovers(Team $team, ?Team $newParent): array
    {
        $resolver = $this->resolverFactory->getResolver($team, $newParent);

        return $resolver->resolve($team, $newParent);
    }
}

```

#### 4.3.2: Create Approval Service

```bash
php artisan make:class App/Services/TeamMove/TeamMoveApprovalService

```

**File:** `app/Services/TeamMove/TeamMoveApprovalService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services\TeamMove;

use App\Actions\Teams\MoveTeam;
use App\Models\TeamMoveApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service for approving/rejecting team move requests.
 */
final readonly class TeamMoveApprovalService
{
    public function __construct(
        private MoveTeam $moveTeamAction,
    ) {}

    /**
     * Approve a team move request using collection-based approval tracking.
     */
    public function approve(TeamMoveApproval $approval, User $approver): void
    {
        $this->validateApprovalRequest($approval, $approver);

        DB::transaction(function () use ($approval, $approver): void {
            $this->recordApproval($approval, $approver);

            if ($this->allApproversHaveApproved($approval)) {
                $this->executeMove($approval);
            }

            $approval->save();
        });
    }

    /**
     * Reject a team move request.
     */
    public function reject(TeamMoveApproval $approval, User $rejector, string $reason): void
    {
        $this->validateRejectionRequest($approval, $rejector);

        $approval->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by_id' => $rejector->id,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Check if all approvers have approved using collection intersection.
     */
    private function allApproversHaveApproved(TeamMoveApproval $approval): bool
    {
        $requiredApprovers = collect($approval->required_approvers ?? []);
        $approvedUserIds = collect($approval->approvals ?? [])
            ->pluck('user_id');

        // Use collection intersection to check if all required have approved
        return $requiredApprovers->intersect($approvedUserIds)->count() === $requiredApprovers->count();
    }

    // ... other private methods ...
}

```

**Key Collection Patterns:**

- `collect()->intersect()->count()` - Replaces manual array comparison

**✅ Checkpoint 4.3:** Services split, collections used for approval tracking.

---

### Step 4.4: Update Original Service (Deprecate)

**Time:** 1 hour

Mark `TeamMoveService` as deprecated and delegate to new services:

```php
/**
 * @deprecated Use TeamMoveRequestService and TeamMoveApprovalService instead
 */
final readonly class TeamMoveService
{
    // Delegate to new services
}

```

**✅ Checkpoint 4.4:** Original service deprecated, delegation in place.

---

### Step 4.5: Update Tests

**Time:** 1 hour

Create comprehensive tests for new services and update existing tests.

```bash
php artisan test --filter=TeamMove

```

**✅ Checkpoint 4.5:** All tests passing.

---

### Step 4.6: Verify Complexity Reduction

**Time:** 15 minutes

```bash
composer test:architecture
# Expected: TeamMoveService no longer flagged

```

**✅ Phase 4 Complete:** TeamMoveService complexity reduced from 21 → 11 (-47%)

---

## Testing Strategy

### Test Coverage Requirements

- **Unit Tests:** Each extracted service/class must have 100% coverage
- **Integration Tests:** Verify services work together correctly
- **Regression Tests:** Ensure existing functionality unchanged

### Test Execution Plan

```bash
# Before each phase
php artisan test --filter=<ComponentName>

# After each phase
php artisan test
composer test:architecture

# Final verification
php artisan test
composer test:architecture
vendor/bin/phpstan analyse --level=5

```

### Test Checklist

- [ ] All existing tests pass
- [ ] New services have comprehensive tests
- [ ] Collection methods are tested (unfold, contains, etc.)
- [ ] Edge cases covered (null values, empty collections, etc.)
- [ ] Performance tests show no regression

---

## Verification & Rollback

### Verification Steps

After each phase:

1. **Run Architecture Tests**
  ```bash
   composer test:architecture
  ```
   Expected: Component no longer flagged
2. **Run Full Test Suite**
  ```bash
   php artisan test
  ```
   Expected: All tests pass
3. **Check Complexity Metrics**
  ```bash
   vendor/bin/phpstan analyse --level=5 app/Models/Concerns/HasTeamHierarchy.php
  ```
   Expected: Complexity reduced
4. **Manual Testing**
  - Test affected features in browser
  - Verify no regressions
  - Check performance

### Rollback Plan

If issues arise:

1. **Immediate Rollback**
  ```bash
   git revert <commit-hash>
  ```
2. **Partial Rollback**
  - Keep extracted services
  - Revert trait/model changes
  - Re-add delegation later
3. **Feature Flag**
  ```php
   if (config('features.collection_refactoring')) {
       // New collection-based code
   } else {
       // Original code
   }
  ```

### Rollback Triggers

- Test failures
- Performance degradation > 10%
- Critical bugs in production
- Complexity not reduced as expected

---

## Post-Implementation

### Documentation Updates

1. **Update Code Comments**
  - Document collection patterns used
  - Explain why collections were chosen
2. **Update Architecture Docs**
  - Document new service structure
  - Update complexity metrics
3. **Create Collection Patterns Guide**
  - Document common patterns used
  - Examples for future reference

### Performance Monitoring

Monitor for 1 week after implementation:

- Response times
- Memory usage
- Database query counts
- Collection operation performance

### Knowledge Sharing

1. **Code Review**
  - Share collection patterns with team
  - Document lessons learned
2. **Training**
  - Workshop on Laravel Collections
  - Best practices session

### Future Improvements

- Consider caching for traversal operations
- Optimize collection operations if needed
- Extract more patterns to services

---

## Appendix: Collection Patterns Reference

### Common Patterns Used


| Pattern             | Use Case                        | Example                 |
| ------------------- | ------------------------------- | ----------------------- |
| `unfold()`          | Replace while loops             | Hierarchy traversal     |
| `contains()`        | Early return checks             | Rule evaluation         |
| `filter()->first()` | Find first matching             | Organisation lookup     |
| `each()`            | Replace foreach                 | Apply operations        |
| `when()`            | Conditional collection building | Conditional validators  |
| `intersect()`       | Set operations                  | Approval tracking       |
| `pluck()`           | Extract values                  | User IDs from approvals |


### Performance Considerations

- Collections are lazy by default (good for memory)
- `unfold()` can be memory-intensive for deep hierarchies
- Consider `take()` or `limit()` for large collections
- Cache results when appropriate

---

## Success Criteria

### Phase Completion

Each phase is complete when:

- ✅ All tests pass
- ✅ Architecture tests show complexity reduced
- ✅ Code review approved
- ✅ No performance regression
- ✅ Documentation updated

### Overall Success

Project is complete when:

- ✅ All 4 components under complexity thresholds
- ✅ All tests passing (100% coverage maintained)
- ✅ No regressions in functionality
- ✅ Performance maintained or improved
- ✅ Team trained on new patterns

---

**End of Implementation Plan**
