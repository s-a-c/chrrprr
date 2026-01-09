<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Division;
use App\Models\Project;
use Illuminate\Database\Seeder;

final class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all departments and divisions (projects can belong to either)
        $departments = Department::all();
        $divisions = Division::all();

        if ($departments->isEmpty() && $divisions->isEmpty()) {
            $this->command->warn('No departments or divisions found. Please run DepartmentSeeder or DivisionSeeder first.');

            return;
        }

        // Create projects for departments
        $departments->each(static function (Department $department): void {
            Project::factory()->count(2)->create([
                'parent_id' => $department->id,
                'tenant_id' => $department->tenant_id,
            ]);
        });

        // Create some projects directly under divisions
        $divisions->take(3)->each(static function (Division $division): void {
            Project::factory()->count(1)->create([
                'parent_id' => $division->id,
                'tenant_id' => $division->tenant_id,
            ]);
        });
    }
}
