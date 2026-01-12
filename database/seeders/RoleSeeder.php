<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Enterprise;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

final class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates roles for all tenants. Note: LandlordSeeder creates global roles (team_id=0)
     * and EnterpriseSeeder creates tenant-scoped roles for each enterprise.
     *
     * This seeder ensures completeness by verifying all enterprises have roles.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create global Super Admin role (idempotent)
        $this->createGlobalSuperAdmin();

        // Verify all enterprises have roles
        $enterprises = Enterprise::all();

        if ($enterprises->isEmpty()) {
            $this->command->warn('No enterprises found. Roles will be created when EnterpriseSeeder runs.');

            return;
        }

        $rolesCreated = 0;

        foreach ($enterprises as $enterprise) {
            $rolesCreated += $this->ensureTenantRoles($enterprise);
        }

        $this->command->info("Verified/created roles for {$enterprises->count()} enterprises ({$rolesCreated} new roles).");
    }

    /**
     * Create the global Super Admin role.
     */
    private function createGlobalSuperAdmin(): void
    {
        setPermissionsTeamId(0);

        Role::query()->firstOrCreate(
            [
                'name' => 'Super Admin',
                'guard_name' => 'web',
            ],
            [
                'is_key' => true,
                'team_id' => 0,
            ]
        );
    }

    /**
     * Ensure all 14 standard roles exist for a tenant.
     *
     * @return int Number of new roles created
     */
    private function ensureTenantRoles(Enterprise $enterprise): int
    {
        setPermissionsTeamId($enterprise->id);
        $created = 0;

        foreach (LandlordSeeder::STANDARD_ROLES as $roleName => $isKey) {
            $role = Role::query()->firstOrCreate(
                [
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'team_id' => $enterprise->id,
                ],
                [
                    'is_key' => $isKey,
                ]
            );

            if ($role->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }
}
