@browser @api
Feature: Team Move Operations
  As an authenticated user
  I want to move teams to different parents
  So that I can reorganize the structure

  Background:
    Given the application is running
    And I am logged in as "admin@example.com"
    And there is an Enterprise named "Acme Corp"
    And there is an Organization "Sales" under Enterprise "Acme Corp"
    And there is an Organization "Marketing" under Enterprise "Acme Corp"
    And there is a Division "North" under Organization "Sales"

  Scenario: Move team to new parent
    Given there is a Division "North" under Organization "Sales"
    When I move team "North" to parent "Marketing"
    Then "North" should be under "Marketing"
    And "North" should not be under "Sales"

  Scenario: Move approval workflow
    Given there is a Division "North" under Organization "Sales"
    When I request to move team "North" to parent "Marketing"
    Then a move approval should be created
    When the move is approved
    Then "North" should be under "Marketing"

  Scenario: Prevent cycles during move
    Given there is an Enterprise named "Acme Corp"
    And there is an Organization "Sales" under Enterprise "Acme Corp"
    And there is a Division "North" under Organization "Sales"
    When I try to move "Sales" to parent "North"
    Then I should see an error about cycle prevention
    And "Sales" should remain under "Acme Corp"

  Scenario: Update tenant_id on move
    Given there is an Enterprise named "Acme Corp"
    And there is an Enterprise named "Beta Corp"
    And there is an Organization "Sales" under Enterprise "Acme Corp"
    When I move team "Sales" to parent "Beta Corp"
    Then "Sales" should have tenant_id of "Beta Corp"

  Scenario: Audit trail for moves
    Given there is a Division "North" under Organization "Sales"
    When I move team "North" to parent "Marketing"
    Then an audit log entry should be created
    And the log should contain "Team moved from Sales to Marketing"
