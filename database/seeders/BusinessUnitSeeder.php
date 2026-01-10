<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BusinessUnit;
use App\Models\Organisation;
use Illuminate\Database\Seeder;

/**
 * Seeds BusinessUnit entities under Organisations.
 *
 * BusinessUnits are hierarchical (level 4), sitting between Organisation (3) and Division (5).
 * They represent strategic profit centers within organisations.
 */
final class BusinessUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organisations = Organisation::all();

        if ($organisations->isEmpty()) {
            $this->command->warn('No organisations found. Please run OrganisationSeeder first.');

            return;
        }

        $totalBUsCreated = 0;

        foreach ($organisations as $organisation) {
            // 30% of organisations have business units
            if (random_int(1, 100) > 30) {
                continue;
            }

            $count = random_int(1, 2);

            for ($i = 0; $i < $count; $i++) {
                BusinessUnit::factory()->create([
                    'parent_id' => $organisation->id,
                    'tenant_id' => $organisation->tenant_id,
                ]);
                $totalBUsCreated++;
            }
        }

        $this->command->info("Created {$totalBUsCreated} business units.");
    }
}
