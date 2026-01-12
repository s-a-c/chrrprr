<?php

declare(strict_types=1);

use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Skip all browser tests during mutation testing due to timeout issues
    if (getenv('PEST_MUTATE') === '1') {
        $this->markTestSkipped('Browser tests skipped during mutation testing due to timeout issues');
    }
});

it('renders correctly on mobile viewport', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/dashboard')
        ->resize(375, 667);

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('renders correctly on tablet viewport', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/dashboard')
        ->resize(768, 1024);

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('renders correctly on desktop viewport', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/dashboard');

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('renders teams page correctly on mobile', function (): void {
    $enterprise = Enterprise::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $enterprise->id,
        'current_context_id' => $enterprise->id,
    ]);
    $organisation = Organisation::factory()->create(['parent_id' => $enterprise->id]);

    $user->enterprises()->attach($enterprise);
    $user->accessibleOrganisations()->attach($organisation);

    $this->actingAs($user);
    $page = visit('/teams')
        ->resize(375, 667);

    // $page->assertSee($enterprise->name);
    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('renders forms correctly on mobile', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/teams/create')
        ->resize(375, 667);

    $page->assertSee('Create New Team');
    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('renders navigation correctly on mobile', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/dashboard')
        ->resize(375, 667);

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('renders correctly on iPhone 14 Pro viewport', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/dashboard')
        ->resize(393, 852);

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('renders correctly on custom viewport sizes', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/dashboard')
        ->resize(375, 667); // iPhone SE size

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();

    $this->actingAs($user);
    $page = visit('/dashboard')
        ->resize(1920, 1080); // Desktop size

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});
