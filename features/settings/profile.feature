@browser
Feature: Profile Settings
  As a user
  I want to update my profile
  So that my information is current

  Background:
    Given the application is running
    And I am logged in as "user@example.com"

  Scenario: Update profile information
    Given I am on "/settings/profile"
    When I fill in "name" with "New Name"
    And I press "Save"
    Then I should see "Profile updated successfully"
    And my name should be "New Name"

  Scenario: Bio rendering and sanitization
    Given I am on "/settings/profile"
    When I fill in "bio" with "<script>alert('xss')</script>Safe content"
    And I press "Save"
    Then the bio should be sanitized
    And I should not see "<script>"
    And I should see "Safe content"

  Scenario: Translatable attributes
    Given I am on "/settings/profile"
    When I set bio in language "en" to "English bio"
    And I set bio in language "fr" to "Bio en français"
    And I press "Save"
    Then the bio should be translatable
    And I should see "English bio" when language is "en"
    And I should see "Bio en français" when language is "fr"

  Scenario: Profile validation
    Given I am on "/settings/profile"
    When I fill in "name" with ""
    And I press "Save"
    Then I should see validation errors
    And the profile should not be updated
