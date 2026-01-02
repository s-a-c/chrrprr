<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed in logical order to maintain referential integrity

        // 1. Roles first (needed for user assignments)
        $this->call(RoleSeeder::class);

        // 2. Enterprises (root-level teams, no dependencies)
        $this->call(EnterpriseSeeder::class);

        // 3. Users (need enterprises for tenant_id)
        $this->call(UserSeeder::class);

        // 4. Organisations (need enterprises as parents)
        $this->call(OrganisationSeeder::class);

        // 5. Divisions (need organisations as parents)
        $this->call(DivisionSeeder::class);

        // 6. Departments (need divisions as parents)
        $this->call(DepartmentSeeder::class);

        // 7. Projects (need departments or divisions as parents)
        $this->call(ProjectSeeder::class);

        // 8. Team Move Approvals (need teams and users)
        $this->call(TeamMoveApprovalSeeder::class);

        // 9. Domains (optional, for tenancy - may be managed automatically)
        // Uncomment if you need to seed domains manually
        // $this->call(DomainSeeder::class);
    }
}
