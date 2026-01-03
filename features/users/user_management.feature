@browser @api
Feature: User Management
  As an administrator
  I want to manage users
  So that I can control access to the system

  Background:
    Given the application is running
    And I am logged in as "admin@example.com"

  Scenario: View user profile
    Given there is a user "user@example.com"
    When I visit the user profile for "user@example.com"
    Then I should see user information
    And I should see "user@example.com"

  Scenario: Update user information
    Given there is a user "user@example.com"
    When I visit the user profile for "user@example.com"
    And I press "Edit"
    And I fill in "name" with "Updated Name"
    And I press "Save"
    Then the user name should be "Updated Name"

  Scenario: Assign user to organizations
    Given there is a user "user@example.com"
    And there is an Enterprise named "Acme Corp"
    And there is an Organization "Sales" under Enterprise "Acme Corp"
    When I assign user "user@example.com" to organization "Sales"
    Then user "user@example.com" should have access to organization "Sales"

  Scenario: User role management
    Given there is a user "user@example.com"
    When I assign role "enterprise_admin" to user "user@example.com"
    Then user "user@example.com" should have role "enterprise_admin"

  Scenario: User deletion constraints for key roles
    Given there is a user "admin@example.com"
    And user "admin@example.com" has role "super_admin"
    When I try to delete user "admin@example.com"
    Then I should see an error about key role protection
    And user "admin@example.com" should still exist
