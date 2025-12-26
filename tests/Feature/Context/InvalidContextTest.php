<?php

declare(strict_types=1);

use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->enterprise = Enterprise::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->enterprise->id]);

    $this->orgA = Organisation::factory()->create(['parent_id' => $this->enterprise->id]);
    $this->orgB = Organisation::factory()->create(['parent_id' => $this->enterprise->id]);

    // Grant access to both organisations
    DB::table('user_organisation_access')->insert([
        ['user_id' => $this->user->id, 'organisation_id' => $this->orgA->id, 'assigned_at' => now()],
        ['user_id' => $this->user->id, 'organisation_id' => $this->orgB->id, 'assigned_at' => now()],
    ]);
});

test('switching to inaccessible organisation is rejected', function (): void {
    $this->actingAs($this->user);

    // Create an organisation user doesn't have access to
    $orgC = Organisation::factory()->create(['parent_id' => $this->enterprise->id]);

    // Attempt to switch to inaccessible organisation
    $result = $this->user->switchContext($orgC);

    expect($result)->toBeFalse();
    expect($this->user->fresh()->current_context_id)->not->toBe($orgC->id);
});

test('invalid context is detected when access is revoked', function (): void {
    $this->actingAs($this->user);

    // Set context to Org A
    $this->user->switchContext($this->orgA);
    expect($this->user->fresh()->current_context_id)->toBe($this->orgA->id);

    // Revoke access to Org A
    DB::table('user_organisation_access')
        ->where('user_id', $this->user->id)
        ->where('organisation_id', $this->orgA->id)
        ->delete();

    // Validate context should detect invalid context
    $this->user->validateContext();

    // Context should be corrected to first accessible organisation
    expect($this->user->fresh()->current_context_id)->toBe($this->orgB->id);
});

test('invalid context defaults to null when no accessible organisations', function (): void {
    $this->actingAs($this->user);

    // Set context to Org A
    $this->user->switchContext($this->orgA);

    // Revoke access to all organisations
    DB::table('user_organisation_access')
        ->where('user_id', $this->user->id)
        ->delete();

    // Validate context should set to null
    $this->user->validateContext();

    expect($this->user->fresh()->current_context_id)->toBeNull();
});

test('switching to non-existent organisation is rejected', function (): void {
    $this->actingAs($this->user);

    // Create a non-existent organisation ID
    $nonExistentOrg = new Organisation();
    $nonExistentOrg->id = 99999;

    // Attempt to switch should fail
    $result = $this->user->switchContext($nonExistentOrg);

    expect($result)->toBeFalse();
});

test('invalid context from different enterprise is rejected', function (): void {
    $this->actingAs($this->user);

    // Create organisation in different enterprise with unique name
    $otherEnterprise = Enterprise::factory()->create();
    $otherOrg = Organisation::factory()->create([
        'parent_id' => $otherEnterprise->id,
        'name' => ['en' => 'Other Enterprise Org '.uniqid()],
    ]);

    // Attempt to switch should fail (user doesn't have access)
    $result = $this->user->switchContext($otherOrg);

    expect($result)->toBeFalse();
    expect($this->user->fresh()->current_context_id)->not->toBe($otherOrg->id);
});
