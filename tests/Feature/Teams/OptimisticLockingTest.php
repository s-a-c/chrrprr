<?php

declare(strict_types=1);

namespace Tests\Feature\Teams;

use App\Exceptions\OptimisticLockingException;
use App\Models\Enterprise;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OptimisticLockingTest extends TestCase
{
    use RefreshDatabase;

    public function test_prevents_updates_from_stale_model_instances(): void
    {
        $enterprise = Enterprise::factory()->create();
        $team = Enterprise::factory()->create([
            'tenant_id' => $enterprise->id,
            'lock_version' => 0,
        ]);

        // Fetch two instances
        $team1 = Team::query()->find($team->id);
        $team2 = Team::query()->find($team->id);

        // Update instance 1
        $team1->setTranslation('name', 'en', 'Updated Name 1');
        $team1->save();

        $this->assertEquals(1, $team1->fresh()->lock_version);
        $this->assertEquals('Updated Name 1', $team1->fresh()->getTranslation('name', 'en'));

        // Attempt to update instance 2 (stale lock_version)
        $this->expectException(OptimisticLockingException::class);

        $team2->setTranslation('name', 'en', 'Updated Name 2');
        $team2->save();
    }

    public function test_increments_lock_version_on_successful_save(): void
    {
        $enterprise = Enterprise::factory()->create();
        $team = Enterprise::factory()->create([
            'tenant_id' => $enterprise->id,
            'lock_version' => 0,
        ]);

        $team->setTranslation('name', 'en', 'New Name');
        $team->save();

        $this->assertEquals(1, $team->lock_version);
        $this->assertEquals(1, $team->fresh()->lock_version);
    }
}
