<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Enterprise;
use App\Models\Sector;
use Illuminate\Database\Seeder;

/**
 * Seeds Sector entities under Enterprises.
 *
 * Sectors are hierarchical (level 2), sitting between Enterprise and Organisation.
 * Only large enterprises typically have sectors (industry verticals).
 */
final class SectorSeeder extends Seeder
{
    /**
     * Sector counts per enterprise classification.
     *
     * @var array<string, array{min: int, max: int}>
     */
    private const array SECTOR_DISTRIBUTION = [
        'sole_trader' => ['min' => 0, 'max' => 0],
        'small' => ['min' => 0, 'max' => 0],
        'medium' => ['min' => 0, 'max' => 1],
        'large' => ['min' => 1, 'max' => 3],
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

        $totalSectorsCreated = 0;

        foreach ($enterprises as $enterprise) {
            $classification = EnterpriseSeeder::getClassification($enterprise);

            if ($classification === 'unknown') {
                $classification = $this->inferClassificationByName($enterprise);
            }

            $distribution = self::SECTOR_DISTRIBUTION[$classification] ?? self::SECTOR_DISTRIBUTION['medium'];
            $count = random_int($distribution['min'], $distribution['max']);

            for ($i = 0; $i < $count; $i++) {
                Sector::factory()->create([
                    'parent_id' => $enterprise->id,
                    'tenant_id' => $enterprise->id,
                ]);
                $totalSectorsCreated++;
            }
        }

        $this->command->info("Created {$totalSectorsCreated} sectors.");
    }

    private function inferClassificationByName(Enterprise $enterprise): string
    {
        $name = (string) $enterprise->getTranslation('name', 'en');

        if (str_contains($name, 'Global') || str_contains($name, 'International')) {
            return 'large';
        }

        return 'medium';
    }
}
