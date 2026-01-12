# Addendum: Test Suite Specification for `TeamHierarchyTraversalService`, Name Validation, and Move Approval

This addendum outlines the testing strategy and specific test cases for the newly extracted `TeamHierarchyTraversalService`. The focus is on ensuring the **functional recursion** and **collection-driven traversal** logic are robust, handle edge cases, and perform efficiently within the Laravel ecosystem.

This addendum provides the test suite specifications for the remaining high-priority remediations: the **TeamNameValidator** and the **ApprovalDecisionEngine**. Both suites utilize a functional testing approach to ensure that the collection-based logic and strategy patterns remain reliable and maintainable.

---

## 1. Service Overview

The `TeamHierarchyTraversalService` replaces manual `while` loops with Laravel Collection pipelines. It utilizes `unfold()` to generate a lazy-loaded lineage of a team, significantly reducing cyclomatic complexity.

### Core Logic Under Test

```php
public function getAncestry(Team $team): Collection
{
    return collect([$team->parent])
        ->unfold(fn ($parent) => $parent ? [$parent, $parent->parent] : null)
        ->filter();
}

```

---

## 2. Testing Strategy

We recommend using **Pest PHP** for its expressive syntax, which aligns with the functional nature of the refactored code. The suite focuses on:
* **Correctness:** Validating that the full ancestry path is recovered.
* **Integrity:** Ensuring circular references (if any exist in the DB) do not cause memory exhaustion.
* **Performance:** Minimizing N+1 query issues using appropriate Eager Loading.

---

## 3. Test Suite (Pest PHP)

```php
use App\Models\Team;
use App\Enums\TeamType;
use App\Services\TeamHierarchyTraversalService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->service = new TeamHierarchyTraversalService());

describe('getAncestry()', function () {

    it('returns an empty collection for a root Enterprise team', function () {
        $enterprise = Team::factory()->create(['type' => TeamType::ENTERPRISE, 'parent_id' => null]);

        $ancestry = $this->service->getAncestry($enterprise);

        expect($ancestry)->toBeEmpty();
    });

    it('returns the full path from Project up to Enterprise', function () {
        // Setup: Enterprise -> Organisation -> Division -> Project
        $ent = Team::factory()->create(['type' => TeamType::ENTERPRISE]);
        $org = Team::factory()->create(['type' => TeamType::ORGANISATION, 'parent_id' => $ent->id]);
        $div = Team::factory()->create(['type' => TeamType::DIVISION, 'parent_id' => $org->id]);
        $prj = Team::factory()->create(['type' => TeamType::PROJECT, 'parent_id' => $div->id]);

        $ancestry = $this->service->getAncestry($prj);

        expect($ancestry->pluck('id')->toArray())->toEqual([
            $div->id,
            $org->id,
            $ent->id
        ]);
    });

    it('handles deeply nested hierarchies within collection limits', function () {
        $bottomTeam = Team::factory()->create();
        $current = $bottomTeam;

        // Create 10 levels
        foreach (range(1, 10) as $i) {
            $parent = Team::factory()->create();
            $current->update(['parent_id' => $parent->id]);
            $current = $parent;
        }

        $ancestry = $this->service->getAncestry($bottomTeam);

        expect($ancestry)->toHaveCount(10);
    });
});

describe('findOrganisation()', function () {

    it('identifies the correct organisation in a mixed hierarchy', function () {
        $ent = Team::factory()->create(['type' => TeamType::ENTERPRISE]);
        $org = Team::factory()->create(['type' => TeamType::ORGANISATION, 'parent_id' => $ent->id]);
        $dept = Team::factory()->create(['type' => TeamType::DEPARTMENT, 'parent_id' => $org->id]);

        $result = $this->service->findOrganisation($dept);

        expect($result->id)->toBe($org->id);
    });

    it('returns null if no organisation exists in the lineage', function () {
        $ent = Team::factory()->create(['type' => TeamType::ENTERPRISE]);
        $dept = Team::factory()->create(['type' => TeamType::DEPARTMENT, 'parent_id' => $ent->id]);

        $result = $this->service->findOrganisation($dept);

        expect($result)->toBeNull();
    });
});

describe('Cycle Detection Safety', function () {

    it('does not enter an infinite loop if a self-reference exists', function () {
        $team = Team::factory()->create();
        // Manually force a cycle in the DB for testing robustness
        $team->parent_id = $team->id;
        $team->saveQuietly();

        // The unfold() should be used with a take() or limit if
        // infinite cycles are a data integrity risk.
        $ancestry = $this->service->getAncestry($team)->take(5);

        expect($ancestry->first()->id)->toBe($team->id);
    });
});

```

---

## 4. Key Explanations

### Functional Path Recovery

The use of `pluck('id')->toArray()` in the tests ensures that the order of the ancestry is preserved (Immediate Parent → Grandparent → Root). This is critical for the `ParentTypeValidator` strategy which relies on correct proximity.

### Mocking vs. Database

While these tests use `RefreshDatabase`, the service is designed to be **database-agnostic** if the `parent` relationship is already eager-loaded. For high-performance scenarios, the service can be tested against a collection of models without hitting the database, proving the pure logic of the `unfold` pipeline.

### Refinement Outcome

The implementation of this test suite ensures that the **-52% Complexity Reduction** in the model does not come at the cost of regression errors in the hierarchy logic.

---

## 5. TeamNameValidator Test Suite (Pest PHP)

The goal here is to verify that the **Pipeline Normalization** correctly handles various name formats (JSON vs. String) and that the **Query Builder Strategy** applies the correct database constraints without cross-contaminating sibling scopes.

```php
use App\Models\Team;
use App\Enums\TeamType;
use App\Support\Validation\TeamNameValidator;
use App\Support\Validation\TeamName\ArrayNameQueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->validator = app(TeamNameValidator::class));

describe('validateUnique()', function () {

    it('throws validation exception when a duplicate name exists in the same scope', function () {
        $parent = Team::factory()->create();
        Team::factory()->create([
            'name' => ['en' => 'Engineering'],
            'parent_id' => $parent->id,
            'type' => TeamType::DEPARTMENT
        ]);

        $newTeam = new Team([
            'name' => ['en' => 'Engineering'],
            'parent_id' => $parent->id,
            'type' => TeamType::DEPARTMENT
        ]);

        expect(fn () => $this->validator->validateUnique($newTeam))
            ->toThrow(ValidationException::class);
    });

    it('allows the same name in different sibling scopes', function () {
        $parentA = Team::factory()->create();
        $parentB = Team::factory()->create();

        Team::factory()->create([
            'name' => 'Marketing',
            'parent_id' => $parentA->id
        ]);

        $newTeam = new Team([
            'name' => 'Marketing',
            'parent_id' => $parentB->id
        ]);

        // Should not throw exception
        $this->validator->validateUnique($newTeam);
        expect(true)->toBeTrue();
    });

    it('correctly normalizes and matches legacy string names against JSON structures', function () {
        $parent = Team::factory()->create();
        Team::factory()->create([
            'name' => ['en' => 'Sales'],
            'parent_id' => $parent->id
        ]);

        $newTeam = new Team([
            'name' => 'Sales', // Legacy string format
            'parent_id' => $parent->id
        ]);

        expect(fn () => $this->validator->validateUnique($newTeam))
            ->toThrow(ValidationException::class);
    });
});

```

---

## 6. ApprovalDecisionEngine Test Suite (Pest PHP)

This suite ensures the **Rule Engine** correctly identifies when a move requires an approval workflow. It tests the **functional collection pipeline** that evaluates various business rules (e.g., cross-organisation moves or depth limits).



```php
use App\Models\Team;
use App\Models\User;
use App\Services\TeamMove\ApprovalDecisionEngine;
use App\Services\TeamMove\Rules\CrossOrganisationRule;
use App\Services\TeamMove\Rules\EnterpriseBoundaryRule;

describe('requiresApproval()', function () {

    it('returns false immediately if no enterprise context is provided', function () {
        $engine = new ApprovalDecisionEngine([new CrossOrganisationRule()]);
        $team = Team::factory()->make();

        $result = $engine->requiresApproval($team, null, null);

        expect($result)->toBeFalse();
    });

    it('returns true if any single rule in the collection is satisfied', function () {
        $rule1 = mock(CrossOrganisationRule::class);
        $rule1->shouldReceive('requiresApproval')->andReturn(false);

        $rule2 = mock(EnterpriseBoundaryRule::class);
        $rule2->shouldReceive('requiresApproval')->andReturn(true);

        $engine = new ApprovalDecisionEngine([$rule1, $rule2]);
        $team = Team::factory()->make();
        $enterprise = Team::factory()->make();

        $result = $engine->requiresApproval($team, null, $enterprise);

        // Functional 'contains' should pick up the true from rule2
        expect($result)->toBeTrue();
    });

    it('stops execution at the first rule that requires approval', function () {
        $rule1 = mock(CrossOrganisationRule::class);
        $rule1->shouldReceive('requiresApproval')->once()->andReturn(true);

        // This rule should never be called because rule1 returns true
        $rule2 = mock(EnterpriseBoundaryRule::class);
        $rule2->shouldReceive('requiresApproval')->never();

        $engine = new ApprovalDecisionEngine([$rule1, $rule2]);
        $team = Team::factory()->make();
        $enterprise = Team::factory()->make();

        $engine->requiresApproval($team, null, $enterprise);
    });
});

```

---

## 7. Summary of Refined Testing Outcomes

By implementing these suites alongside the refactored code, we ensure:
* **Declarative Integrity**: We test the *outcome* of the collection pipelines, not the internal loop mechanics.
* **Rule Isolation**: Each validation or approval rule can be tested independently before being aggregated by the engine.
* **Safety**: Edge cases like legacy data (string names) or missing contexts (null enterprises) are explicitly handled.
