<!--
Sync Impact Report:
Version: 1.0.0 (initial creation)
Modified principles: N/A (initial)
Added sections: Core Principles, Development Standards, Quality Assurance, Governance
Removed sections: N/A
Templates requiring updates:
  ✅ plan-template.md - Constitution Check section already references constitution
  ✅ spec-template.md - No direct constitution references, structure compatible
  ✅ tasks-template.md - No direct constitution references, structure compatible
  ✅ agent-file-template.md - No direct constitution references
  ✅ checklist-template.md - No direct constitution references
Follow-up TODOs: None
-->

# chrrprr Constitution

## Core Principles

### I. Test-First Development (NON-NEGOTIABLE)

All features MUST be developed using Test-Driven Development (TDD). Tests MUST be written before implementation. The Red-Green-Refactor cycle is strictly enforced. Pest 4 is the primary testing framework. All tests MUST pass before code is considered complete. Test coverage MUST be maintained at minimum 99% for PHP code and 100% type coverage.

**Rationale**: Tests provide confidence in code correctness, enable safe refactoring, and serve as living documentation. TDD ensures features are designed with testability in mind from the start.

### II. Laravel Best Practices

All code MUST follow Laravel conventions and use Laravel's built-in features before introducing external dependencies. Prefer Eloquent relationships over raw queries. Use Form Requests for validation. Leverage Laravel's service container for dependency injection. Follow Laravel 12's streamlined structure (no middleware files, use bootstrap/app.php for configuration).

**Rationale**: Laravel conventions ensure consistency, maintainability, and leverage framework optimizations. Following Laravel patterns makes code predictable for other developers.

### III. Type Safety

All methods and functions MUST have explicit return type declarations. All parameters MUST have appropriate type hints. PHPStan level 9 analysis MUST pass. Type coverage MUST be 100%. Use strict types (`declare(strict_types=1);`) in all PHP files.

**Rationale**: Type safety catches errors at development time, improves IDE support, and makes code self-documenting. Strict types prevent subtle bugs from type coercion.

### IV. Code Quality Standards

All code MUST pass Laravel Pint formatting checks. Rector analysis MUST pass with no critical issues. Architecture quality checks via Mago MUST pass. Code MUST be formatted before commit. No linting errors are acceptable.

**Rationale**: Consistent code formatting and quality standards reduce cognitive load, prevent bugs, and ensure codebase maintainability across the team.

### V. Component Reusability

Before creating new components, check for existing reusable components. Prefer Flux UI components when available. Extract repeated patterns into reusable Blade components or Livewire components. Follow DRY (Don't Repeat Yourself) principles.

**Rationale**: Reusable components reduce maintenance burden, ensure UI consistency, and speed up development. Component libraries like Flux UI provide tested, accessible components.

### VI. Documentation and Clarity

Code MUST be self-documenting through clear naming. Use PHPDoc blocks for complex logic. Follow existing code conventions found in sibling files. Prefer descriptive names (e.g., `isRegisteredForDiscounts`) over abbreviations. Comments are only for complex business logic, not obvious code.

**Rationale**: Self-documenting code reduces maintenance time and onboarding effort. Clear naming eliminates the need for most comments while PHPDoc provides IDE support.

## Development Standards

### Technology Stack

- **Framework**: Laravel 12
- **Frontend**: Livewire 4, Flux UI (Free), Tailwind CSS 3
- **Routing**: Laravel Folio (file-based routing)
- **Authentication**: Laravel Fortify
- **Testing**: Pest 4, PHPUnit 12
- **Code Quality**: Laravel Pint, PHPStan, Psalm, Rector, Mago
- **PHP Version**: 8.2+

### Project Structure

Follow Laravel 12's streamlined structure:

- No `app/Http/Middleware/` directory - use `bootstrap/app.php`
- No `app/Console/Kernel.php` - use `bootstrap/app.php` or `routes/console.php`
- Commands auto-register from `app/Console/Commands/`
- Service providers in `bootstrap/providers.php`

### Testing Requirements

- Feature tests for all user-facing functionality
- Unit tests for business logic
- Browser tests for critical user journeys (Pest 4 browser testing)
- Contract tests for API endpoints
- Integration tests for inter-service communication
- All tests MUST be independent and runnable in isolation

## Quality Assurance

### Pre-Commit Checks

Before committing code, ensure:

1. All tests pass (`php artisan test`)
2. Linting passes (`composer run lint`)
3. Type analysis passes (`composer run lint:types`)
4. Code is formatted (`composer run lint:pint:fix`)
5. Architecture checks pass (`composer run lint:architecture`)

### Code Review Requirements

All pull requests MUST:

- Pass all automated checks
- Include tests for new functionality
- Update documentation if behavior changes
- Follow existing code patterns and conventions
- Be reviewed for compliance with this constitution

## Governance

This constitution supersedes all other development practices and guidelines. All code changes MUST comply with these principles.

### Amendment Process

1. Proposed amendments MUST be documented with rationale
2. Amendments require review and approval
3. Version MUST be incremented per semantic versioning:
   - **MAJOR**: Backward incompatible principle removals or redefinitions
   - **MINOR**: New principles added or materially expanded guidance
   - **PATCH**: Clarifications, wording improvements, typo fixes
4. All dependent templates and documentation MUST be updated
5. Sync Impact Report MUST be generated and included in the constitution file

### Compliance Review

- All PRs/reviews MUST verify constitution compliance
- Complexity violations MUST be justified in plan.md
- Use Laravel Boost guidelines for runtime development guidance
- Constitution compliance is a gate before Phase 0 research in planning

### Version History

**Version**: 1.0.0 | **Ratified**: 2025-12-22 | **Last Amended**: 2025-12-22
