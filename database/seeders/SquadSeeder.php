<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Enterprise;
use App\Models\Squad;
use Illuminate\Database\Seeder;

/**
 * Seeds Squad entities as cross-functional (floating) teams.
 *
 * Squads represent cross-functional execution teams.
 * They are floating teams - no hierarchical parent, but tenant-scoped.
 */
final class SquadSeeder extends Seeder
{
    /**
     * Squad counts per enterprise classification.
     *
     * @var array<string, array{min: int, max: int}>
     */
    private const array SQUAD_DISTRIBUTION = [
        'sole_trader' => ['min' => 0, 'max' => 1],
        'small' => ['min' => 1, 'max' => 3],
        'medium' => ['min' => 3, 'max' => 6],
        'large' => ['min' => 5, 'max' => 12],
    ];

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

        $totalSquadsCreated = 0;

        foreach ($enterprises as $enterprise) {
            $classification = EnterpriseSeeder::getClassification($enterprise);

            if ($classification === 'unknown') {
                $classification = 'medium';
            }

            $distribution = self::SQUAD_DISTRIBUTION[$classification] ?? self::SQUAD_DISTRIBUTION['medium'];
            $count = random_int($distribution['min'], $distribution['max']);

            for ($i = 0; $i < $count; $i++) {
                Squad::factory()->create([
                    'parent_id' => null, // Floating - no parent
                    'tenant_id' => $enterprise->id,
                ]);
                $totalSquadsCreated++;
            }
        }

        $this->command->info("Created {$totalSquadsCreated} floating squads.");
    }
}
