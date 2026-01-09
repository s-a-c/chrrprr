<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Enterprise;
use App\Models\Organisation;
use Illuminate\Database\Seeder;

final class OrganisationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all enterprises
        $enterprises = Enterprise::all();

        if ($enterprises->isEmpty()) {
            $this->command->warn('No enterprises found. Please run EnterpriseSeeder first.');

            return;
        }

        // Create organisations for each enterprise
        $enterprises->each(static function (Enterprise $enterprise): void {
            Organisation::factory()->count(2)->create([
                'parent_id' => $enterprise->id,
                'tenant_id' => $enterprise->id,
            ]);
        });
    }
}
