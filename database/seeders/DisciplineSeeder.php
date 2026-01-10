<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Discipline;
use App\Models\Enterprise;
use Illuminate\Database\Seeder;

/**
 * Seeds Discipline entities as cross-functional (floating) teams.
 *
 * Disciplines represent areas of competency (e.g., "Engineering", "Design").
 * They are floating teams - no hierarchical parent, but tenant-scoped.
 */
final class DisciplineSeeder extends Seeder
{
    /**
     * Discipline counts per enterprise classification.
     *
     * @var array<string, array{min: int, max: int}>
     */
    private const array DISCIPLINE_DISTRIBUTION = [
        'sole_trader' => ['min' => 1, 'max' => 2],
        'small' => ['min' => 2, 'max' => 4],
        'medium' => ['min' => 3, 'max' => 6],
        'large' => ['min' => 5, 'max' => 10],
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

        $totalDisciplinesCreated = 0;

        foreach ($enterprises as $enterprise) {
            $classification = EnterpriseSeeder::getClassification($enterprise);

            if ($classification === 'unknown') {
                $classification = 'medium';
            }

            $distribution = self::DISCIPLINE_DISTRIBUTION[$classification] ?? self::DISCIPLINE_DISTRIBUTION['medium'];
            $count = random_int($distribution['min'], $distribution['max']);

            for ($i = 0; $i < $count; $i++) {
                Discipline::factory()->create([
                    'parent_id' => null, // Floating - no parent
                    'tenant_id' => $enterprise->id,
                ]);
                $totalDisciplinesCreated++;
            }
        }

        $this->command->info("Created {$totalDisciplinesCreated} floating disciplines.");
    }
}
