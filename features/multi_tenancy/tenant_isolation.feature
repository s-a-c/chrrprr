@unit @api
Feature: Tenant Isolation
  As a system
  I want to isolate data between tenants
  So that tenants cannot access each other's data

  Background:
    Given the application is running

  Scenario: Data isolation between tenants
    Given there is a tenant named "Acme Corp"
    And there is a tenant named "Beta Corp"
    And I am in tenant context "Acme Corp"
    When I create a team "Sales" in current tenant
    And I switch to tenant context "Beta Corp"
    Then I should not see team "Sales"
    And queries should be scoped to tenant "Beta Corp"

  Scenario: Query scoping by tenant_id
    Given there is a tenant named "Acme Corp"
    And I am in tenant context "Acme Corp"
    When I query teams
    Then all returned teams should have tenant_id matching "Acme Corp"
    And I should not see teams from other tenants

  Scenario: Cache isolation
    Given there is a tenant named "Acme Corp"
    And there is a tenant named "Beta Corp"
    And I am in tenant context "Acme Corp"
    When I cache data with key "test"
    And I switch to tenant context "Beta Corp"
    Then cache key "test" should not exist
    And cache should be isolated per tenant

  Scenario: Filesystem isolation
    Given there is a tenant named "Acme Corp"
    And there is a tenant named "Beta Corp"
    And I am in tenant context "Acme Corp"
    When I upload a file "document.pdf"
    And I switch to tenant context "Beta Corp"
    Then I should not see "document.pdf"
    And files should be isolated per tenant

  Scenario: Queue isolation
    Given there is a tenant named "Acme Corp"
    And there is a tenant named "Beta Corp"
    And I am in tenant context "Acme Corp"
    When I dispatch a job
    Then the job should run in tenant context "Acme Corp"
    And the job should not access data from "Beta Corp"
