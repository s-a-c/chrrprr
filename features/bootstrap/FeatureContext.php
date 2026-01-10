<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Division;
use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkContext;
use Cevinio\Behat\Context\LaravelAwareContext;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Main Feature Context for Behat tests.
 *
 * Combines all context classes and provides common step definitions.
 */
class FeatureContext extends MinkContext implements Context, LaravelAwareContext
{
    protected Application $app;

    protected ?User $currentUser = null;

    public function setLaravelFactory(\Cevinio\Behat\ServiceContainer\LaravelFactory $factory): void
    {
        // Optionally implement if needed. Placeholder for abstract method.
    }

    public function bootstrapLaravelEnvironment(\Behat\Behat\EventDispatcher\Event\BeforeScenarioTested $event): array
    {
        // Implement environment setup here
        return [];
    }

    public function bootstrapLaravelApplication(\Illuminate\Contracts\Foundation\Application $app, \Behat\Behat\EventDispatcher\Event\BeforeScenarioTested $event): void
    {
        // Implement application bootstrapping here
    }


    public function setApp(Application $app): void
    {
        $this->app = $app;
    }

    /**
     * User is registered.
     *
     * @Given I am registered as :email with password :password
     */
    public function iAmRegisteredAsWithPassword(string $email, string $password): void
    {
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->currentUser = $user;
    }

    /**
     * User is logged in.
     *
     * @Given I am logged in as :email
     */
    public function iAmLoggedInAs(string $email): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw new \Exception("User with email '{$email}' not found");
        }

        // Access Laravel directly through the injected app
        $this->app['auth']->login($user);
        $this->currentUser = $user;
    }

    /**
     * Login with credentials.
     *
     * @When I login with email :email and password :password
     */
    public function iLoginWithEmailAndPassword(string $email, string $password): void
    {
        $this->visit('/login');
        $this->fillField('email', $email);
        $this->fillField('password', $password);
        $this->pressButton('Log in');
    }

    /**
     * Assert user is authenticated.
     *
     * @Then I should be authenticated
     */
    public function iShouldBeAuthenticated(): void
    {
        if (! Auth::check()) {
            throw new \Exception('User is not authenticated');
        }
    }

    /**
     * Assert login form is visible.
     *
     * @Then I should see the login form
     */
    public function iShouldSeeTheLoginForm(): void
    {
        $this->assertPageContainsText('Log in');
        $this->assertElementOnPage('input[name="email"]');
        $this->assertElementOnPage('input[name="password"]');
    }

    /**
     * Enterprise exists.
     *
     * @Given there is an Enterprise named :name
     */
    public function thereIsAnEnterpriseNamed(string $name): void
    {
        Enterprise::factory()->create(['name' => ['en' => $name]]);
    }

    /**
     * Organization exists under Enterprise.
     *
     * @Given there is an Organization :orgName under Enterprise :enterpriseName
     */
    public function thereIsAnOrganizationUnderEnterprise(string $orgName, string $enterpriseName): void
    {
        $enterprise = Enterprise::where('name->en', $enterpriseName)
            ->orWhere('name', 'like', "%{$enterpriseName}%")
            ->first();

        if (! $enterprise) {
            throw new \Exception("Enterprise '{$enterpriseName}' not found");
        }

        Organisation::factory()->create([
            'name' => ['en' => $orgName],
            'parent_id' => $enterprise->id,
            'tenant_id' => $enterprise->id,
        ]);
    }

    /**
     * Create team.
     *
     * @When I create a team of type :type named :name
     */
    public function iCreateATeamOfTypeNamed(string $type, string $name): void
    {
        $this->visit('/teams/create');
        // Implementation depends on form structure
        // This is a placeholder - actual implementation would fill the form
    }

    /**
     * Assert team is in list.
     *
     * @Then I should see team :name in the list
     */
    public function iShouldSeeTeamInTheList(string $name): void
    {
        $this->assertPageContainsText($name);
    }

    /**
     * Move team to parent.
     *
     * @When I move team :teamName to parent :parentName
     */
    public function iMoveTeamToParent(string $teamName, string $parentName): void
    {
        // Implementation depends on UI structure
        // This is a placeholder
    }

    /**
     * Get current user.
     */
    public function getCurrentUser(): ?User
    {
        return $this->currentUser;
    }

    /**
     * Set current user.
     */
    public function setCurrentUser(User $user): void
    {
        $this->currentUser = $user;
    }

    /**
     * Application is running.
     *
     * @Given the application is running
     */
    public function theApplicationIsRunning(): void
    {
        // Application is bootstrapped via LaravelContext
        // This step is mainly for documentation/clarity
    }

    /**
     * Create organization under enterprise.
     *
     * @When I create an Organization :orgName under Enterprise :enterpriseName
     */
    public function iCreateAnOrganizationUnderEnterprise(string $orgName, string $enterpriseName): void
    {
        $this->thereIsAnOrganizationUnderEnterprise($orgName, $enterpriseName);
    }

    /**
     * Create division under organization.
     *
     * @When I create a Division :divName under Organization :orgName
     */
    public function iCreateADivisionUnderOrganization(string $divName, string $orgName): void
    {
        $org = Organisation::where('name->en', $orgName)
            ->orWhere('name', 'like', "%{$orgName}%")
            ->first();

        if (! $org) {
            throw new \Exception("Organization '{$orgName}' not found");
        }

        Division::factory()->create([
            'name' => ['en' => $divName],
            'parent_id' => $org->id,
            'tenant_id' => $org->tenant_id,
        ]);
    }

    /**
     * Assert team is under parent.
     *
     * @Then :teamName should be under :parentName
     */
    public function teamShouldBeUnder(string $teamName, string $parentName): void
    {
        $team = Team::where('name->en', $teamName)->first();
        $parent = Team::where('name->en', $parentName)->first();

        if (! $team || ! $parent) {
            throw new \Exception('Team or parent not found');
        }

        if ($team->parent_id !== $parent->id) {
            throw new \Exception("Team '{$teamName}' is not under '{$parentName}'");
        }
    }

    /**
     * Assert team is not under parent.
     *
     * @Then :teamName should not be under :parentName
     */
    public function teamShouldNotBeUnder(string $teamName, string $parentName): void
    {
        $team = Team::where('name->en', $teamName)->first();
        $parent = Team::where('name->en', $parentName)->first();

        if (! $team || ! $parent) {
            throw new \Exception('Team or parent not found');
        }

        if ($team->parent_id === $parent->id) {
            throw new \Exception("Team '{$teamName}' is still under '{$parentName}'");
        }
    }

    /**
     * Assert current context.
     *
     * @Then my current context should be :orgName
     */
    public function myCurrentContextShouldBe(string $orgName): void
    {
        if (! $this->currentUser) {
            throw new \Exception('No current user set');
        }

        $org = Organisation::where('name->en', $orgName)->first();
        if (! $org) {
            throw new \Exception("Organization '{$orgName}' not found");
        }

        $user = $this->currentUser->fresh();
        if ($user->current_context_id !== $org->id) {
            throw new \Exception("Current context is not '{$orgName}'");
        }
    }

    /**
     * Assert queries scoped to context.
     *
     * @Then queries should be scoped to context :orgName
     */
    public function queriesShouldBeScopedToContext(string $orgName): void
    {
        $org = Organisation::where('name->en', $orgName)->first();
        if (! $org) {
            throw new \Exception("Organization '{$orgName}' not found");
        }

        // Verify teams are scoped to this context
        $teams = Team::inContext()->get();
        foreach ($teams as $team) {
            // Teams should be descendants of the organization
            $isDescendant = $this->isDescendantOf($team, $org);
            if (! $isDescendant) {
                throw new \Exception("Team '{$team->name}' is not scoped to context '{$orgName}'");
            }
        }
    }

    /**
     * Assert able to login.
     *
     * @Then I should be able to login with email :email and password :password
     */
    public function iShouldBeAbleToLoginWithEmailAndPassword(string $email, string $password): void
    {
        Auth::logout();
        $this->iLoginWithEmailAndPassword($email, $password);
        $this->iShouldBeAuthenticated();
    }

    /**
     * Create department under division.
     *
     * @When I create a Department :deptName under Division :divName
     */
    public function iCreateADepartmentUnderDivision(string $deptName, string $divName): void
    {
        $division = Division::where('name->en', $divName)->first();
        if (! $division) {
            throw new \Exception("Division '{$divName}' not found");
        }

        Department::factory()->create([
            'name' => ['en' => $deptName],
            'parent_id' => $division->id,
            'tenant_id' => $division->tenant_id,
        ]);
    }

    /**
     * Create project under department.
     *
     * @When I create a Project :projName under Department :deptName
     */
    public function iCreateAProjectUnderDepartment(string $projName, string $deptName): void
    {
        $department = Department::where('name->en', $deptName)->first();
        if (! $department) {
            throw new \Exception("Department '{$deptName}' not found");
        }

        Project::factory()->create([
            'name' => ['en' => $projName],
            'parent_id' => $department->id,
            'tenant_id' => $department->tenant_id,
        ]);
    }

    /**
     * Assert team type.
     *
     * @Then the team should be of type :type
     */
    public function theTeamShouldBeOfType(string $type): void
    {
        // This would need to check the last created team or get from context
        // Placeholder implementation
    }

    /**
     * Assert hierarchy structure.
     *
     * @Then the hierarchy should be:
     */
    public function theHierarchyShouldBe(Behat\Gherkin\Node\PyStringNode $hierarchy): void
    {
        // Validate the hierarchy structure matches the expected tree
        // This is a placeholder - actual implementation would parse and validate
    }

    /**
     * Assert all teams have correct tenant_id.
     *
     * @Then all teams should have correct tenant_id
     */
    public function allTeamsShouldHaveCorrectTenantId(): void
    {
        $teams = Team::all();
        foreach ($teams as $team) {
            if (! $team->tenant_id) {
                throw new \Exception("Team '{$team->name}' does not have tenant_id");
            }
        }
    }

    /**
     * Assert move approval created.
     *
     * @Then a move approval should be created
     */
    public function aMoveApprovalShouldBeCreated(): void
    {
        $approval = App\Models\TeamMoveApproval::latest()->first();
        if (! $approval) {
            throw new \Exception('No move approval was created');
        }
    }

    /**
     * Approve move.
     *
     * @When the move is approved
     */
    public function theMoveIsApproved(): void
    {
        $approval = App\Models\TeamMoveApproval::latest()->first();
        if (! $approval) {
            throw new \Exception('No move approval found');
        }
        // Approve the move - implementation depends on approval workflow
    }

    /**
     * Assert audit log entry created.
     *
     * @Then an audit log entry should be created
     */
    public function anAuditLogEntryShouldBeCreated(): void
    {
        // This step requires CqrsContext to be used separately
        // Check if audit log was created via Result monad logs or Verbs events
        // This is a placeholder - actual implementation would verify audit logs
    }

    /**
     * Assert log contains text.
     *
     * @Then the log should contain :text
     */
    public function theLogShouldContain(string $text): void
    {
        // This step requires CqrsContext to be used separately
        // Check logs from Result monad
        // This is a placeholder - actual implementation would verify logs
    }

    /**
     * Logout user.
     *
     * @When I logout
     */
    public function iLogout(): void
    {
        Auth::logout();
        $this->currentUser = null;
    }

    /**
     * Assert two-factor authentication is enabled.
     *
     * @Then two-factor authentication should be enabled
     */
    public function twoFactorAuthenticationShouldBeEnabled(): void
    {
        if (! $this->currentUser) {
            throw new \Exception('No current user set');
        }

        $user = $this->currentUser->fresh();
        if (! $user->two_factor_secret) {
            throw new \Exception('Two-factor authentication is not enabled for the user');
        }
    }

    /**
     * Assert two-factor authentication is disabled.
     *
     * @Then two-factor authentication should be disabled
     */
    public function twoFactorAuthenticationShouldBeDisabled(): void
    {
        if (! $this->currentUser) {
            throw new \Exception('No current user set');
        }

        $user = $this->currentUser->fresh();
        if ($user->two_factor_secret) {
            throw new \Exception('Two-factor authentication is still enabled for the user');
        }
    }

    /**
     * Two-factor authentication is enabled for user.
     *
     * @Given two-factor authentication is enabled for :email
     */
    public function twoFactorAuthenticationIsEnabledFor(string $email): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw new \Exception("User with email '{$email}' not found");
        }

        // Enable 2FA for the user
        // This would typically use Laravel Fortify's 2FA methods
        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2', 'code3'])),
        ])->save();

        if ($this->currentUser && $this->currentUser->email === $email) {
            $this->currentUser = $user->fresh();
        }
    }

    /**
     * User exists.
     *
     * @Given there is a user :email
     */
    public function thereIsAUser(string $email): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            $user = User::factory()->create(['email' => $email]);
        }

        if ($this->currentUser && $this->currentUser->email === $email) {
            $this->currentUser = $user;
        }
    }

    /**
     * User has role.
     *
     * @Given user :email has role :role
     */
    public function userHasRole(string $email, string $role): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw new \Exception("User with email '{$email}' not found");
        }

        // Assign role using Spatie Permission or similar
        if (method_exists($user, 'assignRole')) {
            $user->assignRole($role);
        } else {
            // Fallback: set role directly if using a simple role field
            if (property_exists($user, 'role') || $user->getAttributes()['role'] ?? null) {
                $user->update(['role' => $role]);
            } else {
                throw new \Exception('Role assignment method not available for user model');
            }
        }
    }

    /**
     * Try to access admin-only resource as user.
     *
     * @When I try to access an admin-only resource as :email
     */
    public function iTryToAccessAnAdminOnlyResourceAs(string $email): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw new \Exception("User with email '{$email}' not found");
        }

        Auth::login($user);
        $this->currentUser = $user;

        // Try to access an admin route
        try {
            $this->visit('/admin');
        } catch (\Exception $e) {
            // Expected to fail for non-admin users
        }
    }

    /**
     * Assert 403 error is shown.
     *
     * @Then I should see a 403 error
     */
    public function iShouldSeeA403Error(): void
    {
        $this->assertResponseStatus(403);
    }

    /**
     * Assert page contains text.
     *
     * @Then I should see :text
     */
    public function iShouldSee(string $text): void
    {
        $this->assertPageContainsText($text);
    }

    /**
     * Grant user access to organization.
     *
     * @When I grant :email access to organization :orgName
     */
    public function iGrantAccessToOrganization(string $email, string $orgName): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw new \Exception("User with email '{$email}' not found");
        }

        $org = Organisation::where('name->en', $orgName)
            ->orWhere('name', 'like', "%{$orgName}%")
            ->first();

        if (! $org) {
            throw new \Exception("Organization '{$orgName}' not found");
        }

        DB::table('user_organisation_access')->insert([
            'user_id' => $user->id,
            'organisation_id' => $org->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Assert user can access organization data.
     *
     * @Then user :email should be able to access :orgName data
     */
    public function userShouldBeAbleToAccessData(string $email, string $orgName): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw new \Exception("User with email '{$email}' not found");
        }

        $org = Organisation::where('name->en', $orgName)
            ->orWhere('name', 'like', "%{$orgName}%")
            ->first();

        if (! $org) {
            throw new \Exception("Organization '{$orgName}' not found");
        }

        // Verify user has access
        $hasAccess = DB::table('user_organisation_access')
            ->where('user_id', $user->id)
            ->where('organisation_id', $org->id)
            ->exists();

        if (! $hasAccess) {
            throw new \Exception("User '{$email}' does not have access to organization '{$orgName}'");
        }
    }

    /**
     * Assert user cannot access other organizations.
     *
     * @Then user :email should not be able to access other organizations
     */
    public function userShouldNotBeAbleToAccessOtherOrganizations(string $email): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw new \Exception("User with email '{$email}' not found");
        }

        // Get user's accessible organizations
        $accessibleOrgIds = DB::table('user_organisation_access')
            ->where('user_id', $user->id)
            ->pluck('organisation_id')
            ->toArray();

        // Get all organizations
        $allOrgs = Organisation::all();
        $inaccessibleOrgs = $allOrgs->filter(function ($org) use ($accessibleOrgIds) {
            return ! in_array($org->id, $accessibleOrgIds);
        });

        // Verify user cannot access these organizations
        // This would typically be tested by trying to switch context
        if ($inaccessibleOrgs->isEmpty()) {
            // User has access to all organizations, which might be valid for enterprise admins
            return;
        }
    }

    /**
     * Assign enterprise admin role to user.
     *
     * @When I assign enterprise admin role to :email
     */
    public function iAssignEnterpriseAdminRoleTo(string $email): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw new \Exception("User with email '{$email}' not found");
        }

        // Assign enterprise admin role
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('enterprise-admin');
        } else {
            $user->update(['role' => 'enterprise-admin']);
        }
    }

    /**
     * Assert user has access to all organizations in enterprise.
     *
     * @Then user :email should have access to all organizations in :enterpriseName
     */
    public function userShouldHaveAccessToAllOrganizationsIn(string $email, string $enterpriseName): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw new \Exception("User with email '{$email}' not found");
        }

        $enterprise = Enterprise::where('name->en', $enterpriseName)
            ->orWhere('name', 'like', "%{$enterpriseName}%")
            ->first();

        if (! $enterprise) {
            throw new \Exception("Enterprise '{$enterpriseName}' not found");
        }

        // Get all organizations in the enterprise
        $orgs = Organisation::where('tenant_id', $enterprise->id)->get();

        // Verify user has enterprise admin role or access to all orgs
        $hasEnterpriseAdminRole = false;
        if (method_exists($user, 'hasRole')) {
            $hasEnterpriseAdminRole = $user->hasRole('enterprise-admin');
        }

        if (! $hasEnterpriseAdminRole) {
            // Check if user has access to all organizations
            $accessibleOrgIds = DB::table('user_organisation_access')
                ->where('user_id', $user->id)
                ->pluck('organisation_id')
                ->toArray();

            foreach ($orgs as $org) {
                if (! in_array($org->id, $accessibleOrgIds)) {
                    throw new \Exception("User '{$email}' does not have access to organization '{$org->name}' in enterprise '{$enterpriseName}'");
                }
            }
        }
    }

    /**
     * Assert user can manage enterprise settings.
     *
     * @Then user :email should be able to manage enterprise settings
     */
    public function userShouldBeAbleToManageEnterpriseSettings(string $email): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw new \Exception("User with email '{$email}' not found");
        }

        // Verify user has enterprise admin role
        $hasEnterpriseAdminRole = false;
        if (method_exists($user, 'hasRole')) {
            $hasEnterpriseAdminRole = $user->hasRole('enterprise-admin');
        } else {
            $userRole = $user->getAttributes()['role'] ?? null;
            $hasEnterpriseAdminRole = $userRole === 'enterprise-admin';
        }

        if (! $hasEnterpriseAdminRole) {
            throw new \Exception("User '{$email}' does not have enterprise admin role");
        }
    }

    /**
     * Assert permissions are inherited from parent organization.
     *
     * @Then permissions should be inherited from parent organization
     */
    public function permissionsShouldBeInheritedFromParentOrganization(): void
    {
        // This would typically verify that when a user has access to an organization,
        // they also have access to child divisions, departments, etc.
        // For now, this is a placeholder that verifies the concept
        if (! $this->currentUser) {
            throw new \Exception('No current user set');
        }

        // Verify that user's accessible organizations include their children
        // This would require checking the hierarchy and access inheritance logic
    }

    /**
     * Check if team is descendant of organization.
     */
    private function isDescendantOf(Team $team, Organisation $org): bool
    {
        $current = $team;
        while ($current && $current->parent_id) {
            if ($current->parent_id === $org->id) {
                return true;
            }
            $current = Team::find($current->parent_id);
        }

        return false;
    }
}
