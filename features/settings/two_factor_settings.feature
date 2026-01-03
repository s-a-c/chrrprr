@browser
Feature: Two-Factor Settings
  As a user
  I want to manage my two-factor authentication settings
  So that I can secure my account

  Background:
    Given the application is running
    And I am logged in as "user@example.com"

  Scenario: Enable two-factor authentication in settings
    Given I am on "/settings/two-factor"
    When I press "Enable Two-Factor Authentication"
    Then I should see a QR code
    And I should see recovery codes
    When I enter the verification code
    And I press "Confirm"
    Then two-factor authentication should be enabled

  Scenario: View recovery codes
    Given two-factor authentication is enabled for "user@example.com"
    And I am on "/settings/two-factor"
    When I click "View Recovery Codes"
    Then I should see my recovery codes
    And I should be able to copy them

  Scenario: Regenerate recovery codes
    Given two-factor authentication is enabled for "user@example.com"
    And I am on "/settings/two-factor"
    When I click "Regenerate Recovery Codes"
    Then I should see new recovery codes
    And the old recovery codes should be invalid

  Scenario: Disable two-factor authentication
    Given two-factor authentication is enabled for "user@example.com"
    And I am on "/settings/two-factor"
    When I press "Disable Two-Factor Authentication"
    And I confirm the action
    Then two-factor authentication should be disabled
