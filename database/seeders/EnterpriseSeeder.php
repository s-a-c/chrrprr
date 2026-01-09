<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Enterprise;
use Illuminate\Database\Seeder;

final class EnterpriseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a few enterprises (root-level teams, no parent)
        Enterprise::factory()->count(3)->create([
            'parent_id' => null, // Enterprises are root-level
        ])->each(static function (Enterprise $enterprise): void {
            // Set tenant_id to self after creation (self-referencing tenant)
            if ($enterprise->tenant_id === null) {
                $enterprise->update(['tenant_id' => $enterprise->id]);
            }
        });
    }
}
