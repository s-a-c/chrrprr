@browser
Feature: End-to-End Workflows
  As a user
  I want to complete full workflows
  So that I can accomplish my goals

  Background:
    Given the application is running

  Scenario: Complete user registration to team creation
    Given I am on "/register"
    When I register as "newuser@example.com" with password "Password123!"
    Then I should be authenticated
    And I should be on "/dashboard"
    When I create an Enterprise named "My Enterprise"
    Then I should see "My Enterprise" in the teams list
    And I should be able to create organizations under "My Enterprise"

  Scenario: Team hierarchy creation workflow
    Given I am logged in as "admin@example.com"
    And there is an Enterprise named "Acme Corp"
    When I create an Organization "Sales" under Enterprise "Acme Corp"
    And I create a Division "North" under Organization "Sales"
    And I create a Department "Retail" under Division "North"
    Then the hierarchy should be:
      """
      Acme Corp
        Sales
          North
            Retail
      """
    And all teams should have correct tenant_id

  Scenario: Team move approval workflow
    Given I am logged in as "admin@example.com"
    And there is an Enterprise named "Acme Corp"
    And there is an Organization "Sales" under Enterprise "Acme Corp"
    And there is an Organization "Marketing" under Enterprise "Acme Corp"
    And there is a Division "North" under Organization "Sales"
    When I request to move "North" to parent "Marketing"
    Then a move approval should be created
    When I approve the move
    Then "North" should be under "Marketing"
    And an audit log should be created

  Scenario: User context switching workflow
    Given I am logged in as "user@example.com"
    And there is an Enterprise named "Acme Corp"
    And there is an Organization "Sales" under Enterprise "Acme Corp"
    And there is an Organization "Marketing" under Enterprise "Acme Corp"
    And user "user@example.com" has access to organization "Sales"
    And user "user@example.com" has access to organization "Marketing"
    When I switch to organization "Sales"
    Then I should see teams from "Sales"
    When I switch to organization "Marketing"
    Then I should see teams from "Marketing"
    And I should not see teams from "Sales"
