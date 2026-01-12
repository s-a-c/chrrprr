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

test('context persists in database after logout', function (): void {
    $this->actingAs($this->user);
    $this->user->switchContext($this->orgA);

    expect($this->user->fresh()->current_context_id)->toBe($this->orgA->id);

    auth()->logout();

    // Context should still be in database
    expect($this->user->fresh()->current_context_id)->toBe($this->orgA->id);
});

test('context is restored from database on login', function (): void {
    $this->actingAs($this->user);
    $this->user->switchContext($this->orgB);

    expect($this->user->fresh()->current_context_id)->toBe($this->orgB->id);

    auth()->logout();

    // Simulate new login session
    $this->actingAs($this->user->fresh());

    // Context should be restored from database
    expect($this->user->fresh()->current_context_id)->toBe($this->orgB->id);
});

test('context persists across multiple sessions', function (): void {
    $this->actingAs($this->user);
    $this->user->switchContext($this->orgA);

    // Simulate multiple logout/login cycles
    for ($i = 0; $i < 3; $i++) {
        auth()->logout();
        $this->actingAs($this->user->fresh());
        expect($this->user->fresh()->current_context_id)->toBe($this->orgA->id);
    }
});

test('context persists when switching between organisations', function (): void {
    $this->actingAs($this->user);

    // Switch to Org A
    $this->user->switchContext($this->orgA);
    expect($this->user->fresh()->current_context_id)->toBe($this->orgA->id);

    auth()->logout();
    $this->actingAs($this->user->fresh());
    expect($this->user->fresh()->current_context_id)->toBe($this->orgA->id);

    // Switch to Org B
    $this->user->switchContext($this->orgB);
    expect($this->user->fresh()->current_context_id)->toBe($this->orgB->id);

    auth()->logout();
    $this->actingAs($this->user->fresh());
    expect($this->user->fresh()->current_context_id)->toBe($this->orgB->id);
});
