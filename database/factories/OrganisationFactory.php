<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TeamStatus;
use App\Models\Organisation;
use App\States\Team\Active;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<Organisation>
 */
final class OrganisationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return (App\Enums\TeamStatus::ONLINE|string|string[])[]
     *
     * @psalm-return array{name: array{en: string}, state: Active::class, status: App\Enums\TeamStatus::ONLINE, bio: string}
     */
    #[Override]
    public function definition(): array
    {
        return [
            'name' => ['en' => fake()->company()],
            'state' => Active::class,
            'status' => TeamStatus::ONLINE,
            'bio' => fake()->paragraph(),
        ];
    }
}
