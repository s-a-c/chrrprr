<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use App\Enums\UserState;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * @extends Migration
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create the Super Admin role
        $superAdminRole = Role::query()->firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ], [
            'is_key' => true,
        ]);

        // Create the System Administrator user
        $systemAdministrator = User::query()->firstOrCreate([
            'email' => 'system@example.com',
        ], [
            'name' => 'System Administrator',
            'password' => Hash::make('password'),
            'email_verified_at' => Date::now(),
            'remember_token' => Str::random(10),
            'state' => UserState::ACTIVE,
            'status' => UserStatus::OFFLINE,
        ]);

        // Assign the Super Admin role to the System Administrator (global, team_id = 0)
        if (!$systemAdministrator->hasRole('Super Admin')) {
            setPermissionsTeamId(0);
            $systemAdministrator->assignRole($superAdminRole);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Find and remove the role assignment
        $systemAdministrator = User::query()->where('email', 'system@example.com')->first();
        if ($systemAdministrator !== null) {
            setPermissionsTeamId(0);
            $systemAdministrator->removeRole('Super Admin');
        }

        // Optionally delete the user (commented out to preserve data)
        // $systemAdministrator?->delete();
        // Optionally delete the role (commented out to preserve data)
        // Role::where('name', 'Super Admin')->where('guard_name', 'web')->delete();
    }
};
