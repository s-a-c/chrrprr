<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TeamStatus;
use App\Models\Enterprise;
use App\Models\Role;
use App\States\Team\Active;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

final class EnterpriseSeeder extends Seeder
{
    /**
     * Enterprise classifications with their names and target counts.
     *
     * @var array<string, array{names: string[], count: int}>
     */
    private const array ENTERPRISE_CONFIGS = [
        'sole_trader' => [
            'names' => [
                'Freelance Design Studio',
                'Independent Consultant',
                'Solo Developer',
                'Personal Brand Agency',
                'Individual Practitioner',
            ],
            'count' => 5,
        ],
        'small' => [
            'names' => [
                'Local Retail Chain',
                'Small Manufacturing Co',
                'Regional Services Ltd',
                'Boutique Marketing Agency',
                'Community Health Clinic',
            ],
            'count' => 5,
        ],
        'medium' => [
            'names' => [
                'Mid-Size Tech Corp',
                'Regional Healthcare Group',
                'Multi-Location Retailer',
                'Digital Innovation Labs',
                'Professional Services Network',
                'Urban Property Management',
                'Coastal Logistics Partners',
                'Metropolitan Education Trust',
                'Industrial Supply Chain Co',
                'Financial Advisory Group',
            ],
            'count' => 10,
        ],
        'large' => [
            'names' => [
                'Global Tech Solutions',
                'International Finance Group',
                'Enterprise Healthcare Partners',
                'Continental Manufacturing Corp',
                'Worldwide Consulting Alliance',
            ],
            'count' => 5,
        ],
    ];

    /**
     * Track which enterprises are sole traders for downstream seeders.
     *
     * @var array<int, string> Enterprise ID => classification
     */
    public static array $enterpriseClassifications = [];

    /**
     * Check if an enterprise is a sole trader.
     */
    public static function isSoleTrader(Enterprise $enterprise): bool
    {
        return (self::$enterpriseClassifications[$enterprise->id] ?? '') === 'sole_trader';
    }

    /**
     * Get the classification for an enterprise.
     */
    public static function getClassification(Enterprise $enterprise): string
    {
        return self::$enterpriseClassifications[$enterprise->id] ?? 'unknown';
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // Clear classifications from previous runs
        self::$enterpriseClassifications = [];

        $totalCreated = 0;

        foreach (self::ENTERPRISE_CONFIGS as $classification => $config) {
            $this->command->info("Creating {$config['count']} {$classification} enterprises...");

            foreach ($config['names'] as $name) {
                $enterprise = $this->createEnterprise($name, $classification);
                self::$enterpriseClassifications[$enterprise->id] = $classification;
                $totalCreated++;
            }
        }

        $this->command->info("Created {$totalCreated} enterprises (5 sole traders, 5 small, 10 medium, 5 large).");
    }

    /**
     * Create a single enterprise with the given name and classification.
     */
    private function createEnterprise(string $name, string $classification): Enterprise
    {
        // Create enterprise using factory with specific name
        $enterprise = Enterprise::factory()->create([
            'name' => ['en' => $name],
            'parent_id' => null, // Enterprises are root-level
            'state' => Active::class,
            'status' => TeamStatus::ONLINE,
        ]);

        // Create the tenant record for Stancl Tenancy (domains FK references tenants table)
        DB::table('tenants')->insertOrIgnore([
            'id' => $enterprise->ulid,
            'created_at' => now(),
            'updated_at' => now(),
            'data' => json_encode(['enterprise_id' => $enterprise->id], JSON_THROW_ON_ERROR),
        ]);

        // Set tenant_id to self after creation (self-referencing tenant)
        $enterprise->update(['tenant_id' => $enterprise->id]);

        // Create all 14 roles for this tenant
        $this->createTenantRoles($enterprise);

        return $enterprise;
    }

    /**
     * Create all 14 standard roles for a tenant.
     */
    private function createTenantRoles(Enterprise $enterprise): void
    {
        setPermissionsTeamId($enterprise->id);

        foreach (LandlordSeeder::STANDARD_ROLES as $roleName => $isKey) {
            Role::query()->firstOrCreate(
                [
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'team_id' => $enterprise->id,
                ],
                [
                    'is_key' => $isKey,
                ]
            );
        }
    }
}
