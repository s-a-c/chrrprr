@browser @api @unit
Feature: Team Hierarchy Management
  As an authenticated user
  I want to create teams in a valid hierarchy
  So that the organizational structure is maintained

  Background:
    Given the application is running
    And I am logged in as "admin@example.com"
    And there is an Enterprise named "Acme Corp"

  Scenario: Create valid parent-child relationship
    Given there is an Enterprise named "Acme Corp"
    When I create an Organization "Sales" under Enterprise "Acme Corp"
    And I create a Division "North" under Organization "Sales"
    Then "Sales" should be a child of "Acme Corp"
    And "North" should be a child of "Sales"

  Scenario: Prevent invalid hierarchy - cycle
    Given there is an Enterprise named "Acme Corp"
    And there is an Organization "Sales" under Enterprise "Acme Corp"
    When I try to set "Acme Corp" as parent of "Sales"
    Then I should see an error about cycle prevention
    And the hierarchy should remain valid

  Scenario: Validate depth constraints
    Given there is an Enterprise named "Acme Corp"
    And there is an Organization "Sales" under Enterprise "Acme Corp"
    And there is a Division "North" under Organization "Sales"
    And there is a Department "Retail" under Division "North"
    And there is a Project "Q1 Campaign" under Department "Retail"
    When I try to create a team under "Q1 Campaign"
    Then I should see an error about maximum depth
    And the team should not be created

  Scenario: Validate type-specific constraints - Executive/Deputy
    Given there is an Enterprise named "Acme Corp"
    When I try to create an Executive team under "Acme Corp"
    Then I should see an error about Executive constraints
    And the team should not be created

  Scenario: Unique name validation within context
    Given there is an Enterprise named "Acme Corp"
    And there is an Organization "Sales" under Enterprise "Acme Corp"
    When I try to create another Organization "Sales" under Enterprise "Acme Corp"
    Then I should see "The name has already been taken"
    And the duplicate team should not be created
