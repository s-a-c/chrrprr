<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TeamStatus;
use App\Models\Unit;
use App\States\Team\Active;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<Unit>
 */
final class UnitFactory extends Factory
{
    #[Override]
    public function definition(): array
    {
        return [
            'name' => ['en' => fake()->word().' Unit '.fake()->numerify('####')],
            'state' => Active::class,
            'status' => TeamStatus::ONLINE,
            'bio' => fake()->optional(0.7)->paragraph(),
        ];
    }
}
