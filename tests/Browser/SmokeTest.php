<?php

declare(strict_types=1);

use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can visit the homepage', function (): void {
    $page = visit('/');

    assert_no_javascript_errors_except_csp_parser(
        $page->assertSee('Laravel')
    )->assertNoConsoleLogs();
});

it('can visit the login page', function (): void {
    $page = visit('/login');

    assert_no_javascript_errors_except_csp_parser(
        $page->assertSee('Log in to your account')
    )->assertNoConsoleLogs();
});

it('can visit the register page', function (): void {
    $page = visit('/register');

    $page->assertSee('Create an account');
    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('can visit the dashboard when authenticated', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/dashboard');

    $page->assertSee('Dashboard');
    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('can visit the chrrps page when authenticated', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/chrrps');

    $page->assertSee('What\'s on your mind?');
    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('can visit the teams index page when authenticated', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/teams');

    $page->assertSee('Teams');
    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('can visit the teams create page when authenticated', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/teams/create');

    $page->assertSee('Create New Team');
    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('can visit a team edit page when authenticated', function (): void {
    $user = User::factory()->create();
    $team = Enterprise::factory()->create();

    $this->actingAs($user);
    $page = visit("/teams/{$team->ulid}");

    $page->assertSee('Edit Team');
    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('can visit a user show page when authenticated', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($user);
    $page = visit("/users/{$otherUser->ulid}");

    $page->assertSee($otherUser->name);
    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('can visit the teams switch context page when authenticated', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/teams/switch-context');

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('can visit the profile settings page when authenticated', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/settings/profile');

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('can visit the password settings page when authenticated', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/settings/password');

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('can visit the appearance settings page when authenticated', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/settings/appearance');

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('can visit the two-factor settings page when authenticated', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/settings/two-factor');

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('can visit the delete account page when authenticated', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/settings/delete-account');

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});
