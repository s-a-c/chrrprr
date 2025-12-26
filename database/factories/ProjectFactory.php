<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TeamStatus;
use App\Models\Project;
use App\States\Team\Active;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<Project>
 */
final class ProjectFactory extends Factory
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
            'name' => ['en' => fake()->words(3, true) . ' Project'],
            'state' => Active::class,
            'status' => TeamStatus::ONLINE,
            'bio' => fake()->paragraph(),
        ];
    }
}
