@unit
Feature: Result Monad Behaviors
  As a developer
  I want to use the Result monad
  So that I can handle success and failure states functionally

  Background:
    Given the application is running

  Scenario: Success Result handling
    When I create a successful Result with value "test"
    Then the Result should be successful
    And the Result value should be "test"

  Scenario: Failure Result handling
    When I create a failed Result with error "Something went wrong"
    Then the Result should be a failure
    And the Result should contain error "Something went wrong"

  Scenario: Result chaining with map
    Given I have a successful Result with value "hello"
    When I map the Result to uppercase
    Then the Result value should be "HELLO"
    And the Result should be successful

  Scenario: Result chaining with flatMap
    Given I have a successful Result with value "test"
    When I flatMap to create another Result
    Then the Result should be successful
    And the Result should contain the transformed value

  Scenario: Writer monad log accumulation
    Given I have a Result with logs "Step 1"
    When I chain another operation with logs "Step 2"
    Then the Result should contain logs:
      """
      Step 1
      Step 2
      """

  Scenario: Collection proxy operations
    Given I have a successful Result with value [1, 2, 3]
    When I call "map" on the Result with callback "multiply by 2"
    Then the Result value should be [2, 4, 6]
    And the Result should be successful

  Scenario: AsyncResult parallel execution
    Given I have multiple async operations
    When I execute them in parallel using AsyncResult
    Then all operations should complete
    And the results should be collected
