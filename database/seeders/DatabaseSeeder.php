<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Execution order is critical for maintaining referential integrity.
     *
     * Hierarchical teams follow level order (Enterprise → Sector → ... → Unit).
     * Cross-functional teams (Discipline, Group, Squad, Project) are floating.
     */
    public function run(): void
    {
        // 1. Landlord first (creates id=0 enterprise, global roles, system admin)
        $this->call(LandlordSeeder::class);

        // 2. Enterprises (25 tenants: 5 sole traders + 20 regular)
        $this->call(EnterpriseSeeder::class);

        // 3. Roles (verifies all enterprises have roles)
        $this->call(RoleSeeder::class);

        // 4. Users (creates users with role assignments per enterprise type)
        $this->call(UserSeeder::class);

        // --- HIERARCHICAL TEAMS (parent-child, level order) ---

        // 5. Sectors (level 2: under enterprises, large orgs only)
        $this->call(SectorSeeder::class);

        // 6. Organisations (level 3: under enterprises or sectors)
        $this->call(OrganisationSeeder::class);

        // 7. Divisions (level 5: under organisations)
        $this->call(DivisionSeeder::class);

        // 8. BusinessUnits (level 4: under divisions, optional)
        $this->call(BusinessUnitSeeder::class);

        // 9. Departments (level 6: under divisions or business units)
        $this->call(DepartmentSeeder::class);

        // 10. Units (level 7: under departments, operational teams)
        $this->call(UnitSeeder::class);

        // --- CROSS-FUNCTIONAL TEAMS (floating, no parent) ---

        // 11. Disciplines (areas of competency)
        $this->call(DisciplineSeeder::class);

        // 12. Groups (feature/topic clusters)
        $this->call(GroupSeeder::class);

        // 13. Squads (execution teams)
        $this->call(SquadSeeder::class);

        // 14. Projects (time-bound initiatives)
        $this->call(ProjectSeeder::class);

        // --- OTHER ---

        // 15. Domains (creates domains for all enterprises)
        $this->call(DomainSeeder::class);

        // 16. Team Move Approvals (needs teams and users)
        $this->call(TeamMoveApprovalSeeder::class);
    }
}
