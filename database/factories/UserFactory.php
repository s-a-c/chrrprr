<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserState;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return (App\Enums\UserState::ACTIVE|App\Enums\UserStatus::OFFLINE|Carbon|null|string)[]
     *
     * @psalm-return array{name: string, email: string, email_verified_at: Carbon, password: string, remember_token: string, two_factor_secret: string, two_factor_recovery_codes: string, two_factor_confirmed_at: Carbon, bio: string, state: App\Enums\UserState::ACTIVE, status: App\Enums\UserStatus::OFFLINE, tenant_id: null, current_context_id: null}
     */
    #[Override]
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => Str::random(10),
            'two_factor_recovery_codes' => Str::random(10),
            'two_factor_confirmed_at' => now(),
            'bio' => ($paragraphCount = random_int(0, 5)) > 0
                ? collect(range(1, $paragraphCount))
                    ->map(fn (): string => implode(' ', fake()->sentences(random_int(3, 7))))
                    ->implode("\n\n")
                : '',
            'state' => UserState::ACTIVE,
            'status' => UserStatus::OFFLINE,
            'tenant_id' => null,
            'current_context_id' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(
            /**
             * @return null[]
             *
             * @psalm-return array{email_verified_at: null}
             */
            fn (array $attributes): array => [
                'email_verified_at' => null,
            ],
        );
    }

    /**
     * Indicate that the model does not have two-factor authentication configured.
     */
    public function withoutTwoFactor(): static
    {
        return $this->state(
            /**
             * @return null[]
             *
             * @psalm-return array{two_factor_secret: null, two_factor_recovery_codes: null, two_factor_confirmed_at: null}
             */
            fn (array $attributes): array => [
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ],
        );
    }
}
