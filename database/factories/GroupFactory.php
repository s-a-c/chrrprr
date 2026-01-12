<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TeamStatus;
use App\Models\Group;
use App\States\Team\Active;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<Group>
 */
final class GroupFactory extends Factory
{
    #[Override]
    public function definition(): array
    {
        return [
            'name' => ['en' => fake()->words(2, true).' Group '.fake()->numerify('####')],
            'state' => Active::class,
            'status' => TeamStatus::ONLINE,
            'bio' => fake()->optional(0.7)->paragraph(),
        ];
    }
}
