<?php

declare(strict_types=1);

use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Flux UI Components', function (): void {
    it('renders buttons correctly', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/teams');

        $page->assertSee('Create Team');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('renders form inputs correctly', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/teams/create');

        $page->assertSee('Team Name');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('renders tables correctly', function (): void {
        $user = User::factory()->create();
        Enterprise::factory()->count(1)->create();
        Organisation::factory()->count(1)->create();

        $this->actingAs($user);
        $page = visit('/teams');

        $page->assertSee('Name')
            ->assertSee('Type')
            ->assertSee('Status')
            ->assertSee('Actions');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('renders badges correctly', function (): void {
        $user = User::factory()->create();
        $team = Enterprise::factory()->create();

        $this->actingAs($user);
        $page = visit('/teams');

        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('renders avatars correctly', function (): void {
        $user = User::factory()->create();
        $team = Enterprise::factory()->create();

        $this->actingAs($user);
        $page = visit('/teams');

        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('renders headings correctly', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/teams');

        $page->assertSee('Teams');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('renders textareas correctly', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/chrrps');

        $page->assertSee('What\'s on your mind?');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('renders select dropdowns correctly', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/teams/create');

        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });
});

describe('Livewire Components', function (): void {
    it('renders logout component', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/dashboard');

        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('renders team list component', function (): void {
        $user = User::factory()->create();
        Enterprise::factory()->count(2)->create();
        Organisation::factory()->count(1)->create();

        $this->actingAs($user);
        $page = visit('/teams');

        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('renders context switcher component', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/teams/switch-context');

        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });
});
