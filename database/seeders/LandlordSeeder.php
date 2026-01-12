<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TeamStatus;
use App\Enums\TeamType;
use App\Enums\UserState;
use App\Enums\UserStatus;
use App\Models\Enterprise;
use App\Models\Role;
use App\Models\User;
use App\States\Team\Active;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

final class LandlordSeeder extends Seeder
{
    /**
     * The 14 standard roles to create globally and per tenant.
     *
     * @var array<string, bool> Role name => is_key
     */
    public const array STANDARD_ROLES = [
        'Admin' => true,
        'Deputy' => true,
        'Executive' => true,
        'Owner' => true,
        // Standard roles (is_key = false)
        'Customer' => false,
        'Guest' => false,
        'Host' => false,
        'Manager' => false,
        'Member' => false,
        'Partner' => false,
        'Subscriber' => false,
        'User' => false,
        'Vendor' => false,
        'Visitor' => false,
    ];

    /**
     * Run the database seeds.
     *
     * Creates the landlord enterprise (id=0), global roles, and system admin user.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Create the Landlord Enterprise with id=0
        $landlord = $this->createLandlordEnterprise();

        // 2. Create the landlord domain (main application domain)
        $this->createLandlordDomain($landlord);

        // 3. Create or update the Super Admin role (global)
        $this->createSuperAdminRole();

        // 4. Create all 14 standard roles in global context (team_id=0)
        $this->createGlobalRoles();

        // 5. Ensure system administrator exists and has Super Admin role
        $this->ensureSystemAdministrator();

        $this->command->info('Landlord Enterprise (id=0) created with global roles and system admin.');
    }

    /**
     * Create the landlord enterprise with id=0.
     */
    private function createLandlordEnterprise(): Enterprise
    {
        // Check if landlord already exists
        $existing = Enterprise::query()->where('id', 0)->first();

        if ($existing instanceof Enterprise) {
            $this->command->warn('Landlord Enterprise (id=0) already exists, skipping creation.');

            return $existing;
        }

        // Generate ULID for the landlord
        $ulid = Str::ulid()->toBase32();

        // Use direct DB insert to set id=0 (bypasses auto-increment)
        DB::table('teams')->insert([
            'id' => 0,
            'ulid' => $ulid,
            'name' => json_encode(['en' => 'Landlord'], JSON_THROW_ON_ERROR),
            'slug' => json_encode(['en' => 'landlord'], JSON_THROW_ON_ERROR),
            'type' => TeamType::ENTERPRISE->value,
            'parent_id' => null,
            'state' => Active::class,
            'status' => TeamStatus::ONLINE->value,
            'tenant_id' => $ulid, // Self-referencing tenant
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Also create the tenant record for Stancl Tenancy (domains FK references tenants table)
        DB::table('tenants')->insert([
            'id' => $ulid,
            'created_at' => now(),
            'updated_at' => now(),
            'data' => json_encode(['enterprise_id' => 0], JSON_THROW_ON_ERROR),
        ]);

        // Retrieve the created enterprise
        $landlord = Enterprise::query()->where('id', 0)->firstOrFail();

        $this->command->info("Created Landlord Enterprise with id=0, ULID: {$ulid}");

        return $landlord;
    }

    /**
     * Create the domain for the landlord enterprise (main application domain).
     */
    private function createLandlordDomain(Enterprise $landlord): void
    {
        // Derive domain from application URL (e.g., https://chrrprr.test -> chrrprr.test)
        $appUrl = config('app.url', 'http://localhost');
        $appDomain = parse_url((string) $appUrl, PHP_URL_HOST) ?? 'localhost';

        // Use direct DB insert to avoid Stancl's relationship loading
        // which tries to query teams.id with ULID value
        DB::table('domains')->insertOrIgnore([
            'domain' => $appDomain,
            'tenant_id' => $landlord->ulid,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info("Created domain: {$appDomain}");
    }

    /**
     * Create the Super Admin role in global context.
     */
    private function createSuperAdminRole(): void
    {
        setPermissionsTeamId(0);

        Role::query()->firstOrCreate(
            [
                'name' => 'Super Admin',
                'guard_name' => 'web',
            ],
            [
                'is_key' => true,
                'team_id' => 0,
            ]
        );

        $this->command->info('Created/verified Super Admin role (global, team_id=0).');
    }

    /**
     * Create all 14 standard roles in global context (team_id=0).
     */
    private function createGlobalRoles(): void
    {
        setPermissionsTeamId(0);

        foreach (self::STANDARD_ROLES as $roleName => $isKey) {
            Role::query()->firstOrCreate(
                [
                    'name' => $roleName,
                    'guard_name' => 'web',
                ],
                [
                    'is_key' => $isKey,
                    'team_id' => 0,
                ]
            );
        }

        $this->command->info('Created 14 standard roles in global context (team_id=0).');
    }

    /**
     * Ensure the system administrator user exists and has Super Admin role.
     */
    private function ensureSystemAdministrator(): void
    {
        $systemAdmin = User::query()->firstOrCreate(
            ['email' => 'system@example.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'state' => UserState::ACTIVE,
                'status' => UserStatus::OFFLINE,
                'tenant_id' => null, // Global user, not tied to a tenant
            ]
        );

        // Assign Super Admin role if not already assigned
        if (! $systemAdmin->hasRole('Super Admin')) {
            setPermissionsTeamId(0);
            $systemAdmin->assignRole('Super Admin');
            $this->command->info('Assigned Super Admin role to System Administrator.');
        }
    }
}
