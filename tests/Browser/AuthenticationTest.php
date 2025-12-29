<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can render the login page', function (): void {
    $page = visit('/login');

    $page->assertSee('Log in to your account')
        ->assertSee('Email address')
        ->assertSee('Password')
        ->assertSee('Remember me')
        ->assertSee('Log in');
    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('can render the register page', function (): void {
    $page = visit('/register');

    $page->assertSee('Create an account')
        ->assertSee('Name')
        ->assertSee('Email address')
        ->assertSee('Password')
        ->assertSee('Confirm password')
        ->assertSee('Create account');
    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('can login with valid credentials', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $page = visit('/login')
        ->fill('email', $user->email)
        ->fill('password', 'password')
        ->click('Log in');

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    $this->assertAuthenticated();
});

it('shows error with invalid login credentials', function (): void {
    $user = User::factory()->create();

    $page = visit('/login')
        ->fill('email', $user->email)
        ->fill('password', 'wrong-password')
        ->click('Log in');

    $page->assertSee('These credentials do not match our records');
    assert_no_javascript_errors_except_csp_parser($page);
    $this->assertGuest();
});

it('can register a new user', function (): void {
    $page = visit('/register')
        ->fill('name', 'John Doe')
        ->fill('email', 'john@example.com')
        ->fill('password', 'password')
        ->fill('password_confirmation', 'password')
        ->click('Create account');

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    $this->assertAuthenticated();

    expect(User::query()->where('email', 'john@example.com')->exists())->toBeTrue();
});

it('validates registration form fields', function (): void {
    $page = visit('/register')
        ->click('Create account');

    $page->assertSee('The name field is required')
        ->assertSee('The email field is required')
        ->assertSee('The password field is required');
    assert_no_javascript_errors_except_csp_parser($page);
});

it('validates password confirmation on registration', function (): void {
    $page = visit('/register')
        ->fill('name', 'John Doe')
        ->fill('email', 'john@example.com')
        ->fill('password', 'password')
        ->fill('password_confirmation', 'different-password')
        ->click('Create account');

    $page->assertSee('The password field confirmation does not match');
    assert_no_javascript_errors_except_csp_parser($page);

    $this->assertGuest();
});

it('can logout when authenticated', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/dashboard')
        ->click('Log out');

    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();

    $this->assertGuest();
});

it('redirects to dashboard after login', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $page = visit('/login')
        ->fill('email', $user->email)
        ->fill('password', 'password')
        ->click('Log in');

    $page->assertPathIs('/dashboard');
    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('can navigate from login to register page', function (): void {
    $page = visit('/login')
        ->click('Sign up');

    $page->assertPathIs('/register')
        ->assertSee('Create an account');
    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});

it('can navigate from register to login page', function (): void {
    $page = visit('/register')
        ->click('Log in');

    $page->assertPathIs('/login')
        ->assertSee('Log in to your account');
    assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
});
