@browser @api
Feature: Team Context Switching
  As an authenticated user
  I want to switch between organizational contexts
  So that I can work within different parts of the enterprise

  Background:
    Given the application is running
    And I am logged in as "user@example.com"
    And there is an Enterprise named "Acme Corp"
    And there is an Organization "Sales" under Enterprise "Acme Corp"
    And there is an Organization "Marketing" under Enterprise "Acme Corp"
    And user "user@example.com" has access to organization "Sales"
    And user "user@example.com" has access to organization "Marketing"

  Scenario: Switch to accessible organization
    Given I am on "/teams"
    When I switch to organization "Sales"
    Then my current context should be "Sales"
    And queries should be scoped to context "Sales"

  Scenario: Query scoping to current context
    Given I switch to organization "Sales"
    And there is a Division "North" under Organization "Sales"
    And there is a Division "South" under Organization "Marketing"
    When I view teams
    Then I should see "North"
    And I should not see "South"

  Scenario: Context persistence across sessions
    Given I switch to organization "Sales"
    When I logout
    And I login with email "user@example.com" and password "Password123!"
    Then my current context should be "Sales"

  Scenario: Invalid context auto-correction
    Given I switch to organization "Sales"
    And access to "Sales" is revoked for "user@example.com"
    When I visit "/teams"
    Then my context should be automatically corrected
    And my current context should be "Marketing"

  Scenario: Context bypass for authorized users
    Given I am logged in as "admin@example.com"
    And I have enterprise admin role
    When I switch to organization "Sales"
    Then I should be able to see teams from all organizations
    And context scoping should be bypassed
