<?php

declare(strict_types=1);

namespace Tests\Feature\Teams;

use App\Models\Department;
use App\Models\Division;
use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\Project;
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
        $subDept = Project::factory()->create(['parent_id' => $dept->id]);

        $this->expectException(ValidationException::class);

        $org->parent_id = $subDept->id;
        $org->save();
    }
}
