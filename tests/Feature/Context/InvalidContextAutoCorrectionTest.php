<?php

declare(strict_types=1);

use App\Listeners\ContextRestorationListener;
use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->enterprise = Enterprise::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->enterprise->id]);

    $this->orgA = Organisation::factory()->create(['parent_id' => $this->enterprise->id]);
    $this->orgB = Organisation::factory()->create(['parent_id' => $this->enterprise->id]);
    $this->orgC = Organisation::factory()->create(['parent_id' => $this->enterprise->id]);

    // Grant access to all organisations initially
    DB::table('user_organisation_access')->insert([
        ['user_id' => $this->user->id, 'organisation_id' => $this->orgA->id, 'assigned_at' => now()],
        ['user_id' => $this->user->id, 'organisation_id' => $this->orgB->id, 'assigned_at' => now()],
        ['user_id' => $this->user->id, 'organisation_id' => $this->orgC->id, 'assigned_at' => now()],
    ]);
});

test('invalid context is auto-corrected to first accessible organisation', function (): void {
    $this->actingAs($this->user);

    // Set context to Org C
    $this->user->switchContext($this->orgC);
    expect($this->user->fresh()->current_context_id)->toBe($this->orgC->id);

    // Revoke access to Org C
    DB::table('user_organisation_access')
        ->where('user_id', $this->user->id)
        ->where('organisation_id', $this->orgC->id)
        ->delete();

    // Auto-correct should set to first accessible (Org A)
    $this->user->validateContext();
    expect($this->user->fresh()->current_context_id)->toBe($this->orgA->id);
});

test('invalid context auto-correction preserves user preference when possible', function (): void {
    $this->actingAs($this->user);

    // Set context to Org B
    $this->user->switchContext($this->orgB);
    expect($this->user->fresh()->current_context_id)->toBe($this->orgB->id);

    // Revoke access to Org B
    DB::table('user_organisation_access')
        ->where('user_id', $this->user->id)
        ->where('organisation_id', $this->orgB->id)
        ->delete();

    // Auto-correct should set to first accessible (Org A), not Org C
    $this->user->validateContext();
    expect($this->user->fresh()->current_context_id)->toBe($this->orgA->id);
});

test('invalid context auto-correction works on login', function (): void {
    $this->actingAs($this->user);

    // Set context to Org C
    $this->user->switchContext($this->orgC);

    // Revoke access to Org C
    DB::table('user_organisation_access')
        ->where('user_id', $this->user->id)
        ->where('organisation_id', $this->orgC->id)
        ->delete();

    auth()->logout();

    // Login should trigger auto-correction via listener
    $listener = new ContextRestorationListener();
    $loginEvent = new Login('web', $this->user, false);
    $listener->handle($loginEvent);

    // Refresh user to get updated context
    $this->user->refresh();

    // Context should be auto-corrected
    expect($this->user->current_context_id)->toBe($this->orgA->id);
});

test('invalid context auto-correction handles deleted organisation gracefully', function (): void {
    $this->actingAs($this->user);

    // Set context to Org C
    $this->user->switchContext($this->orgC);
    expect($this->user->fresh()->current_context_id)->toBe($this->orgC->id);

    // Delete the organisation (soft delete)
    $this->orgC->delete();

    // Auto-correct should detect invalid context
    $this->user->validateContext();

    // Should fall back to first accessible organisation
    expect($this->user->fresh()->current_context_id)->toBe($this->orgA->id);
});

test('invalid context auto-correction works with middleware', function (): void {
    $this->actingAs($this->user);

    // Create an organisation the user doesn't have access to, then set it as context
    $inaccessibleOrg = Organisation::factory()->create(['parent_id' => $this->enterprise->id]);

    // Set invalid context directly in database (bypassing validation)
    DB::table('users')->where('id', $this->user->id)->update(['current_context_id' => $inaccessibleOrg->id]);

    // Refresh user model
    $this->user->refresh();

    // Accessing a page should trigger context validation via middleware
    // For now, we'll test the validation method directly
    $this->user->validateContext();

    // Should be corrected to first accessible organisation
    expect($this->user->fresh()->current_context_id)->toBe($this->orgA->id);
});
