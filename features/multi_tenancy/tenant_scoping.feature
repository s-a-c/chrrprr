@unit @api
Feature: Tenant Scoping
  As a system
  I want to scope queries to tenants
  So that data isolation is maintained

  Background:
    Given the application is running

  Scenario: Team queries scoped to tenant
    Given there is a tenant named "Acme Corp"
    And there is a tenant named "Beta Corp"
    And I am in tenant context "Acme Corp"
    When I create a team "Sales" in current tenant
    And I switch to tenant context "Beta Corp"
    When I query teams
    Then I should not see team "Sales"
    And all returned teams should belong to "Beta Corp"

  Scenario: User queries scoped to tenant
    Given there is a tenant named "Acme Corp"
    And there is a tenant named "Beta Corp"
    And I am in tenant context "Acme Corp"
    When I create a user "user@acme.com" in current tenant
    And I switch to tenant context "Beta Corp"
    When I query users
    Then I should not see user "user@acme.com"
    And all returned users should belong to "Beta Corp"

  Scenario: Cross-tenant data leakage prevention
    Given there is a tenant named "Acme Corp"
    And there is a tenant named "Beta Corp"
    And I am in tenant context "Acme Corp"
    When I create sensitive data in current tenant
    And I switch to tenant context "Beta Corp"
    Then I should not see data from "Acme Corp"
    And queries should not return cross-tenant data
    And database queries should include tenant_id filter
