---
name: Behat Integration and Behavioral Tests
overview: Integrate Behat with Playwright for comprehensive behavioral testing covering all features, user behaviors, monadic CQRS patterns, and multi-tenancy scenarios. Keep Behat tests separate from Pest test suite.
todos: []
---

# Behat Integration and Behavioral Testing Plan

## Overview

This plan integrates Behat 3.x with Playwright (via Mink) for comprehensive behavioral testing. Tests will cover all application features, validate monadic CQRS patterns, test multi-tenancy isolation, and remain separate from the existing Pest test suite.

## Architecture

### Test Structure

```
features/
├── authentication/
│   ├── registration.feature
│   ├── login.feature
│   ├── password_reset.feature
│   └── two_factor.feature
├── teams/
│   ├── team_crud.feature
│   ├── team_hierarchy.feature
│   ├── team_moves.feature
│   ├── team_context_switching.feature
│   └── team_validation.feature
├── users/
│   ├── user_management.feature
│   └── user_permissions.feature
├── settings/
│   ├── profile.feature
│   ├── password_update.feature
│   ├── two_factor_settings.feature
│   └── appearance.feature
├── multi_tenancy/
│   ├── tenant_isolation.feature
│   ├── context_switching.feature
│   └── tenant_scoping.feature
├── cqrs/
│   ├── result_monad.feature
│   ├── command_handlers.feature
│   ├── query_handlers.feature
│   └── event_sourcing.feature
└── integration/
    ├── end_to_end_workflows.feature
    └── error_handling.feature

features/bootstrap/
├── FeatureContext.php (main context)
├── LaravelContext.php (Laravel integration)
├── BrowserContext.php (Playwright/Mink)
├── ApiContext.php (HTTP API testing)
├── TenantContext.php (multi-tenancy helpers)
└── CqrsContext.php (CQRS pattern validation)
```

## Implementation Tasks

### Phase 1: Behat Setup and Configuration

1. **Install Required Dependencies**

  - Add `behat/mink` and `behat/mink-extension` for browser testing
  - Add `dmore/behat-chrome-extension` or `friends-of-behat/mink-extension` for Playwright
  - Add `laracasts/behat-laravel-extension` for Laravel integration
  - Add `behat/mink-goutte-driver` for API/HTTP testing (optional)

2. **Create Behat Configuration**

  - Create `behat.yml` in project root
  - Configure multiple suites: `browser` (Playwright), `api` (HTTP), `unit` (Laravel context)
  - Set up Laravel application context
  - Configure Playwright driver settings
  - Set up test database configuration

3. **Create Base Context Classes**

  - `FeatureContext.php`: Main context extending `MinkContext` and `LaravelContext`
  - `LaravelContext.php`: Laravel-specific helpers (database, factories, authentication)
  - `BrowserContext.php`: Playwright-specific browser interactions
  - `ApiContext.php`: HTTP API testing helpers
  - `TenantContext.php`: Multi-tenancy setup and context switching
  - `CqrsContext.php`: CQRS pattern validation helpers

### Phase 2: Authentication Feature Scenarios

4. **Registration Scenarios**

  - Successful user registration
  - Email validation
  - Password strength requirements
  - Email verification flow
  - Registration with existing email (failure)

5. **Login Scenarios**

  - Successful login
  - Invalid credentials
  - Remember me functionality
  - Redirect after login
  - Session persistence

6. **Password Reset Scenarios**

  - Request password reset
  - Invalid token handling
  - Password reset completion
  - Token expiration

7. **Two-Factor Authentication Scenarios**

  - Enable 2FA
  - Login with 2FA
  - Recovery codes
  - Disable 2FA

### Phase 3: Team Management Scenarios

8. **Team CRUD Scenarios**

  - Create Enterprise team
  - Create Organization under Enterprise
  - Create Division under Organization
  - Create Department under Division
  - Create Project under Department
  - View team details
  - Update team information
  - Delete team (with constraints)

9. **Team Hierarchy Scenarios**

  - Valid parent-child relationships
  - Invalid hierarchy (cycle prevention)
  - Depth validation
  - Type-specific constraints (Executive/Deputy)
  - Unique name validation within context

10. **Team Move Scenarios**

  - Move team to new parent
  - Move approval workflow
  - Prevent cycles during move
  - Update tenant_id on move
  - Audit trail for moves

11. **Team Context Switching Scenarios**

  - Switch to accessible organization
  - Query scoping to current context
  - Context persistence across sessions
  - Invalid context auto-correction
  - Context bypass for authorized users

### Phase 4: User Management Scenarios

12. **User Management Scenarios**

  - View user profile
  - Update user information
  - Assign user to organizations
  - User role management
  - User deletion constraints (key roles)

13. **User Permissions Scenarios**

  - Access control based on roles
  - Organization-level permissions
  - Enterprise-level permissions
  - Permission inheritance

### Phase 5: Settings Scenarios

14. **Profile Settings**

  - Update profile information
  - Bio rendering and sanitization
  - Translatable attributes
  - Profile validation

15. **Password Update**

  - Change password with current password
  - Password confirmation
  - Password strength validation

16. **Two-Factor Settings**

  - Enable/disable 2FA in settings
  - View recovery codes
  - Regenerate recovery codes

17. **Appearance Settings**

  - Toggle dark mode
  - Theme persistence

### Phase 6: Multi-Tenancy Scenarios

18. **Tenant Isolation**

  - Data isolation between tenants
  - Query scoping by tenant_id
  - Cache isolation
  - Filesystem isolation
  - Queue isolation

19. **Context Switching**

  - Switch between accessible organizations
  - Prevent access to unauthorized contexts
  - Context restoration on login
  - Context persistence

20. **Tenant Scoping**

  - Team queries scoped to tenant
  - User queries scoped to tenant
  - Cross-tenant data leakage prevention

### Phase 7: CQRS Pattern Scenarios

21. **Result Monad Behaviors**

  - Success Result handling
  - Failure Result handling
  - Result chaining (map, flatMap)
  - Writer monad log accumulation
  - Collection proxy operations
  - AsyncResult parallel execution

22. **Command Handler Scenarios**

  - Successful command execution
  - Command validation failures
  - Transaction rollback on failure
  - Audit trail generation
  - Event firing (Verbs integration)

23. **Query Handler Scenarios**

  - Successful query execution
  - Query result transformation
  - Query caching
  - Error handling in queries

24. **Event Sourcing Scenarios**

  - Event creation and validation
  - Projection updates
  - Event replay
  - Event validation rules

### Phase 8: Integration and Error Handling

25. **End-to-End Workflows**

  - Complete user registration to team creation
  - Team hierarchy creation workflow
  - Team move approval workflow
  - User context switching workflow

26. **Error Handling Scenarios**

  - Validation error display
  - Authorization error handling
  - Database constraint violations
  - Optimistic locking conflicts
  - Network/timeout errors

### Phase 9: Step Definitions

27. **Authentication Step Definitions**

  - `Given I am registered as "email" with password "password"`
  - `Given I am logged in as "email"`
  - `When I login with email "email" and password "password"`
  - `Then I should be authenticated`
  - `Then I should see the login form`

28. **Team Step Definitions**

  - `Given there is an Enterprise named "name"`
  - `Given there is an Organization "name" under Enterprise "enterprise"`
  - `When I create a team of type "type" named "name"`
  - `Then I should see team "name" in the list`
  - `When I move team "team" to parent "parent"`

29. **Multi-Tenancy Step Definitions**

  - `Given I am in tenant context "tenant"`
  - `Given I switch to organization "org"`
  - `Then queries should be scoped to tenant "tenant"`
  - `Then I should not see data from tenant "other_tenant"`

30. **CQRS Step Definitions**

  - `When I execute command "CommandName" with data:`
  - `Then the command should return a successful Result`
  - `Then the Result should contain logs:`
  - `Then an event "EventName" should be fired`
  - `Then the projection should be updated`

31. **Browser Step Definitions**

  - `When I visit "url"`
  - `When I click on "selector"`
  - `When I fill in "field" with "value"`
  - `Then I should see "text"`
  - `Then I should see element "selector"`
  - `When I wait for "selector" to appear`

32. **API Step Definitions**

  - `When I send a "METHOD" request to "url" with body:`
  - `Then the response status should be "status"`
  - `Then the response should contain JSON:`
  - `Then the response should contain header "header" with value "value"`

### Phase 10: CI/CD Integration

33. **GitHub Actions Integration**

  - Add Behat test suite to `.github/workflows/tests.yml`
  - Configure Playwright in CI environment
  - Set up test database for Behat
  - Add Behat test results reporting

34. **Composer Scripts**

  - Add `test:behat` script
  - Add `test:behat:browser` script
  - Add `test:behat:api` script
  - Add `test:behat:watch` script for development

35. **Documentation**

  - Create `docs/behat/README.md` with setup instructions
  - Document feature file structure
  - Document step definition patterns
  - Add examples for common scenarios

## Key Files to Create/Modify

### New Files

- `behat.yml` - Behat configuration
- `features/bootstrap/FeatureContext.php` - Main context
- `features/bootstrap/LaravelContext.php` - Laravel integration
- `features/bootstrap/BrowserContext.php` - Browser helpers
- `features/bootstrap/ApiContext.php` - API helpers
- `features/bootstrap/TenantContext.php` - Multi-tenancy helpers
- `features/bootstrap/CqrsContext.php` - CQRS helpers
- `features/**/*.feature` - Gherkin feature files (30+ files)
- `docs/behat/README.md` - Documentation

### Modified Files

- `composer.json` - Add Behat extensions
- `.github/workflows/tests.yml` - Add Behat test suite
- `phpunit.xml` - Ensure Behat tests are excluded (separate suite)

## Technical Considerations

### Behat Configuration

- Use `behat/mink-extension` for browser testing
- Use Playwright driver via `friends-of-behat/mink-extension` or custom driver
- Configure Laravel application context via `laracasts/behat-laravel-extension`
- Set up separate test database schema for Behat

### Playwright Integration

- Install Playwright via npm/bun
- Configure Playwright in `behat.yml` with headless mode for CI
- Set up screenshot capture on failures
- Configure browser timeouts and retries

### Laravel Integration

- Use Laravel's testing database
- Leverage factories for test data setup
- Use `RefreshDatabase` trait equivalent in Behat contexts
- Integrate with Laravel's authentication system

### Multi-Tenancy Testing

- Create tenant factories for test setup
- Implement tenant context switching in step definitions
- Validate tenant isolation in assertions
- Test context persistence across requests

### CQRS Pattern Testing

- Direct handler invocation for unit-level CQRS tests
- Validate Result monad behaviors
- Check event firing via Verbs
- Validate projection updates
- Test audit trail accumulation

## Success Criteria

- All feature areas have comprehensive Gherkin scenarios
- Browser tests run successfully with Playwright
- API tests validate HTTP endpoints
- Multi-tenancy isolation is validated
- CQRS patterns are tested at behavioral level
- Tests integrate with CI/CD pipeline
- Documentation is complete and clear
- Tests are maintainable and follow DRY principles

## Dependencies

- Behat 3.x (already in composer.json)
- behat/mink and behat/mink-extension
- Playwright driver for Mink
- laracasts/behat-laravel-extension
- Laravel test environment
- Test database configuration
