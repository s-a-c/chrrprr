<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Team;
use App\Models\TeamMoveApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<TeamMoveApproval>
 */
final class TeamMoveApprovalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'from_parent_id' => static fn (array $attributes) => Team::factory()->create()->id,
            'to_parent_id' => static fn (array $attributes) => Team::factory()->create()->id,
            'requested_by_id' => User::factory(),
            'status' => 'pending',
            'reason' => fake()->optional()->sentence(),
            'required_approvers' => null,
            'approvals' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'rejected_by_id' => null,
            'rejection_reason' => null,
        ];
    }
}
