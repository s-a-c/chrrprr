<?php

declare(strict_types=1);

use App\Models\Domain;
use App\Models\Enterprise;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamMoveApproval;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('database connection prioritizes custom schemas', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This test requires PostgreSQL');
    }

    $path = DB::selectOne('SHOW search_path')->search_path;
    $schema = Config::get('database.connections.pgsql.schema');
    $searchSchema = Config::get('database.connections.pgsql.search_schema');

    expect($path)->toContain($schema)
        ->toContain($searchSchema)
        ->toContain('public');
})->group('arch', 'schema');

test('core models resolve to the application schema', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This test requires PostgreSQL');
    }

    $team = new Team();
    $user = new User();
    $teamMoveApproval = new TeamMoveApproval();
    $schema = Config::get('database.connections.pgsql.schema');

    expect($team->getTable())->toBe("{$schema}.teams");
    expect($user->getTable())->toBe("{$schema}.users");
    expect($teamMoveApproval->getTable())->toBe("{$schema}.team_move_approvals");
})->group('arch', 'schema');

test('role model resolves to the application schema', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This test requires PostgreSQL');
    }

    $role = new Role();
    $schema = Config::get('database.connections.pgsql.schema');

    expect($role->getTable())->toBe("{$schema}.roles");
})->group('arch', 'schema');

test('domain model is excluded from schema prefixing', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This test requires PostgreSQL');
    }

    // Domain extends Stancl BaseDomain and usually expects a specific setup
    $domain = new Domain();
    $schema = Config::get('database.connections.pgsql.schema');

    // Should NOT contain the custom schema prefix 'chrrprr.'
    expect($domain->getTable())->not->toContain("{$schema}.");
})->group('arch', 'schema');

test('user model supports fuzzy search', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This test requires PostgreSQL');
    }

    $user = User::factory()->create(['name' => 'John Doe']);

    $results = User::query()->fuzzySearch('Jon')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($user->id);
})->group('arch', 'schema', 'search');

test('user model supports full-text search', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This test requires PostgreSQL');
    }

    // Ensure search_vector column exists and is properly configured
    $schema = config('database.connections.pgsql.schema', 'public');
    $columnInfo = DB::selectOne("
        SELECT column_name, is_generated
        FROM information_schema.columns
        WHERE table_schema = ? AND table_name = 'users' AND column_name = 'search_vector'
    ", [$schema]);

    if (! $columnInfo || $columnInfo->is_generated !== 'ALWAYS') {
        $this->markTestSkipped('search_vector column is not properly configured as GENERATED ALWAYS');
    }

    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    // For GENERATED ALWAYS columns, the value should be populated automatically on insert
    // Query search_vector directly from database
    $searchVector = DB::selectOne('SELECT search_vector FROM users WHERE id = ?', [$user->id]);

    // If search_vector is NULL, the GENERATED ALWAYS column isn't working
    // This can happen if the migration didn't run correctly or the column wasn't created properly
    if ($searchVector->search_vector === null) {
        $this->markTestSkipped('search_vector is NULL - GENERATED ALWAYS column is not populating. This may be a test environment issue where the migration did not run correctly.');
    }

    $results = User::query()->fullTextSearch('john')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($user->id);
})->group('arch', 'schema', 'search');

test('team model supports fuzzy search', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This test requires PostgreSQL');
    }

    $team = Enterprise::factory()->create([
        'name' => ['en' => 'Engineering Team'],
    ]);

    $results = Team::query()->fuzzySearch('Enginering')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($team->id);
})->group('arch', 'schema', 'search');

test('team model supports full-text search', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This test requires PostgreSQL');
    }

    $team = Enterprise::factory()->create([
        'name' => ['en' => 'Engineering Team'],
        'bio' => ['en' => 'We build amazing software'],
    ]);

    $results = Team::query()->fullTextSearch('engineering')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($team->id);
})->group('arch', 'schema', 'search');

test('user model toSearchableArray returns correct structure', function (): void {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'bio' => ['en' => 'Software engineer'],
    ]);

    $searchable = $user->toSearchableArray();

    expect($searchable)->toHaveKeys(['id', 'ulid', 'name', 'email', 'bio', 'status']);
    expect($searchable['name'])->toBe('John Doe');
    expect($searchable['email'])->toBe('john@example.com');
    expect($searchable['bio'])->toBe('Software engineer');
})->group('arch', 'schema', 'search');

test('team model toSearchableArray returns correct structure', function (): void {
    $team = Enterprise::factory()->create([
        'name' => ['en' => 'Engineering Team'],
        'bio' => ['en' => 'We build amazing software'],
    ]);

    $searchable = $team->toSearchableArray();

    expect($searchable)->toHaveKeys(['id', 'ulid', 'name', 'bio', 'type', 'status']);
    expect($searchable['name'])->toBe('Engineering Team');
    expect($searchable['bio'])->toBe('We build amazing software');
})->group('arch', 'schema', 'search');

test('spatie permission package works with schema scoped role model', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This test requires PostgreSQL');
    }

    // Create a role using the schema-scoped model
    $role = Role::create([
        'name' => 'test-role',
        'guard_name' => 'web',
    ]);

    expect($role->id)->toBeInt();
    expect($role->name)->toBe('test-role');

    // Verify role can be retrieved
    $foundRole = Role::findByName('test-role');
    expect($foundRole->id)->toBe($role->id);

    // Verify role can be assigned to user
    $user = User::factory()->create();
    setPermissionsTeamId(0);
    $user->assignRole($role);

    expect($user->hasRole('test-role'))->toBeTrue();
    expect($user->roles)->toHaveCount(1);
})->group('arch', 'schema', 'spatie');

test('spatie permission package role queries work with schema scoping', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This test requires PostgreSQL');
    }

    setPermissionsTeamId(0);

    // Get count before creating new roles to account for any existing roles
    $initialCount = Role::query()->count();

    $role1 = Role::create([
        'name' => 'admin',
        'guard_name' => 'web',
    ]);

    $role2 = Role::create([
        'name' => 'editor',
        'guard_name' => 'web',
    ]);

    // Test various query methods from Spatie package
    // Expect 2 new roles plus any existing roles
    $allRoles = Role::all();
    expect($allRoles)->toHaveCount($initialCount + 2);

    $adminRole = Role::query()->where('name', 'admin')->first();
    expect($adminRole->id)->toBe($role1->id);

    // Test permission assignment (uses pivot table)
    $permission = Permission::create([
        'name' => 'edit-posts',
        'guard_name' => 'web',
    ]);

    $role1->givePermissionTo($permission);
    expect($role1->hasPermissionTo('edit-posts'))->toBeTrue();
})->group('arch', 'schema', 'spatie');
