<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Team;
use App\Models\TeamMoveApproval;
use App\Models\User;
use Illuminate\Database\Seeder;

final class TeamMoveApprovalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get teams that can be moved (non-root teams)
        $teams = Team::query()
            ->whereNotNull('parent_id')
            ->with('parent')
            ->get();

        if ($teams->isEmpty()) {
            $this->command->warn('No teams with parents found. Please run team seeders first.');

            return;
        }

        // Get users who can request moves
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');

            return;
        }

        // Create a few move approval requests
        $teams->take(3)->each(static function (Team $team) use ($users): void {
            if ($team->parent === null) {
                return;
            }

            // Find a different parent at the same level
            $potentialNewParent = Team::query()
                ->where('id', '!=', $team->parent_id)
                ->where('tenant_id', $team->tenant_id)
                ->where('type', $team->parent->type)
                ->first();

            if ($potentialNewParent === null) {
                return;
            }

            $status = fake()->randomElement(['pending', 'approved', 'rejected']);
            $rejectedBy = $status === 'rejected' ? $users->random() : null;

            TeamMoveApproval::factory()->create([
                'team_id' => $team->id,
                'from_parent_id' => $team->parent_id,
                'to_parent_id' => $potentialNewParent->id,
                'requested_by_id' => $users->random()->id,
                'status' => $status,
                'reason' => fake()->optional()->sentence(),
                'rejected_by_id' => $rejectedBy?->id,
                'rejection_reason' => $status === 'rejected' ? fake()->sentence() : null,
                'approved_at' => $status === 'approved' ? now() : null,
                'rejected_at' => $status === 'rejected' ? now() : null,
            ]);
        });
    }
}
