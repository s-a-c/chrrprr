<?php

declare(strict_types=1);

namespace Tests\Feature\Teams;

use App\Models\Enterprise;
use App\Models\Organisation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class TeamMoveTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_move_organisation_to_another_enterprise(): void
    {
        $enterprise1 = Enterprise::factory()->create(['name' => 'Enterprise 1']);
        $enterprise2 = Enterprise::factory()->create(['name' => 'Enterprise 2']);

        $organisation = Organisation::factory()->create([
            'parent_id' => $enterprise1->id,
            'tenant_id' => $enterprise1->id,
        ]);

        self::assertEquals($enterprise1->id, $organisation->parent_id);
        self::assertEquals($enterprise1->id, $organisation->tenant_id);

        // Move to Enterprise 2
        $organisation->parent_id = $enterprise2->id;
        $organisation->save();

        $organisation->refresh();
        self::assertEquals($enterprise2->id, $organisation->parent_id);
        self::assertEquals($enterprise2->id, $organisation->tenant_id);
    }

    public function test_cannot_move_enterprise_to_have_a_parent(): void
    {
        $enterprise1 = Enterprise::factory()->create();
        $enterprise2 = Enterprise::factory()->create();

        $this->expectException(ValidationException::class);

        $enterprise1->parent_id = $enterprise2->id;
        $enterprise1->save();
    }
}
