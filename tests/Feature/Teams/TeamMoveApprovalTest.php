<?php

declare(strict_types=1);

namespace Tests\Feature\Teams;

use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\TeamMoveApproval;
use App\Models\User;
use App\Services\TeamMoveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(TeamMoveService::class);
});

it('creates approval request when move requires approval due to descendant threshold', function (): void {
    $enterprise = Enterprise::factory()->create([
        'move_approval_descendant_threshold' => 5,
    ]);
    $org = Organisation::factory()->create(['parent_id' => $enterprise->id, 'tenant_id' => $enterprise->id]);

    // Create enough divisions (Organisation -> Division is valid) to trigger approval threshold
    $division1 = \App\Models\Division::factory()->create(['parent_id' => $org->id, 'tenant_id' => $enterprise->id]);
    $division2 = \App\Models\Division::factory()->create(['parent_id' => $org->id, 'tenant_id' => $enterprise->id]);
    $division3 = \App\Models\Division::factory()->create(['parent_id' => $org->id, 'tenant_id' => $enterprise->id]);
    $division4 = \App\Models\Division::factory()->create(['parent_id' => $org->id, 'tenant_id' => $enterprise->id]);
    $division5 = \App\Models\Division::factory()->create(['parent_id' => $org->id, 'tenant_id' => $enterprise->id]);

    $user = User::factory()->create(['tenant_id' => $enterprise->id]);

    $result = $this->service->requestMove($org, null, $user, 'Reason for move');

    expect($result)->toBeInstanceOf(TeamMoveApproval::class)
        ->and($result->status)->toBe('pending')
        ->and($result->team_id)->toBe($org->id)
        ->and($result->from_parent_id)->toBe($enterprise->id)
        ->and($result->to_parent_id)->toBeNull()
        ->and($result->requested_by_id)->toBe($user->id)
        ->and($result->reason)->toBe('Reason for move');
});

it('executes move directly when approval not required', function (): void {
    $enterprise = Enterprise::factory()->create([
        'move_approval_descendant_threshold' => 10,
    ]);
    $org = Organisation::factory()->create(['parent_id' => $enterprise->id, 'tenant_id' => $enterprise->id]);
    $targetEnterprise = Enterprise::factory()->create();

    $user = User::factory()->create(['tenant_id' => $enterprise->id]);

    $result = $this->service->requestMove($org, $targetEnterprise->id, $user);

    expect($result)->toBeInstanceOf(Organisation::class)
        ->and($result->id)->toBe($org->id)
        ->and($result->parent_id)->toBe($targetEnterprise->id);
});

it('creates approval request for cross-organisation move', function (): void {
    $enterprise = Enterprise::factory()->create([
        'move_approval_require_cross_org' => true,
    ]);
    $org1 = Organisation::factory()->create(['parent_id' => $enterprise->id, 'tenant_id' => $enterprise->id]);
    $org2 = Organisation::factory()->create(['parent_id' => $enterprise->id, 'tenant_id' => $enterprise->id]);
    $division = \App\Models\Division::factory()->create(['parent_id' => $org1->id, 'tenant_id' => $enterprise->id]);

    $user = User::factory()->create(['tenant_id' => $enterprise->id]);

    $result = $this->service->requestMove($division, $org2->id, $user);

    expect($result)->toBeInstanceOf(TeamMoveApproval::class)
        ->and($result->status)->toBe('pending');
});

it('approves move request when all required approvers approve', function (): void {
    $enterprise = Enterprise::factory()->create([
        'move_approval_require_cross_org' => true,
    ]);
    $org1 = Organisation::factory()->create(['parent_id' => $enterprise->id, 'tenant_id' => $enterprise->id]);
    $org2 = Organisation::factory()->create(['parent_id' => $enterprise->id, 'tenant_id' => $enterprise->id]);
    $division = \App\Models\Division::factory()->create(['parent_id' => $org1->id, 'tenant_id' => $enterprise->id]);

    $requester = User::factory()->create(['tenant_id' => $enterprise->id]);
    $approver1 = User::factory()->create(['tenant_id' => $enterprise->id]);
    $approver2 = User::factory()->create(['tenant_id' => $enterprise->id]);

    // Create organisation_admin role and assign to approvers
    Role::firstOrCreate(['name' => 'organisation_admin', 'guard_name' => 'web']);

    $previousTeamId = getPermissionsTeamId();
    setPermissionsTeamId($org1->id);
    $approver1->assignRole('organisation_admin');
    setPermissionsTeamId($org2->id);
    $approver2->assignRole('organisation_admin');
    setPermissionsTeamId($previousTeamId);

    $approval = $this->service->requestMove($division, $org2->id, $requester);
    expect($approval)->toBeInstanceOf(TeamMoveApproval::class);

    // First approval
    $this->service->approve($approval, $approver1);
    $approval->refresh();
    expect($approval->status)->toBe('pending'); // Still pending, waiting for second approval

    // Second approval - should execute the move
    $this->service->approve($approval, $approver2);
    $approval->refresh();
    expect($approval->status)->toBe('approved')
        ->and($approval->approved_at)->not->toBeNull();

    // Verify move was executed
    $division->refresh();
    expect($division->parent_id)->toBe($org2->id);
});

it('rejects move request', function (): void {
    $enterprise = Enterprise::factory()->create([
        'move_approval_require_cross_org' => true,
    ]);
    $org1 = Organisation::factory()->create(['parent_id' => $enterprise->id, 'tenant_id' => $enterprise->id]);
    $org2 = Organisation::factory()->create(['parent_id' => $enterprise->id, 'tenant_id' => $enterprise->id]);
    $division = \App\Models\Division::factory()->create(['parent_id' => $org1->id, 'tenant_id' => $enterprise->id]);

    $requester = User::factory()->create(['tenant_id' => $enterprise->id]);
    $approver = User::factory()->create(['tenant_id' => $enterprise->id]);

    Role::firstOrCreate(['name' => 'organisation_admin', 'guard_name' => 'web']);

    $previousTeamId = getPermissionsTeamId();
    setPermissionsTeamId($org1->id);
    $approver->assignRole('organisation_admin');
    setPermissionsTeamId($previousTeamId);

    $approval = $this->service->requestMove($division, $org2->id, $requester);
    expect($approval)->toBeInstanceOf(TeamMoveApproval::class);

    $this->service->reject($approval, $approver, 'Not a good idea');

    $approval->refresh();
    expect($approval->status)->toBe('rejected')
        ->and($approval->rejected_at)->not->toBeNull()
        ->and($approval->rejected_by_id)->toBe($approver->id)
        ->and($approval->rejection_reason)->toBe('Not a good idea');

    // Verify move was NOT executed
    $division->refresh();
    expect($division->parent_id)->toBe($org1->id);
});

it('throws exception when non-authorized user tries to approve', function (): void {
    $enterprise = Enterprise::factory()->create([
        'move_approval_require_cross_org' => true,
    ]);
    $org1 = Organisation::factory()->create(['parent_id' => $enterprise->id, 'tenant_id' => $enterprise->id]);
    $org2 = Organisation::factory()->create(['parent_id' => $enterprise->id, 'tenant_id' => $enterprise->id]);
    $division = \App\Models\Division::factory()->create(['parent_id' => $org1->id, 'tenant_id' => $enterprise->id]);

    $requester = User::factory()->create(['tenant_id' => $enterprise->id]);
    $unauthorizedUser = User::factory()->create(['tenant_id' => $enterprise->id]);

    $approval = $this->service->requestMove($division, $org2->id, $requester);
    expect($approval)->toBeInstanceOf(TeamMoveApproval::class);

    expect(fn () => $this->service->approve($approval, $unauthorizedUser))
        ->toThrow(ValidationException::class);
});

it('creates approval request when depth change exceeds threshold', function (): void {
    $enterprise = Enterprise::factory()->create([
        'move_approval_depth_change_threshold' => 2,
    ]);
    $org = Organisation::factory()->create(['parent_id' => $enterprise->id, 'tenant_id' => $enterprise->id]);
    $division = \App\Models\Division::factory()->create(['parent_id' => $org->id, 'tenant_id' => $enterprise->id]);
    $dept = \App\Models\Department::factory()->create(['parent_id' => $division->id, 'tenant_id' => $enterprise->id]);
    // dept is at depth 3, moving to enterprise (depth 1) would change by 2

    $user = User::factory()->create(['tenant_id' => $enterprise->id]);

    $result = $this->service->requestMove($dept, $enterprise->id, $user);

    expect($result)->toBeInstanceOf(TeamMoveApproval::class);
});
