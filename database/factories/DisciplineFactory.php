<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TeamStatus;
use App\Models\Discipline;
use App\States\Team\Active;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<Discipline>
 */
final class DisciplineFactory extends Factory
{
    #[Override]
    public function definition(): array
    {
        $disciplines = [
            'Software Engineering',
            'Data Science',
            'UX Design',
            'DevOps',
            'Security',
            'Architecture',
            'Quality Assurance',
            'Product Management',
        ];

        return [
            'name' => ['en' => fake()->randomElement($disciplines).' '.fake()->numerify('####')],
            'state' => Active::class,
            'status' => TeamStatus::ONLINE,
            'bio' => fake()->optional(0.7)->paragraph(),
        ];
    }
}
