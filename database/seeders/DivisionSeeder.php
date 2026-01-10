<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Organisation;
use Illuminate\Database\Seeder;

final class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organisations = Organisation::all();

        if ($organisations->isEmpty()) {
            $this->command->warn('No organisations found. Please run OrganisationSeeder first.');
            $this->command->info('(Note: Sole trader enterprises have no organisations by design.)');

            return;
        }

        $totalDivisionsCreated = 0;

        // Create 0-3 divisions per organisation
        foreach ($organisations as $organisation) {
            $count = random_int(0, 3);

            for ($i = 0; $i < $count; $i++) {
                Division::factory()->create([
                    'parent_id' => $organisation->id,
                    'tenant_id' => $organisation->tenant_id,
                ]);
                $totalDivisionsCreated++;
            }
        }

        $this->command->info("Created {$totalDivisionsCreated} divisions across {$organisations->count()} organisations.");
    }
}
