@unit
Feature: Event Sourcing with Verbs
  As a developer
  I want to use event sourcing
  So that I have a complete audit trail

  Background:
    Given the application is running

  Scenario: Event creation and validation
    Given there is an Enterprise named "Acme Corp"
    When I create a TeamCreated event with:
      """
      {
        "type": "organization",
        "name": "Sales",
        "parent_id": 1
      }
      """
    Then the event should be valid
    And the event should be ready to fire

  Scenario: Projection updates
    Given there is an Enterprise named "Acme Corp"
    When I fire a TeamCreated event:
      """
      {
        "type": "organization",
        "name": "Sales",
        "parent_id": 1
      }
      """
    Then the TeamProjection should be updated
    And the projection should contain "Sales"

  Scenario: Event replay
    Given there are multiple TeamCreated events
    When I replay events
    Then the projections should be rebuilt
    And all teams should be in the projection

  Scenario: Event validation rules
    Given there is an Enterprise named "Acme Corp"
    When I try to create a TeamCreated event with invalid data:
      """
      {
        "type": "organization",
        "name": "Sales",
        "parent_id": 999
      }
      """
    Then the event validation should fail
    And the event should not be fired
    And an error should be returned
