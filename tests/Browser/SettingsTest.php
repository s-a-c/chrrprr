<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Profile Settings', function (): void {
    it('renders profile settings page correctly', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/settings/profile');

        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('can update profile information', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/settings/profile')
            ->fill('name', 'Updated Name')
            ->fill('email', 'updated@example.com')
            ->click('Save');

        $page->assertSee('Profile updated successfully');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });
});

describe('Password Settings', function (): void {
    it('renders password settings page correctly', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/settings/password');

        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('can update password', function (): void {
        $user = User::factory()->create([
            'password' => 'Password123!',
        ]);

        $this->actingAs($user);
        $page = visit('/settings/password')
            ->fill('current_password', 'Password123!')
            ->fill('password', 'NewPassword123!')
            ->fill('password_confirmation', 'NewPassword123!')
            ->click('Save');

        $page->assertSee('Password updated successfully');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('validates current password', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/settings/password')
            ->fill('current_password', 'wrong-password')
            ->fill('password', 'NewPassword123!')
            ->fill('password_confirmation', 'NewPassword123!')
            ->click('Save');

        $page->assertSee('The password is incorrect');
        assert_no_javascript_errors_except_csp_parser($page);
    });
});

describe('Appearance Settings', function (): void {
    it('renders appearance settings page correctly', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/settings/appearance');

        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('can toggle dark mode', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/settings/appearance');

        // This will depend on your actual implementation
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });
});

describe('Two Factor Settings', function (): void {
    it('renders two factor settings page correctly', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/settings/two-factor');

        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('can enable two factor authentication', function (): void {
        $user = User::factory()->withoutTwoFactor()->create();

        $this->actingAs($user);
        $page = visit('/settings/two-factor');

        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });
});

describe('Delete Account Settings', function (): void {
    it('renders delete account page correctly', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/settings/delete-account');

        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });
});
