@unit @api
Feature: Query Handler Execution
  As a developer
  I want to execute queries through handlers
  So that data retrieval is properly encapsulated

  Background:
    Given the application is running
    And I am logged in as "admin@example.com"

  Scenario: Successful query execution
    Given there is an Enterprise named "Acme Corp"
    When I execute query "GetTeamQuery" with data:
      """
      {
        "team_id": 1
      }
      """
    Then the query should return a successful Result
    And the Result value should contain team data
    And the team name should be "Acme Corp"

  Scenario: Query result transformation
    Given there is an Enterprise named "Acme Corp"
    And there is an Organization "Sales" under Enterprise "Acme Corp"
    When I execute query "GetTeamQuery" with data:
      """
      {
        "team_id": 1
      }
      """
    Then the Result value should be transformed
    And the Result should contain formatted team data

  Scenario: Query caching
    Given there is an Enterprise named "Acme Corp"
    When I execute query "GetTeamQuery" with data:
      """
      {
        "team_id": 1
      }
      """
    And I execute the same query again
    Then the second query should use cache
    And database queries should be reduced

  Scenario: Error handling in queries
    When I execute query "GetTeamQuery" with data:
      """
      {
        "team_id": 999
      }
      """
    Then the query should return a failed Result
    And the Result should contain error "Team not found"
