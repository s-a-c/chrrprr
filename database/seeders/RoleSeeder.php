<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

final class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create global Super Admin role (team_id = 0 or null)
        Role::query()->firstOrCreate(
            [
                'name' => 'Super Admin',
                'guard_name' => 'web',
            ],
            [
                'is_key' => true,
                'team_id' => null,
            ]
        );

        // Note: 'executive' and 'deputy' roles are created dynamically when assigned to teams
        // They are team-scoped roles, so we don't create them globally here
        // They will be created automatically by spatie/laravel-permission when first assigned
    }
}
