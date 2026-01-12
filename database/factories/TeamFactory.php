<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TeamStatus;
use App\Enums\TeamType;
use App\Models\Team;
use App\States\Team\Active;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<Team>
 */
final class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return (App\Enums\TeamStatus::ONLINE|App\Enums\TeamType::ORGANISATION|string|string[])[]
     *
     * @psalm-return array{type: App\Enums\TeamType::ORGANISATION, name: array{en: string}, state: Active::class, status: App\Enums\TeamStatus::ONLINE, bio: string}
     */
    #[Override]
    public function definition(): array
    {
        return [
            'type' => TeamType::ORGANISATION,
            'name' => ['en' => fake()->company().' '.uniqid()],
            'state' => Active::class,
            'status' => TeamStatus::ONLINE,
            'bio' => ($paragraphCount = random_int(0, 5)) > 0
                ? collect(range(1, $paragraphCount))
                    ->map(fn (): string => implode(' ', fake()->sentences(random_int(3, 7))))
                    ->implode("\n\n")
                : '',
        ];
    }
}
