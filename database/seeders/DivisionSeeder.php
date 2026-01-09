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
        // Get all organisations
        $organisations = Organisation::all();

        if ($organisations->isEmpty()) {
            $this->command->warn('No organisations found. Please run OrganisationSeeder first.');

            return;
        }

        // Create divisions for each organisation
        $organisations->each(static function (Organisation $organisation): void {
            Division::factory()->count(2)->create([
                'parent_id' => $organisation->id,
                'tenant_id' => $organisation->tenant_id,
            ]);
        });
    }
}
