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

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->enterprise = Enterprise::factory()->create([
        'bulk_operation_batch_size' => 500,
    ]);
    $this->user = User::factory()->create(['tenant_id' => $this->enterprise->id]);
    $this->actingAs($this->user);

    // Register route if not already registered
    if (! Route::has('api.teams.bulk')) {
        Route::post('/api/teams/bulk', [BulkTeamController::class, 'store'])
            ->name('api.teams.bulk');
    }
});

it('creates multiple teams in bulk', function (): void {
    $teams = [
        ['name' => 'Team 1', 'type' => TeamType::ORGANISATION->value, 'parent_id' => $this->enterprise->id],
        ['name' => 'Team 2', 'type' => TeamType::ORGANISATION->value, 'parent_id' => $this->enterprise->id],
        ['name' => 'Team 3', 'type' => TeamType::ORGANISATION->value, 'parent_id' => $this->enterprise->id],
    ];

    $response = $this->postJson('/api/teams/bulk', ['teams' => $teams]);

    $response->assertStatus(200)
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

    expect(Organisation::count())->toBe(3);
});

it('updates multiple teams in bulk', function (): void {
    $org1 = Organisation::factory()->create(['parent_id' => $this->enterprise->id, 'tenant_id' => $this->enterprise->id]);
    $org2 = Organisation::factory()->create(['parent_id' => $this->enterprise->id, 'tenant_id' => $this->enterprise->id]);

    $teams = [
        ['id' => $org1->id, 'name' => 'Updated Team 1', 'type' => TeamType::ORGANISATION->value, 'parent_id' => $this->enterprise->id, 'lock_version' => $org1->lock_version],
        ['id' => $org2->id, 'name' => 'Updated Team 2', 'type' => TeamType::ORGANISATION->value, 'parent_id' => $this->enterprise->id, 'lock_version' => $org2->lock_version],
    ];

    $response = $this->postJson('/api/teams/bulk', ['teams' => $teams]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'total' => 2,
            'success_count' => 2,
            'failure_count' => 0,
        ]);

    $org1->refresh();
    $org2->refresh();
    expect($org1->getTranslation('name', app()->getLocale()))->toBe('Updated Team 1')
        ->and($org2->getTranslation('name', app()->getLocale()))->toBe('Updated Team 2');
});

it('handles partial success when some teams fail validation', function (): void {
    $org = Organisation::factory()->create(['parent_id' => $this->enterprise->id, 'tenant_id' => $this->enterprise->id]);

    $teams = [
        ['name' => 'Valid Team', 'type' => TeamType::ORGANISATION->value, 'parent_id' => $this->enterprise->id],
        ['name' => $org->getTranslation('name', app()->getLocale()), 'type' => TeamType::ORGANISATION->value, 'parent_id' => $this->enterprise->id], // Duplicate name
        ['name' => 'Another Valid Team', 'type' => TeamType::ORGANISATION->value, 'parent_id' => $this->enterprise->id],
    ];

    $response = $this->postJson('/api/teams/bulk', ['teams' => $teams]);

    $response->assertStatus(207) // Multi-Status (partial success)
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
    expect(Organisation::where('name->en', 'Valid Team')->exists())->toBeTrue()
        ->and(Organisation::where('name->en', 'Another Valid Team')->exists())->toBeTrue();
});

it('returns error when batch size exceeds enterprise limit', function (): void {
    $this->enterprise->update(['bulk_operation_batch_size' => 5]);
    $this->enterprise->refresh();

    $teams = [];
    for ($i = 0; $i < 10; $i++) {
        $teams[] = ['name' => "Team {$i}", 'type' => TeamType::ORGANISATION->value, 'parent_id' => $this->enterprise->id];
    }

    $response = $this->postJson('/api/teams/bulk', ['teams' => $teams]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'error' => 'Batch size exceeds maximum allowed (5).',
        ]);
});

it('handles mixed create and update operations', function (): void {
    $existingOrg = Organisation::factory()->create(['parent_id' => $this->enterprise->id, 'tenant_id' => $this->enterprise->id]);

    $teams = [
        ['name' => 'New Team 1', 'type' => TeamType::ORGANISATION->value, 'parent_id' => $this->enterprise->id],
        ['id' => $existingOrg->id, 'name' => 'Updated Existing Team', 'type' => TeamType::ORGANISATION->value, 'parent_id' => $this->enterprise->id, 'lock_version' => $existingOrg->lock_version],
        ['name' => 'New Team 2', 'type' => TeamType::ORGANISATION->value, 'parent_id' => $this->enterprise->id],
    ];

    $response = $this->postJson('/api/teams/bulk', ['teams' => $teams]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'total' => 3,
            'success_count' => 3,
            'failure_count' => 0,
        ]);

    // Verify new teams were created
    expect(Organisation::where('name->en', 'New Team 1')->exists())->toBeTrue()
        ->and(Organisation::where('name->en', 'New Team 2')->exists())->toBeTrue();

    // Verify existing team was updated
    $existingOrg->refresh();
    expect($existingOrg->getTranslation('name', app()->getLocale()))->toBe('Updated Existing Team');
});

it('returns failure status when all operations fail', function (): void {
    // Try to create teams with invalid parent (will fail form validation before reaching controller)
    $invalidParentId = 99999; // Non-existent parent
    $teams = [
        ['name' => 'Team 1', 'type' => TeamType::ORGANISATION->value, 'parent_id' => $invalidParentId],
        ['name' => 'Team 2', 'type' => TeamType::ORGANISATION->value, 'parent_id' => $invalidParentId],
    ];

    $response = $this->postJson('/api/teams/bulk', ['teams' => $teams]);

    // Form validation fails with 422 before reaching controller logic
    $response->assertStatus(422);
});
