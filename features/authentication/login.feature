@browser @api
Feature: User Login
  As a registered user
  I want to log in to my account
  So that I can access the application

  Background:
    Given the application is running
    And I am registered as "user@example.com" with password "Password123!"

  Scenario: Successful login
    Given I am on "/login"
    When I fill in "email" with "user@example.com"
    And I fill in "password" with "Password123!"
    And I press "Log in"
    Then I should be authenticated
    And I should be on "/dashboard"

  Scenario: Login with invalid credentials fails
    Given I am on "/login"
    When I fill in "email" with "user@example.com"
    And I fill in "password" with "WrongPassword"
    And I press "Log in"
    Then I should see the login form
    And I should see "These credentials do not match our records"
    And I should not be authenticated

  Scenario: Remember me functionality
    Given I am on "/login"
    When I fill in "email" with "user@example.com"
    And I fill in "password" with "Password123!"
    And I check "remember"
    And I press "Log in"
    Then I should be authenticated
    And the session should persist
