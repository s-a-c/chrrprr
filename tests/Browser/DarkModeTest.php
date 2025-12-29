<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders pages correctly in light mode', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/dashboard');

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('renders pages correctly in dark mode', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/dashboard')
        ->inDarkMode();

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('maintains dark mode preference across pages', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/dashboard');

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();

    $page = visit('/teams')
        ->actingAs($user);

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('renders all components correctly in dark mode', function (): void {
    $user = User::factory()->create();

    $pages = [
        '/dashboard',
        '/chrrps',
        '/teams',
        '/teams/create',
        '/settings/profile',
        '/settings/password',
        '/settings/appearance',
    ];

    foreach ($pages as $path) {
        $this->actingAs($user);
        $page = visit($path);

        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    }
});
