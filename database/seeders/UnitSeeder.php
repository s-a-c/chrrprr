<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * Seeds Unit entities under Departments.
 *
 * Units are hierarchical (level 7), representing operational teams
 * within departments. They are the lowest hierarchical level.
 */
final class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = Department::all();

        if ($departments->isEmpty()) {
            $this->command->warn('No departments found. Please run DepartmentSeeder first.');

            return;
        }

        $totalUnitsCreated = 0;

        foreach ($departments as $department) {
            // 40% of departments have units
            if (random_int(1, 100) > 40) {
                continue;
            }

            $count = random_int(1, 3);

            for ($i = 0; $i < $count; $i++) {
                Unit::factory()->create([
                    'parent_id' => $department->id,
                    'tenant_id' => $department->tenant_id,
                ]);
                $totalUnitsCreated++;
            }
        }

        $this->command->info("Created {$totalUnitsCreated} units.");
    }
}
