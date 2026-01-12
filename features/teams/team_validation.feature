@unit @api
Feature: Team Validation
  As a system
  I want to validate team operations
  So that data integrity is maintained

  Background:
    Given the application is running
    And I am logged in as "admin@example.com"
    And there is an Enterprise named "Acme Corp"

  Scenario: Validate unique name within context
    Given there is an Organization "Sales" under Enterprise "Acme Corp"
    When I try to create another Organization "Sales" under Enterprise "Acme Corp"
    Then I should see "The name has already been taken"
    And the duplicate should not be created

  Scenario: Validate hierarchy depth
    Given there is an Enterprise named "Acme Corp"
    And the hierarchy is at maximum depth
    When I try to create another level
    Then I should see "Maximum hierarchy depth exceeded"
    And the team should not be created

  Scenario: Validate parent type compatibility
    Given there is an Enterprise named "Acme Corp"
    When I try to create an Organization under a Division
    Then I should see "Invalid parent type"
    And the team should not be created

  Scenario: Validate cycle prevention
    Given there is an Enterprise named "Acme Corp"
    And there is an Organization "Sales" under Enterprise "Acme Corp"
    When I try to set "Sales" as parent of "Acme Corp"
    Then I should see "Cycle detected in hierarchy"
    And the operation should be rejected
