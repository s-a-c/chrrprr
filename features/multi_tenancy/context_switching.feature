@browser @api
Feature: Context Switching
  As an authenticated user
  I want to switch between organizational contexts
  So that I can work within different parts of my enterprise

  Background:
    Given the application is running
    And I am logged in as "user@example.com"
    And there is an Enterprise named "Acme Corp"
    And there is an Organization "Sales" under Enterprise "Acme Corp"
    And there is an Organization "Marketing" under Enterprise "Acme Corp"
    And user "user@example.com" has access to organization "Sales"
    And user "user@example.com" has access to organization "Marketing"

  Scenario: Switch between accessible organizations
    Given I am on "/teams"
    When I switch to organization "Sales"
    Then my current context should be "Sales"
    When I switch to organization "Marketing"
    Then my current context should be "Marketing"

  Scenario: Prevent access to unauthorized contexts
    Given there is an Organization "Finance" under Enterprise "Acme Corp"
    When I try to switch to organization "Finance"
    Then I should see an error message
    And my context should not change to "Finance"

  Scenario: Context restoration on login
    Given I switch to organization "Sales"
    And I logout
    When I login with email "user@example.com" and password "Password123!"
    Then my current context should be "Sales"

  Scenario: Context persistence
    Given I switch to organization "Sales"
    When I navigate to "/teams"
    Then my current context should be "Sales"
    When I navigate to "/dashboard"
    Then my current context should still be "Sales"
