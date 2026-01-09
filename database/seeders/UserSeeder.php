<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

final class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // Ensure System Administrator is configured correctly (migration creates it initially)
        // This is safe for testing - only updates if user exists, doesn't create if migration hasn't run
        $systemAdmin = User::query()->where('email', 'system@example.com')->first();

        if ($systemAdmin !== null) {
            // Ensure system admin has no tenant_id (global, not tied to an enterprise)
            if ($systemAdmin->tenant_id !== null) {
                $systemAdmin->update(['tenant_id' => null]);
            }

            // Assign Super Admin role globally (team_id = 0) if not already assigned
            if (! $systemAdmin->hasRole('Super Admin')) {
                setPermissionsTeamId(0);
                $systemAdmin->assignRole('Super Admin');
            }
        }

        // Get or create enterprises for tenant assignment
        $enterprises = Enterprise::all();

        if ($enterprises->isEmpty()) {
            $this->command->warn('No enterprises found. Skipping enterprise user creation. Run EnterpriseSeeder first if needed.');

            return;
        }

        // Create regular users for each enterprise
        $enterprises->each(static function (Enterprise $enterprise): void {
            User::factory()->count(5)->create([
                'tenant_id' => $enterprise->id,
            ]);
        });

        // Create a test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $enterprises->first()?->id,
        ]);
    }
}
