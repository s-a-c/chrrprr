<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TeamStatus;
use App\Models\Squad;
use App\States\Team\Active;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<Squad>
 */
final class SquadFactory extends Factory
{
    #[Override]
    public function definition(): array
    {
        $squadNames = [
            'Alpha', 'Beta', 'Gamma', 'Delta', 'Epsilon',
            'Phoenix', 'Thunder', 'Lightning', 'Storm', 'Blaze',
        ];

        return [
            'name' => ['en' => fake()->randomElement($squadNames).' Squad '.fake()->numerify('####')],
            'state' => Active::class,
            'status' => TeamStatus::ONLINE,
            'bio' => fake()->optional(0.7)->paragraph(),
        ];
    }
}
