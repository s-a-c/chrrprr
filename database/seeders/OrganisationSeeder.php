<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Enterprise;
use App\Models\Organisation;
use Illuminate\Database\Seeder;

final class OrganisationSeeder extends Seeder
{
    /**
     * Organisation counts per enterprise classification.
     *
     * @var array<string, array{min: int, max: int}>
     */
    private const array ORG_DISTRIBUTION = [
        'sole_trader' => ['min' => 0, 'max' => 0], // No orgs for sole traders
        'small' => ['min' => 1, 'max' => 2],
        'medium' => ['min' => 2, 'max' => 4],
        'large' => ['min' => 3, 'max' => 6],
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

        $totalOrgsCreated = 0;
        $soleTraderSkipped = 0;

        foreach ($enterprises as $enterprise) {
            $classification = EnterpriseSeeder::getClassification($enterprise);

            // Infer classification if unknown
            if ($classification === 'unknown') {
                $classification = $this->inferClassificationByName($enterprise);
            }

            // Skip sole traders - they have no organisations
            if ($classification === 'sole_trader') {
                $soleTraderSkipped++;

                continue;
            }

            $distribution = self::ORG_DISTRIBUTION[$classification] ?? self::ORG_DISTRIBUTION['medium'];
            $count = random_int($distribution['min'], $distribution['max']);

            for ($i = 0; $i < $count; $i++) {
                Organisation::factory()->create([
                    'parent_id' => $enterprise->id,
                    'tenant_id' => $enterprise->id,
                ]);
                $totalOrgsCreated++;
            }
        }

        $this->command->info("Created {$totalOrgsCreated} organisations. Skipped {$soleTraderSkipped} sole traders.");
    }

    /**
     * Infer classification from enterprise name patterns.
     */
    private function inferClassificationByName(Enterprise $enterprise): string
    {
        $name = (string) $enterprise->getTranslation('name', 'en');

        // Sole trader patterns
        if (str_contains($name, 'Freelance') ||
            str_contains($name, 'Independent') ||
            str_contains($name, 'Solo') ||
            str_contains($name, 'Personal') ||
            str_contains($name, 'Individual')) {
            return 'sole_trader';
        }

        // Large patterns
        if (str_contains($name, 'Global') ||
            str_contains($name, 'International') ||
            str_contains($name, 'Enterprise') ||
            str_contains($name, 'Continental') ||
            str_contains($name, 'Worldwide')) {
            return 'large';
        }

        // Small patterns
        if (str_contains($name, 'Local') ||
            str_contains($name, 'Small') ||
            str_contains($name, 'Regional') ||
            str_contains($name, 'Boutique') ||
            str_contains($name, 'Community')) {
            return 'small';
        }

        return 'medium';
    }
}
