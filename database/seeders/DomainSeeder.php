<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Enterprise;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates domains for all enterprises. Note: Landlord domain (chrrprr.test)
     * is already created by LandlordSeeder.
     */
    public function run(): void
    {
        // Get all enterprises except landlord (id=0, already has domain from LandlordSeeder)
        $enterprises = Enterprise::query()->where('id', '>', 0)->get();

        if ($enterprises->isEmpty()) {
            $this->command->warn('No enterprises found. Please run EnterpriseSeeder first.');

            return;
        }

        $domainsCreated = 0;

        foreach ($enterprises as $enterprise) {
            $domainName = str($enterprise->getTranslation('name', 'en'))
                ->slug()
                ->append('.test')
                ->toString();

            // Use direct DB insert to avoid Domain model's relationship loading issues
            // tenant_id references tenants.id which is the ULID
            $inserted = DB::table('domains')->insertOrIgnore([
                'domain' => $domainName,
                'tenant_id' => $enterprise->ulid,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted) {
                $domainsCreated++;
            }
        }

        $this->command->info("Created {$domainsCreated} domains for enterprises.");
    }
}
