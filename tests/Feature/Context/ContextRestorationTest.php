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

    // Grant access to both organisations
    DB::table('user_organisation_access')->insert([
        ['user_id' => $this->user->id, 'organisation_id' => $this->orgA->id, 'assigned_at' => now()],
        ['user_id' => $this->user->id, 'organisation_id' => $this->orgB->id, 'assigned_at' => now()],
    ]);
});

test('context is restored from database on login', function (): void {
    // Set context before logout
    $this->actingAs($this->user);
    $this->user->switchContext($this->orgB);
    expect($this->user->fresh()->current_context_id)->toBe($this->orgB->id);

    auth()->logout();

    // Simulate login by calling the listener directly - context should be restored
    // The listener calls validateContext() which restores the context from database
    $listener = new ContextRestorationListener();
    $loginEvent = new Login('web', $this->user, false);
    $listener->handle($loginEvent);

    // Refresh user to get updated context
    $this->user->refresh();

    // Context should be restored from database
    expect($this->user->current_context_id)->toBe($this->orgB->id);
});

test('context is validated and corrected on login if invalid', function (): void {
    // Set context to an organisation user no longer has access to
    $this->actingAs($this->user);
    $this->user->switchContext($this->orgA);
    expect($this->user->fresh()->current_context_id)->toBe($this->orgA->id);

    // Remove access to Org A
    DB::table('user_organisation_access')
        ->where('user_id', $this->user->id)
        ->where('organisation_id', $this->orgA->id)
        ->delete();

    auth()->logout();

    // Login should trigger context validation via listener
    $listener = new ContextRestorationListener();
    $loginEvent = new Login('web', $this->user, false);
    $listener->handle($loginEvent);

    // Refresh user to get updated context
    $this->user->refresh();

    // Context should be auto-corrected to first accessible organisation
    expect($this->user->current_context_id)->toBe($this->orgB->id);
});

test('context defaults to first accessible organisation if none set', function (): void {
    // Ensure no context is set
    $this->user->update(['current_context_id' => null]);

    auth()->logout();

    // Login should set default context via listener
    $listener = new ContextRestorationListener();
    $loginEvent = new Login('web', $this->user, false);
    $listener->handle($loginEvent);

    // Refresh user to get updated context
    $this->user->refresh();

    // Context should default to first accessible organisation
    expect($this->user->current_context_id)->toBe($this->orgA->id);
});

test('context restoration works with multiple login sessions', function (): void {
    $this->actingAs($this->user);
    $this->user->switchContext($this->orgB);

    // Simulate multiple login/logout cycles
    $listener = new ContextRestorationListener();
    for ($i = 0; $i < 3; $i++) {
        auth()->logout();

        // Fire Login event via listener to trigger context restoration
        $loginEvent = new Login('web', $this->user, false);
        $listener->handle($loginEvent);

        // Refresh user to get updated context
        $this->user->refresh();

        expect($this->user->current_context_id)->toBe($this->orgB->id);
    }
});
