<?php

declare(strict_types=1);

use App\Models\Domain;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamMoveApproval;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

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

    $results = User::fuzzySearch('Jon')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($user->id);
})->group('arch', 'schema', 'search');

test('user model supports full-text search', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This test requires PostgreSQL');
    }

    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $results = User::fullTextSearch('john')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($user->id);
})->group('arch', 'schema', 'search');

test('team model supports fuzzy search', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This test requires PostgreSQL');
    }

    $team = Team::factory()->create([
        'name' => ['en' => 'Engineering Team'],
    ]);

    $results = Team::fuzzySearch('Enginering')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($team->id);
})->group('arch', 'schema', 'search');

test('team model supports full-text search', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This test requires PostgreSQL');
    }

    $team = Team::factory()->create([
        'name' => ['en' => 'Engineering Team'],
        'bio' => ['en' => 'We build amazing software'],
    ]);

    $results = Team::fullTextSearch('engineering')->get();

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
    $team = Team::factory()->create([
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
    $user->assignRole($role);

    expect($user->hasRole('test-role'))->toBeTrue();
    expect($user->roles)->toHaveCount(1);
})->group('arch', 'schema', 'spatie');

test('spatie permission package role queries work with schema scoping', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This test requires PostgreSQL');
    }

    $role1 = Role::create([
        'name' => 'admin',
        'guard_name' => 'web',
    ]);

    $role2 = Role::create([
        'name' => 'editor',
        'guard_name' => 'web',
    ]);

    // Test various query methods from Spatie package
    $allRoles = Role::all();
    expect($allRoles)->toHaveCount(2);

    $adminRole = Role::where('name', 'admin')->first();
    expect($adminRole->id)->toBe($role1->id);

    // Test permission assignment (uses pivot table)
    $permission = Spatie\Permission\Models\Permission::create([
        'name' => 'edit-posts',
        'guard_name' => 'web',
    ]);

    $role1->givePermissionTo($permission);
    expect($role1->hasPermissionTo('edit-posts'))->toBeTrue();
})->group('arch', 'schema', 'spatie');
