@browser @api
Feature: Password Reset
  As a user
  I want to reset my password
  So that I can regain access to my account

  Background:
    Given the application is running
    And I am registered as "user@example.com" with password "OldPassword123!"

  Scenario: Request password reset
    Given I am on "/forgot-password"
    When I fill in "email" with "user@example.com"
    And I press "Send Password Reset Link"
    Then I should see "We have emailed your password reset link"

  Scenario: Complete password reset
    Given I am on "/forgot-password"
    And I fill in "email" with "user@example.com"
    And I press "Send Password Reset Link"
    When I follow the password reset link in the email
    And I fill in "password" with "NewPassword123!"
    And I fill in "password_confirmation" with "NewPassword123!"
    And I press "Reset Password"
    Then I should be authenticated
    And I should be able to login with email "user@example.com" and password "NewPassword123!"

  Scenario: Password reset with invalid token fails
    Given I am on "/reset-password/invalid-token"
    When I fill in "password" with "NewPassword123!"
    And I fill in "password_confirmation" with "NewPassword123!"
    And I press "Reset Password"
    Then I should see "This password reset token is invalid"
