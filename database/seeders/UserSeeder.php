<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserState;
use App\Enums\UserStatus;
use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

final class UserSeeder extends Seeder
{
    /**
     * User distribution per enterprise type.
     *
     * @var array<string, array<string, array{min: int, max: int}>>
     */
    private const array USER_DISTRIBUTION = [
        'sole_trader' => [
            'Owner' => ['min' => 1, 'max' => 1],
            'Member' => ['min' => 0, 'max' => 2],
            'Guest' => ['min' => 0, 'max' => 1],
        ],
        'small' => [
            'Owner' => ['min' => 1, 'max' => 1],
            'Executive' => ['min' => 1, 'max' => 1],
            'Admin' => ['min' => 1, 'max' => 2],
            'Manager' => ['min' => 1, 'max' => 2],
            'Member' => ['min' => 3, 'max' => 5],
        ],
        'medium' => [
            'Owner' => ['min' => 1, 'max' => 1],
            'Executive' => ['min' => 1, 'max' => 2],
            'Admin' => ['min' => 2, 'max' => 3],
            'Deputy' => ['min' => 0, 'max' => 1],
            'Manager' => ['min' => 2, 'max' => 4],
            'Member' => ['min' => 5, 'max' => 10],
        ],
        'large' => [
            'Owner' => ['min' => 1, 'max' => 1],
            'Executive' => ['min' => 2, 'max' => 3],
            'Admin' => ['min' => 3, 'max' => 5],
            'Deputy' => ['min' => 1, 'max' => 2],
            'Manager' => ['min' => 4, 'max' => 6],
            'Member' => ['min' => 10, 'max' => 20],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // Ensure System Administrator is configured correctly
        $this->ensureSystemAdministrator();

        // Get all enterprises
        $enterprises = Enterprise::all();

        if ($enterprises->isEmpty()) {
            $this->command->warn('No enterprises found. Skipping user creation. Run EnterpriseSeeder first.');

            return;
        }

        $totalUsersCreated = 0;

        foreach ($enterprises as $enterprise) {
            $classification = EnterpriseSeeder::getClassification($enterprise);

            // If classification is unknown, determine by checking org count
            if ($classification === 'unknown') {
                $classification = $this->inferClassification($enterprise);
            }

            $usersCreated = $this->createUsersForEnterprise($enterprise, $classification);
            $totalUsersCreated += $usersCreated;
        }

        // Create a known test user for the first enterprise
        $this->createTestUser($enterprises->first());

        $this->command->info("Created {$totalUsersCreated} users across {$enterprises->count()} enterprises.");
    }

    /**
     * Ensure the system administrator user exists and has Super Admin role.
     */
    private function ensureSystemAdministrator(): void
    {
        $systemAdmin = User::query()->firstOrCreate(
            ['email' => 'system@example.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'state' => UserState::ACTIVE,
                'status' => UserStatus::OFFLINE,
                'tenant_id' => null,
            ]
        );

        if (! $systemAdmin->hasRole('Super Admin')) {
            setPermissionsTeamId(0);
            $systemAdmin->assignRole('Super Admin');
        }
    }

    /**
     * Create users for an enterprise based on its classification.
     *
     * @return int Number of users created
     */
    private function createUsersForEnterprise(Enterprise $enterprise, string $classification): int
    {
        $distribution = self::USER_DISTRIBUTION[$classification] ?? self::USER_DISTRIBUTION['medium'];
        $usersCreated = 0;

        setPermissionsTeamId($enterprise->id);

        foreach ($distribution as $roleName => $range) {
            $count = random_int($range['min'], $range['max']);

            for ($i = 0; $i < $count; $i++) {
                $user = User::factory()->create([
                    'tenant_id' => $enterprise->id,
                    'current_context_id' => null, // Will be set when orgs are created
                ]);

                $user->assignRole($roleName);
                $usersCreated++;
            }
        }

        return $usersCreated;
    }

    /**
     * Create a known test user for development/testing.
     */
    private function createTestUser(?Enterprise $enterprise): void
    {
        if (! $enterprise instanceof Enterprise) {
            return;
        }

        $testUser = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'state' => UserState::ACTIVE,
                'status' => UserStatus::OFFLINE,
                'tenant_id' => $enterprise->id,
                'current_context_id' => null,
            ]
        );

        if (! $testUser->hasRole('Admin')) {
            setPermissionsTeamId($enterprise->id);
            $testUser->assignRole('Admin');
        }

        $this->command->info("Test user created: test@example.com (Admin of {$enterprise->getTranslation('name', 'en')})");
    }

    /**
     * Infer classification based on enterprise name patterns.
     */
    private function inferClassification(Enterprise $enterprise): string
    {
        $name = (string) $enterprise->getTranslation('name', 'en');

        // Sole trader patterns
        if (str_contains($name, 'Freelance') ||
            str_contains($name, 'Independent') ||
            str_contains($name, 'Solo') ||
            str_contains($name, 'Personal') ||
            str_contains($name, 'Individual')) {
            return 'sole_trader';
        }

        // Large patterns
        if (str_contains($name, 'Global') ||
            str_contains($name, 'International') ||
            str_contains($name, 'Enterprise') ||
            str_contains($name, 'Continental') ||
            str_contains($name, 'Worldwide')) {
            return 'large';
        }

        // Small patterns
        if (str_contains($name, 'Local') ||
            str_contains($name, 'Small') ||
            str_contains($name, 'Regional') ||
            str_contains($name, 'Boutique') ||
            str_contains($name, 'Community')) {
            return 'small';
        }

        return 'medium';
    }
}
