@unit @api
Feature: Command Handler Execution
  As a developer
  I want to execute commands through handlers
  So that business logic is properly encapsulated

  Background:
    Given the application is running
    And I am logged in as "admin@example.com"

  Scenario: Successful command execution
    Given there is an Enterprise named "Acme Corp"
    When I execute command "CreateTeamCommand" with data:
      """
      {
        "type": "organization",
        "name": "Sales",
        "parent_id": 1
      }
      """
    Then the command should return a successful Result
    And a team "Sales" should be created
    And an event "TeamCreated" should be fired

  Scenario: Command validation failures
    When I execute command "CreateTeamCommand" with data:
      """
      {
        "type": "organization",
        "name": ""
      }
      """
    Then the command should return a failed Result
    And the Result should contain error "name"
    And no team should be created

  Scenario: Transaction rollback on failure
    Given there is an Enterprise named "Acme Corp"
    When I execute command "CreateTeamCommand" with data that causes an error:
      """
      {
        "type": "organization",
        "name": "Sales",
        "parent_id": 999
      }
      """
    Then the command should return a failed Result
    And no database changes should be persisted
    And the transaction should be rolled back

  Scenario: Audit trail generation
    Given there is an Enterprise named "Acme Corp"
    When I execute command "CreateTeamCommand" with data:
      """
      {
        "type": "organization",
        "name": "Sales",
        "parent_id": 1
      }
      """
    Then the Result should contain logs:
      """
      Starting team creation
      """

  Scenario: Event firing with Verbs integration
    Given there is an Enterprise named "Acme Corp"
    When I execute command "CreateTeamCommand" with data:
      """
      {
        "type": "organization",
        "name": "Sales",
        "parent_id": 1
      }
      """
    Then an event "TeamCreated" should be fired
    And the event should contain type "organization"
    And the event should contain name "Sales"
