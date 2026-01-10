<?php

declare(strict_types=1);

namespace Tests\Feature\Teams;

use App\Models\Department;
use App\Models\Division;
use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class TeamMoveCyclePreventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_prevents_moving_team_to_be_its_own_child(): void
    {
        $enterprise = Enterprise::factory()->create();
        $org = Organisation::factory()->create(['parent_id' => $enterprise->id]);
        $division = Division::factory()->create(['parent_id' => $org->id]);
        $dept = Department::factory()->create(['parent_id' => $division->id]);

        $this->expectException(ValidationException::class);

        $org->parent_id = $dept->id;
        $org->save();
    }

    public function test_prevents_moving_team_to_be_its_own_grandchild(): void
    {
        $enterprise = Enterprise::factory()->create();
        $org = Organisation::factory()->create(['parent_id' => $enterprise->id]);
        $division = Division::factory()->create(['parent_id' => $org->id]);
        $dept = Department::factory()->create(['parent_id' => $division->id]);
        // Use Unit instead of Project (Unit is hierarchical, Project is floating)
        $unit = Unit::factory()->create([
            'parent_id' => $dept->id,
            'tenant_id' => $enterprise->id,
        ]);

        $this->expectException(ValidationException::class);

        $org->parent_id = $unit->id;
        $org->save();
    }

    public function test_allows_valid_parent_change(): void
    {
        $enterprise = Enterprise::factory()->create();
        $org1 = Organisation::factory()->create(['parent_id' => $enterprise->id]);
        $org2 = Organisation::factory()->create(['parent_id' => $enterprise->id]);
        $division = Division::factory()->create(['parent_id' => $org1->id]);

        // Move division from org1 to org2 - valid
        $division->parent_id = $org2->id;
        $division->save();

        $division->refresh();
        $this->assertSame($org2->id, $division->parent_id);
    }
}
