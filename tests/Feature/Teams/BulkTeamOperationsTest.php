<?php

declare(strict_types=1);

namespace Tests\Feature\Teams;

use App\Enums\TeamType;
use App\Http\Controllers\Teams\BulkTeamController;
use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->enterprise = Enterprise::factory()->create([
        'bulk_operation_batch_size' => 500,
    ]);
    $this->user = User::factory()->create(['tenant_id' => $this->enterprise->id]);
    $this->actingAs($this->user);

    // Register route if not already registered
    if (! Route::has('api.teams.bulk')) {
        Route::post('/api/teams/bulk', [BulkTeamController::class, 'store'])->name('api.teams.bulk');
    }
});

it('creates multiple teams in bulk', function (): void {
    $teams = collect(['Team 1', 'Team 2', 'Team 3'])
        ->map(/**
         * @return (mixed|string)[]
         *
         * @psalm-return array{name: 'Team 1'|'Team 2'|'Team 3', type: 'organisation', parent_id: mixed}
         */
            fn (string $name): array => [
                'name' => $name,
                'type' => TeamType::ORGANISATION->value,
                'parent_id' => $this->enterprise->id,
            ])
        ->all();

    $response = $this->postJson('/api/teams/bulk', ['teams' => $teams]);

    $response
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'total' => 3,
            'success_count' => 3,
            'failure_count' => 0,
        ])
        ->assertJsonStructure([
            'success',
            'total',
            'success_count',
            'failure_count',
            'results' => [
                '*' => ['index', 'success', 'team_id', 'team_ulid', 'action'],
            ],
        ]);

    expect(Organisation::query()->count())->toBe(3);
});

it('updates multiple teams in bulk', function (): void {
    $org1 = Organisation::factory()->create([
        'parent_id' => $this->enterprise->id,
        'tenant_id' => $this->enterprise->id,
    ]);
    $org2 = Organisation::factory()->create([
        'parent_id' => $this->enterprise->id,
        'tenant_id' => $this->enterprise->id,
    ]);

    $teams = collect([$org1, $org2])
        ->map(fn (Organisation $org, int $index): array => [
            'id' => $org->id,
            'name' => 'Updated Team '.($index + 1),
            'type' => TeamType::ORGANISATION->value,
            'parent_id' => $this->enterprise->id,
            'lock_version' => $org->lock_version,
        ])
        ->all();

    $response = $this->postJson('/api/teams/bulk', ['teams' => $teams]);

    $response
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'total' => 2,
            'success_count' => 2,
            'failure_count' => 0,
        ]);

    $org1->refresh();
    $org2->refresh();
    expect($org1->getTranslation('name', app()->getLocale()))
        ->toBe('Updated Team 1')
        ->and($org2->getTranslation('name', app()->getLocale()))
        ->toBe('Updated Team 2');
});

it('handles partial success when some teams fail validation', function (): void {
    $org = Organisation::factory()->create([
        'parent_id' => $this->enterprise->id,
        'tenant_id' => $this->enterprise->id,
    ]);

    $teams = collect([
        ['name' => 'Valid Team'],
        ['name' => $org->getTranslation('name', app()->getLocale())], // Duplicate name
        ['name' => 'Another Valid Team'],
    ])
        ->map(fn (array $team): array => array_merge($team, [
            'type' => TeamType::ORGANISATION->value,
            'parent_id' => $this->enterprise->id,
        ]))
        ->all();

    $response = $this->postJson('/api/teams/bulk', ['teams' => $teams]);

    $response
        ->assertStatus(207) // Multi-Status (partial success)
        ->assertJson([
            'success' => true,
            'total' => 3,
            'success_count' => 2,
            'failure_count' => 1,
        ])
        ->assertJsonStructure([
            'results' => [
                '*' => ['index', 'success'],
            ],
        ]);

    // Verify successful teams were created
    $validTeam = Organisation::query()
        ->where('parent_id', $this->enterprise->id)
        ->get()
        ->first(fn ($team): bool => $team->getTranslation('name', app()->getLocale()) === 'Valid Team');
    $anotherTeam = Organisation::query()
        ->where('parent_id', $this->enterprise->id)
        ->get()
        ->first(fn ($team): bool => $team->getTranslation('name', app()->getLocale()) === 'Another Valid Team');
    expect($validTeam)->not->toBeNull()->and($anotherTeam)->not->toBeNull();
});

it('returns error when batch size exceeds enterprise limit', function (): void {
    $this->enterprise->update(['bulk_operation_batch_size' => 5]);
    $this->enterprise->refresh();

    $teams = collect(range(0, 9))
        ->map(/**
         * @return (mixed|string)[]
         *
         * @psalm-return array{name: string, type: 'organisation', parent_id: mixed}
         */
            fn (int $i): array => [
                'name' => "Team {$i}",
                'type' => TeamType::ORGANISATION->value,
                'parent_id' => $this->enterprise->id,
            ])
        ->all();

    $response = $this->postJson('/api/teams/bulk', ['teams' => $teams]);

    $response
        ->assertStatus(422)
        ->assertJson([
            'success' => false,
            'error' => 'Batch size exceeds maximum allowed (5).',
        ]);
});

it('handles mixed create and update operations', function (): void {
    $existingOrg = Organisation::factory()->create([
        'parent_id' => $this->enterprise->id,
        'tenant_id' => $this->enterprise->id,
    ]);

    $teams = collect([
        ['name' => 'New Team 1'],
        [
            'id' => $existingOrg->id,
            'name' => 'Updated Existing Team',
            'lock_version' => $existingOrg->lock_version,
        ],
        ['name' => 'New Team 2'],
    ])
        ->map(fn (array $team): array => array_merge($team, [
            'type' => TeamType::ORGANISATION->value,
            'parent_id' => $this->enterprise->id,
        ]))
        ->all();

    $response = $this->postJson('/api/teams/bulk', ['teams' => $teams]);

    $response
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'total' => 3,
            'success_count' => 3,
            'failure_count' => 0,
        ]);

    // Verify new teams were created
    $newTeam1 = Organisation::query()
        ->where('parent_id', $this->enterprise->id)
        ->get()
        ->first(fn ($team): bool => $team->getTranslation('name', app()->getLocale()) === 'New Team 1');
    $newTeam2 = Organisation::query()
        ->where('parent_id', $this->enterprise->id)
        ->get()
        ->first(fn ($team): bool => $team->getTranslation('name', app()->getLocale()) === 'New Team 2');
    expect($newTeam1)->not->toBeNull()->and($newTeam2)->not->toBeNull();

    // Verify existing team was updated
    $existingOrg->refresh();
    expect($existingOrg->getTranslation('name', app()->getLocale()))->toBe('Updated Existing Team');
});

it('returns failure status when all operations fail', function (): void {
    // Try to create teams with invalid parent (will fail form validation before reaching controller)
    $invalidParentId = 99999; // Non-existent parent
    $teams = collect(['Team 1', 'Team 2'])
        ->map(fn (string $name): array => [
            'name' => $name,
            'type' => TeamType::ORGANISATION->value,
            'parent_id' => $invalidParentId,
        ])
        ->all();

    $response = $this->postJson('/api/teams/bulk', ['teams' => $teams]);

    // Form validation fails with 422 before reaching controller logic
    $response->assertStatus(422);
});
