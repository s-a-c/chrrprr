@browser @api
Feature: Error Handling
  As a user
  I want to see clear error messages
  So that I can understand what went wrong

  Background:
    Given the application is running

  Scenario: Validation error display
    Given I am logged in as "admin@example.com"
    When I try to create a team with invalid data
    Then I should see validation errors
    And the errors should be clear and actionable

  Scenario: Authorization error handling
    Given I am logged in as "user@example.com"
    When I try to access an admin-only resource
    Then I should see a 403 error
    And I should see "You do not have permission"

  Scenario: Database constraint violations
    Given I am logged in as "admin@example.com"
    When I try to create a duplicate team name
    Then I should see a constraint violation error
    And the error should be user-friendly

  Scenario: Optimistic locking conflicts
    Given I am logged in as "admin@example.com"
    And there is a team "Sales"
    When two users try to update "Sales" simultaneously
    Then one should succeed
    And the other should see an optimistic locking error

  Scenario: Network/timeout errors
    Given the database is slow
    When I make a request
    Then I should see a timeout error
    And the error should suggest retrying
