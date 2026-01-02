<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Domain;
use App\Models\Enterprise;
use Illuminate\Database\Seeder;

final class DomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Note: Domain is part of the tenancy package and may be managed automatically.
     * This seeder creates basic domains for enterprises if needed.
     */
    public function run(): void
    {
        // Get all enterprises
        $enterprises = Enterprise::all();

        if ($enterprises->isEmpty()) {
            $this->command->warn('No enterprises found. Please run EnterpriseSeeder first.');

            return;
        }

        // Create domains for each enterprise
        // Note: Adjust this based on your tenancy configuration
        // Domains typically need to link to Tenant model, not directly to Enterprise
        $enterprises->each(static function (Enterprise $enterprise): void {
            // Only create domain if one doesn't exist for this enterprise
            // Adjust the domain format based on your tenancy setup
            $domainName = str($enterprise->getTranslation('name', 'en'))
                ->slug()
                ->append('.test')
                ->toString();

            Domain::query()->firstOrCreate(
                [
                    'domain' => $domainName,
                ],
                [
                    // Note: tenant_id might need to be the UUID/ID from the Tenant model
                    // Adjust based on your tenancy configuration
                    'tenant_id' => (string) $enterprise->id,
                ]
            );
        });
    }
}
