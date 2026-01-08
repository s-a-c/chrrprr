# Behat Behavioral Testing

This project uses [Behat](https://behat.org/) for behavioral-driven development (BDD) testing. Behat allows us to write tests in natural language using Gherkin syntax, making tests readable by both technical and non-technical stakeholders.

## Overview

Behat tests are organized into feature files that describe application behavior from a user's perspective. These tests complement our Pest test suite by focusing on end-to-end workflows and business logic validation.

## Structure

```
features/
├── authentication/     # Authentication and authorization scenarios
├── teams/             # Team management scenarios
├── users/             # User management scenarios
├── settings/          # User settings scenarios
├── multi_tenancy/     # Multi-tenancy and context switching
├── cqrs/              # CQRS pattern validation
├── integration/       # End-to-end workflows
└── bootstrap/         # Context classes and step definitions
```

## Test Suites

Behat is configured with three test suites:

- **browser**: Browser-based tests using Playwright (via Mink)
- **api**: HTTP API tests using Goutte driver
- **unit**: Unit-level tests using Laravel context only

## Running Tests

### Run All Behat Tests

```bash
composer run test:behat
```

### Run Browser Tests Only

```bash
composer run test:behat:browser
```

### Run API Tests Only

```bash
composer run test:behat:api
```

### Run with Watch Mode

```bash
composer run test:behat:watch
```

### Run Specific Feature

```bash
vendor/bin/behat features/authentication/login.feature
```

### Run Scenarios with Specific Tags

```bash
vendor/bin/behat --tags @browser
vendor/bin/behat --tags @api
vendor/bin/behat --tags @unit
```

## Writing Feature Files

Feature files use Gherkin syntax:

```gherkin
Feature: User Login
  As a registered user
  I want to log in to my account
  So that I can access the application

  Scenario: Successful login
    Given I am on "/login"
    When I fill in "email" with "user@example.com"
    And I fill in "password" with "Password123!"
    And I press "Log in"
    Then I should be authenticated
    And I should be on "/dashboard"
```

## Context Classes

### FeatureContext

Main context that combines all other contexts and provides common step definitions.

### LaravelContext

Provides Laravel-specific functionality:
- Database management
- Factory usage
- Application bootstrapping

### BrowserContext

Provides browser interactions:
- Element waiting
- Screenshot capture
- Scrolling

### ApiContext

Provides HTTP API testing:
- Request/response handling
- JSON validation
- Header assertions

### TenantContext

Provides multi-tenancy functionality:
- Tenant context switching
- Organization access management
- Tenant isolation validation

### CqrsContext

Provides CQRS pattern validation:
- Command execution
- Result monad validation
- Event firing verification

## Step Definitions

Common step definitions are available across all contexts:

### Authentication

- `Given I am registered as "email" with password "password"`
- `Given I am logged in as "email"`
- `When I login with email "email" and password "password"`
- `Then I should be authenticated`

### Teams

- `Given there is an Enterprise named "name"`
- `Given there is an Organization "name" under Enterprise "enterprise"`
- `When I create a team of type "type" named "name"`
- `Then I should see team "name" in the list`

### Multi-Tenancy

- `Given I am in tenant context "tenant"`
- `Given I switch to organization "org"`
- `Then queries should be scoped to tenant "tenant"`

### CQRS

- `When I execute command "CommandName" with data:`
- `Then the command should return a successful Result`
- `Then an event "EventName" should be fired`

## Configuration

Behat configuration is in `behat.yml`. Key settings:

- **Base URL**: `http://chrrprr.test` (configured for Laravel Herd)
- **Browser**: Chrome/Chromium via Playwright
- **Screenshots**: Saved to `tests/Browser/Screenshots/` on failures
- **Laravel Integration**: Uses `laracasts/behat-laravel-extension`

## CI/CD Integration

Behat tests run as part of the CI pipeline. The GitHub Actions workflow includes:

1. Setup of Playwright browser
2. Database migration
3. Running all test suites
4. Screenshot capture on failures

## Best Practices

1. **Write scenarios, not scripts**: Focus on behavior, not implementation
2. **Use tags**: Tag scenarios appropriately (`@browser`, `@api`, `@unit`)
3. **Keep scenarios independent**: Each scenario should be able to run alone
4. **Use Background**: Set up common prerequisites in Background sections
5. **Reuse step definitions**: Create reusable steps in context classes
6. **Test user workflows**: Focus on end-to-end user journeys

## Troubleshooting

### Browser Tests Not Running

Ensure Playwright is installed and Chrome is available:

```bash
bunx playwright install chromium
```

### Database Issues

Behat uses the same test database as Pest. Ensure migrations are run:

```bash
php artisan migrate:fresh --env=testing
```

### Screenshots Not Saving

Check that the `tests/Browser/Screenshots/` directory exists and is writable.

## Related Documentation

- [Behat Documentation](https://docs.behat.org/)
- [Gherkin Syntax](https://cucumber.io/docs/gherkin/)
- [Mink Documentation](https://mink.behat.org/)
- [Laravel Behat Extension](https://github.com/laracasts/behat-laravel-extension)
