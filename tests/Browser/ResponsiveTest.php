<?php

declare(strict_types=1);

use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders correctly on mobile viewport', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/dashboard')
        ->on()
        ->mobile();

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('renders correctly on tablet viewport', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/dashboard')
        ->on()
        ->tablet();

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('renders correctly on desktop viewport', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/dashboard');

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('renders teams page correctly on mobile', function (): void {
    $user = User::factory()->create();
    Enterprise::factory()->count(1)->create();
    Organisation::factory()->count(1)->create();

    $this->actingAs($user);
    $page = visit('/teams')
        ->on()
        ->mobile();

    $page->assertSee('Teams');
    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('renders forms correctly on mobile', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/teams/create')
        ->on()
        ->mobile();

    $page->assertSee('Create New Team');
    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('renders navigation correctly on mobile', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/dashboard')
        ->on()
        ->mobile();

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('renders correctly on iPhone 14 Pro viewport', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/dashboard')
        ->on()
        ->iPhone14Pro();

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
