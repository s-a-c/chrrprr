@browser @api
Feature: Team CRUD Operations
  As an authenticated user
  I want to manage teams
  So that I can organize my enterprise structure

  Background:
    Given the application is running
    And I am logged in as "admin@example.com"
    And there is an Enterprise named "Acme Corp"

  Scenario: Create Enterprise team
    Given I am on "/teams/create"
    When I select "Enterprise" from "type"
    And I fill in "name" with "New Enterprise"
    And I press "Create"
    Then I should see "New Enterprise" in the list
    And the team should be of type "Enterprise"

  Scenario: Create Organization under Enterprise
    Given there is an Enterprise named "Acme Corp"
    And I am on "/teams/create"
    When I select "Organization" from "type"
    And I select "Acme Corp" from "parent_id"
    And I fill in "name" with "Sales Organization"
    And I press "Create"
    Then I should see "Sales Organization" in the list
    And "Sales Organization" should be under "Acme Corp"

  Scenario: View team details
    Given there is an Enterprise named "Acme Corp"
    When I visit the team page for "Acme Corp"
    Then I should see "Acme Corp"
    And I should see team details

  Scenario: Update team information
    Given there is an Enterprise named "Acme Corp"
    When I visit the team page for "Acme Corp"
    And I press "Edit"
    And I fill in "name" with "Updated Enterprise"
    And I press "Save"
    Then I should see "Updated Enterprise"
    And the team name should be "Updated Enterprise"

  Scenario: Delete team with constraints
    Given there is an Enterprise named "Acme Corp"
    And there is an Organization "Sales" under Enterprise "Acme Corp"
    When I try to delete "Acme Corp"
    Then I should see an error message
    And "Acme Corp" should still exist
