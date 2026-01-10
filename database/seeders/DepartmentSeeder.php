<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Division;
use Illuminate\Database\Seeder;

final class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = Division::all();

        if ($divisions->isEmpty()) {
            $this->command->warn('No divisions found. Please run DivisionSeeder first.');
            $this->command->info('(Note: Sole trader enterprises have no hierarchy by design.)');

            return;
        }

        $totalDepartmentsCreated = 0;

        // Create 0-5 departments per division
        // Note: Departments MUST be under divisions per hierarchy validation rules
        foreach ($divisions as $division) {
            $count = random_int(0, 5);

            for ($i = 0; $i < $count; $i++) {
                Department::factory()->create([
                    'parent_id' => $division->id,
                    'tenant_id' => $division->tenant_id,
                ]);
                $totalDepartmentsCreated++;
            }
        }

        $this->command->info("Created {$totalDepartmentsCreated} departments across {$divisions->count()} divisions.");
    }
}
