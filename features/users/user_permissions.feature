@browser @api
Feature: User Permissions
  As an administrator
  I want to manage user permissions
  So that access control is properly enforced

  Background:
    Given the application is running
    And I am logged in as "admin@example.com"

  Scenario: Access control based on roles
    Given there is a user "user@example.com"
    And user "user@example.com" has role "viewer"
    When I try to access an admin-only resource as "user@example.com"
    Then I should see a 403 error
    And I should see "You do not have permission"

  Scenario: Organization-level permissions
    Given there is an Enterprise named "Acme Corp"
    And there is an Organization "Sales" under Enterprise "Acme Corp"
    And there is a user "user@example.com"
    When I grant "user@example.com" access to organization "Sales"
    Then user "user@example.com" should be able to access "Sales" data
    And user "user@example.com" should not be able to access other organizations

  Scenario: Enterprise-level permissions
    Given there is an Enterprise named "Acme Corp"
    And there is a user "admin@example.com"
    When I assign enterprise admin role to "admin@example.com"
    Then user "admin@example.com" should have access to all organizations in "Acme Corp"
    And user "admin@example.com" should be able to manage enterprise settings

  Scenario: Permission inheritance
    Given there is an Enterprise named "Acme Corp"
    And there is an Organization "Sales" under Enterprise "Acme Corp"
    And there is a Division "North" under Organization "Sales"
    And there is a user "user@example.com"
    When I grant "user@example.com" access to organization "Sales"
    Then user "user@example.com" should have access to "North" division
    And permissions should be inherited from parent organization
