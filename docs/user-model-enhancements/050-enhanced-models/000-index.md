# Enhanced User and Team Models Documentation

## Overview

This documentation suite provides comprehensive implementation guides for enhanced User and Team models with ULID primary keys, translatable slugs, Enterprise-based tenancy with fine-grained scoping, and complete testing infrastructure.

## Key Features

- **ULID Primary Keys**: All models use ULID as route key (keeping integer `id` for relationships)
- **Translatable Slugs**: Using `cviebrock/eloquent-sluggable` + `spatie/laravel-translatable`
- **Enterprise-Based Tenancy**: Enterprise as tenant with fine-grained Organisation/Division/Department scoping
- **Enhanced User Model**: ULID, translatable slugs, tenant relationships, states, and statuses
- **BDD/TDD Approach**: Behavior-Driven Development with Behat, Test-Driven Development with Pest
- **100% Test Coverage**: Complete test coverage for both PHP and JavaScript code
- **Maximum Quality Standards**: PHPStan level 9, ESLint strict mode, 100% test pass rate

## Documentation Structure

### Core Documentation

- **[Overview & Architecture](010-overview.md)** - System overview, architecture decisions, and design rationale
- **[Business Rules & Validation](015-business-rules.md)** - Complete business rules governing the Team hierarchy and User model
- **[Package Installation Guide](020-package-installation.md)** - Step-by-step installation commands and configuration
- **[Composer Configuration](030-composer-configuration.md)** - Complete `composer.json` with all required packages

### Implementation Guides

- **[Database Setup](040-database-setup.md)** - Migration files, schema design, and PostgreSQL optimizations
- **[Traits Implementation](050-traits-implementation.md)** - Reusable traits (HasUlid, HasTranslatableAttributes, HasTranslatableSlug)
- **[Enums, States & Statuses](060-enums-states-statuses.md)** - All enum classes and state/status implementations
- **[Models Implementation](070-models-implementation.md)** - User and Team model implementations with trait composition

### Integration Guides

- **[Tenancy Setup](080-tenancy-setup.md)** - Enterprise tenant configuration and context scoping
- **[Filament Integration](090-filament-integration.md)** - Filament v5 panel setup and plugin configuration
- **[Security Implementation](095-security-implementation.md)** - Authorization policies, guards, and security features

### Testing & Quality

- **[Testing Infrastructure](100-testing-infrastructure.md)** - Behat, Pest, coverage, and static analysis setup
- **[Test Examples](110-test-examples.md)** - Complete test examples for all components
- **[Quality Assurance](120-quality-assurance.md)** - Code coverage, static analysis, and CI/CD configuration

### Reference & Diagrams

- **[Architecture Diagrams](130-architecture-diagrams.md)** - Mermaid diagrams for infrastructure, architecture, and data flows
- **[Implementation Reference](140-implementation-reference.md)** - Quick reference for common patterns and code snippets
- **[FAQ & Troubleshooting](150-faq-troubleshooting.md)** - Common issues and solutions

## Quick Start

1. Start with [Overview & Architecture](010-overview.md) to understand the system design
2. Review [Business Rules & Validation](015-business-rules.md) to understand requirements
3. Follow [Package Installation Guide](020-package-installation.md) to set up dependencies
4. Use [Database Setup](040-database-setup.md) to create the schema
5. Implement traits and models following the implementation guides
6. Set up testing infrastructure using [Testing Infrastructure](100-testing-infrastructure.md)

## Related Documentation

- [User Model Enhancements PRD](../010-ume-prd.md) - Original product requirements
- [Multi-Tenancy Implementation](../040-multi-tenancy/) - Multi-tenancy documentation
- [Single Table Inheritance](../050-ume-single-table-inheritance.md) - STI pattern documentation
