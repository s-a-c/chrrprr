@browser
Feature: Appearance Settings
  As a user
  I want to customize my appearance settings
  So that the interface matches my preferences

  Background:
    Given the application is running
    And I am logged in as "user@example.com"

  Scenario: Toggle dark mode
    Given I am on "/settings/appearance"
    When I toggle dark mode
    Then the interface should be in dark mode
    When I toggle dark mode again
    Then the interface should be in light mode

  Scenario: Theme persistence
    Given I am on "/settings/appearance"
    When I toggle dark mode
    And I navigate to "/dashboard"
    Then the interface should still be in dark mode
    When I logout
    And I login with email "user@example.com" and password "Password123!"
    Then the interface should still be in dark mode
