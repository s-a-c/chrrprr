@browser
Feature: Two-Factor Authentication
  As a user
  I want to enable two-factor authentication
  So that my account is more secure

  Background:
    Given the application is running
    And I am logged in as "user@example.com"

  Scenario: Enable two-factor authentication
    Given I am on "/settings/two-factor"
    When I press "Enable Two-Factor Authentication"
    Then I should see a QR code
    And I should see recovery codes
    When I enter the verification code
    And I press "Confirm"
    Then two-factor authentication should be enabled

  Scenario: Login with two-factor authentication
    Given two-factor authentication is enabled for "user@example.com"
    And I am on "/login"
    When I fill in "email" with "user@example.com"
    And I fill in "password" with "Password123!"
    And I press "Log in"
    Then I should see "Enter your authentication code"
    When I enter the verification code
    And I press "Verify"
    Then I should be authenticated

  Scenario: Use recovery code
    Given two-factor authentication is enabled for "user@example.com"
    And I have recovery codes
    And I am on "/login"
    When I fill in "email" with "user@example.com"
    And I fill in "password" with "Password123!"
    And I press "Log in"
    And I click "Use a recovery code"
    And I enter a recovery code
    And I press "Verify"
    Then I should be authenticated
