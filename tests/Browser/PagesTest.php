<?php

declare(strict_types=1);

use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Dashboard Page', function (): void {
    it('renders dashboard correctly', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/dashboard');

        $page->assertSee('Dashboard');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('requires authentication to access dashboard', function (): void {
        $page = visit('/dashboard');

        $page->assertPathIs('/login');
        assert_no_javascript_errors_except_csp_parser($page);
    });
});

describe('Chrrps Page', function (): void {
    it('renders chrrps page correctly', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/chrrps');

        $page->assertSee('What\'s on your mind?')
            ->assertSee('Chrrp');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('displays existing chrrps', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/chrrps');

        $page->assertSee('Jane Doe')
            ->assertSee('Just deployed my first Laravel app!');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('can submit a new chrrp', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/chrrps')
            ->fill('message', 'This is a test chrrp!')
            ->click('Chrrp');

        $page->assertSee('Chrrp sent!');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('requires authentication to access chrrps', function (): void {
        $page = visit('/chrrps');

        $page->assertPathIs('/login');
        assert_no_javascript_errors_except_csp_parser($page);
    });
});

describe('Teams Index Page', function (): void {
    it('renders teams index page correctly', function (): void {
        $user = User::factory()->create();
        Enterprise::factory()->count(2)->create();
        Organisation::factory()->count(1)->create();

        $this->actingAs($user);
        $page = visit('/teams');

        $page->assertSee('Teams')
            ->assertSee('Manage your team hierarchy and biographies')
            ->assertSee('Create Team');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('displays teams in a table', function (): void {
        $user = User::factory()->create();
        $team = Enterprise::factory()->create();

        $this->actingAs($user);
        $page = visit('/teams');

        $page->assertSee($team->name);
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('has a link to create new team', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/teams')
            ->click('Create Team');

        $page->assertPathIs('/teams/create');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });
});

describe('Teams Create Page', function (): void {
    it('renders teams create page correctly', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/teams/create');

        $page->assertSee('Create New Team')
            ->assertSee('Team Name')
            ->assertSee('Type')
            ->assertSee('Parent Team')
            ->assertSee('Bio (Markdown)')
            ->assertSee('Create Team')
            ->assertSee('Cancel');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('can create a new team', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/teams/create')
            ->fill('name', 'Test Team')
            ->select('type', 'organisation')
            ->fill('bio', '# Test Team Bio')
            ->click('Create Team');

        $page->assertPathIs('/teams')
            ->assertSee('Team created successfully');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('validates required fields when creating team', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/teams/create')
            ->click('Create Team');

        $page->assertSee('The name field is required');
        assert_no_javascript_errors_except_csp_parser($page);
    });

    it('can cancel team creation', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);
        $page = visit('/teams/create')
            ->click('Cancel');

        $page->assertPathIs('/teams');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });
});

describe('Teams Edit Page', function (): void {
    it('renders teams edit page correctly', function (): void {
        $user = User::factory()->create();
        $team = Enterprise::factory()->create();

        $this->actingAs($user);
        $page = visit("/teams/{$team->ulid}");

        $page->assertSee('Edit Team')
            ->assertSee($team->name)
            ->assertSee('Team Name')
            ->assertSee('Update Team')
            ->assertSee('Cancel');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('displays team bio if available', function (): void {
        $user = User::factory()->create();
        $team = Enterprise::factory()->create(['bio' => ['en' => '# Test Bio']]);

        $this->actingAs($user);
        $page = visit("/teams/{$team->ulid}");

        $page->assertSee('Biography');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('can update team details', function (): void {
        $user = User::factory()->create();
        $team = Enterprise::factory()->create();

        $this->actingAs($user);
        $page = visit("/teams/{$team->ulid}")
            ->fill('name', 'Updated Team Name')
            ->fill('bio', '# Updated Bio')
            ->click('Update Team');

        $page->assertPathIs('/teams')
            ->assertSee('Team updated successfully');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('can cancel team editing', function (): void {
        $user = User::factory()->create();
        $team = Enterprise::factory()->create();

        $this->actingAs($user);
        $page = visit("/teams/{$team->ulid}")
            ->click('Cancel');

        $page->assertPathIs('/teams');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });
});

describe('Users Show Page', function (): void {
    it('renders user show page correctly', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create(['bio' => ['en' => '# User Bio']]);

        $this->actingAs($user);
        $page = visit("/users/{$otherUser->ulid}");

        $page->assertSee($otherUser->name)
            ->assertSee($otherUser->email);
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('displays user bio if available', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create(['bio' => ['en' => '# Test User Bio']]);

        $this->actingAs($user);
        $page = visit("/users/{$otherUser->ulid}");

        $page->assertSee('Biography');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });

    it('shows message when user has no bio', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create(['bio' => null]);

        $this->actingAs($user);
        $page = visit("/users/{$otherUser->ulid}");

        $page->assertSee('This user has not written a biography yet');
        assert_no_javascript_errors_except_csp_parser($page)->assertNoConsoleLogs();
    });
});
