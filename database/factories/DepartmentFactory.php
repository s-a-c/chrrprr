<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TeamStatus;
use App\Models\Department;
use App\States\Team\Active;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<Department>
 */
final class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     *
     * @psalm-return array{name: array{en: string}, state: Active::class, status: App\Enums\TeamStatus::ONLINE, bio: string}
     */
    #[Override]
    public function definition(): array
    {
        return [
            'name' => ['en' => fake()->jobTitle() . ' Department'],
            'state' => Active::class,
            'status' => TeamStatus::ONLINE,
            'bio' => fake()->paragraph(),
        ];
    }
}
