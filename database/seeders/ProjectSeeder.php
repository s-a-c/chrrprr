<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Enterprise;
use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Seeds Project entities as cross-functional (floating) teams.
 *
 * Projects are floating teams - they have no hierarchical parent,
 * but still belong to a tenant (Enterprise).
 */
final class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $enterprises = Enterprise::all();

        if ($enterprises->isEmpty()) {
            $this->command->warn('No enterprises found. Please run EnterpriseSeeder first.');

            return;
        }

        $totalProjectsCreated = 0;

        // Create projects for each enterprise (floating, no parent)
        foreach ($enterprises as $enterprise) {
            // Create 2-10 projects per enterprise
            $count = random_int(2, 10);

            for ($i = 0; $i < $count; $i++) {
                Project::factory()->create([
                    'parent_id' => null, // Floating - no parent
                    'tenant_id' => $enterprise->id,
                ]);
                $totalProjectsCreated++;
            }
        }

        $this->command->info("Created {$totalProjectsCreated} floating projects across {$enterprises->count()} enterprises.");
    }
}
