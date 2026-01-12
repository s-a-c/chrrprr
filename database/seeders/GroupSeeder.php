<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Enterprise;
use App\Models\Group;
use Illuminate\Database\Seeder;

/**
 * Seeds Group entities as cross-functional (floating) teams.
 *
 * Groups represent feature/topic clusters for collaboration.
 * They are floating teams - no hierarchical parent, but tenant-scoped.
 */
final class GroupSeeder extends Seeder
{
    /**
     * Group counts per enterprise classification.
     *
     * @var array<string, array{min: int, max: int}>
     */
    private const array GROUP_DISTRIBUTION = [
        'sole_trader' => ['min' => 0, 'max' => 1],
        'small' => ['min' => 1, 'max' => 3],
        'medium' => ['min' => 2, 'max' => 5],
        'large' => ['min' => 4, 'max' => 8],
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

        $totalGroupsCreated = 0;

        foreach ($enterprises as $enterprise) {
            $classification = EnterpriseSeeder::getClassification($enterprise);

            if ($classification === 'unknown') {
                $classification = 'medium';
            }

            $distribution = self::GROUP_DISTRIBUTION[$classification] ?? self::GROUP_DISTRIBUTION['medium'];
            $count = random_int($distribution['min'], $distribution['max']);

            for ($i = 0; $i < $count; $i++) {
                Group::factory()->create([
                    'parent_id' => null, // Floating - no parent
                    'tenant_id' => $enterprise->id,
                ]);
                $totalGroupsCreated++;
            }
        }

        $this->command->info("Created {$totalGroupsCreated} floating groups.");
    }
}
