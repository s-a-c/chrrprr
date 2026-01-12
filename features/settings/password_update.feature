@browser
Feature: Password Update
  As a user
  I want to change my password
  So that my account remains secure

  Background:
    Given the application is running
    And I am logged in as "user@example.com"

  Scenario: Change password with current password
    Given I am on "/settings/password"
    When I fill in "current_password" with "Password123!"
    And I fill in "password" with "NewPassword123!"
    And I fill in "password_confirmation" with "NewPassword123!"
    And I press "Update Password"
    Then I should see "Password updated successfully"
    And I should be able to login with email "user@example.com" and password "NewPassword123!"

  Scenario: Password confirmation required
    Given I am on "/settings/password"
    When I fill in "current_password" with "Password123!"
    And I fill in "password" with "NewPassword123!"
    And I fill in "password_confirmation" with "DifferentPassword123!"
    And I press "Update Password"
    Then I should see "The password confirmation does not match"
    And the password should not be updated

  Scenario: Password strength validation
    Given I am on "/settings/password"
    When I fill in "current_password" with "Password123!"
    And I fill in "password" with "weak"
    And I fill in "password_confirmation" with "weak"
    And I press "Update Password"
    Then I should see password strength requirements
    And the password should not be updated
