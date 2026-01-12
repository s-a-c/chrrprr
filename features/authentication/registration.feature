@browser @api
Feature: User Registration
  As a visitor
  I want to register for an account
  So that I can access the application

  Background:
    Given the application is running

  Scenario: Successful user registration
    Given I am on "/register"
    When I fill in "name" with "John Doe"
    And I fill in "email" with "john@example.com"
    And I fill in "password" with "Password123!"
    And I fill in "password_confirmation" with "Password123!"
    And I press "Register"
    Then I should be authenticated
    And I should see "Dashboard"

  Scenario: Registration with existing email fails
    Given I am registered as "existing@example.com" with password "Password123!"
    And I am on "/register"
    When I fill in "name" with "Jane Doe"
    And I fill in "email" with "existing@example.com"
    And I fill in "password" with "Password123!"
    And I fill in "password_confirmation" with "Password123!"
    And I press "Register"
    Then I should see "The email has already been taken"
    And I should not be authenticated
