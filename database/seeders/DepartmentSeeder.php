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
        // Get all divisions
        $divisions = Division::all();

        if ($divisions->isEmpty()) {
            $this->command->warn('No divisions found. Please run DivisionSeeder first.');

            return;
        }

        // Create departments for each division
        $divisions->each(static function (Division $division): void {
            Department::factory()->count(2)->create([
                'parent_id' => $division->id,
                'tenant_id' => $division->tenant_id,
            ]);
        });
    }
}
