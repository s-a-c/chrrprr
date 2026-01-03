---
name: Monadic CQRS Implementation
overview: Implement a comprehensive monadic CQRS architecture for Laravel 12 with Result/AsyncResult monads, integrating seamlessly with Filament v5 resources and Livewire 4 SFCs, including full documentation and migration path from existing Actions.
todos:
  - id: core-result
    content: Create Result monad class with PHP 8.5 features (property hooks, readonly, collection proxy)
    status: completed
  - id: core-async-result
    content: Create AsyncResult class extending Result for concurrent operations
    status: completed
  - id: core-base-handler
    content: Create BaseHandler abstract class with ensureFound() and guard() helpers
    status: completed
  - id: core-contracts
    content: Create CommandHandler and QueryHandler interfaces
    status: completed
  - id: test-monadic-laws
    content: Write Pest tests validating monadic laws (Identity, Associativity, Writer accumulation)
    status: completed
  - id: mago-config
    content: Configure Mago rules to enforce Result return types and prevent null returns
    status: completed
  - id: provider-result
    content: Create ResultServiceProvider with logInternal() macro and Telescope integration
    status: completed
  - id: provider-blade
    content: Create MonadBladeServiceProvider with @success, @failure, @audit directives
    status: completed
  - id: global-exception-bridge
    content: Update bootstrap/app.php to convert exceptions to Result::failure
    status: completed
  - id: filament-result-action
    content: Create ResultAction base class for Filament actions
    status: completed
  - id: filament-resource-example
    content: Migrate UserResource or TeamResource to use CQRS handlers
    status: completed
  - id: livewire-handles-results
    content: Create HandlesResults trait for Livewire components
    status: completed
  - id: livewire-example
    content: Refactor MoveTeam Livewire component to use Result monads
    status: completed
  - id: folio-bridge-enhancement
    content: Enhance FolioServiceProvider to handle Result objects
    status: completed
  - id: docs-onboarding
    content: Write comprehensive developer onboarding guide
    status: completed
  - id: docs-architecture
    content: Create architecture documentation with diagrams
    status: completed
  - id: docs-api-reference
    content: Write API reference documentation
    status: completed
  - id: docs-cheat-sheet
    content: Create quick reference cheat sheet
    status: completed
  - id: docs-migration
    content: Write migration guide with examples
    status: completed
  - id: example-command-handler
    content: Create example command handler (migrate CreateTeam Action)
    status: completed
  - id: example-query-handler
    content: Create example query handler
    status: completed
---

# Monadic CQRS Architecture Implementation Plan

## Overview

This plan implements a comprehensive monadic CQRS (Command Query Responsibility Segregation) architecture for Laravel 12, integrating the Result monad (combining Error, Option, and Writer patterns) with Filament v5 and Livewire 4 SFCs. The architecture treats errors as data, eliminates null returns, and provides automatic audit trails through the Writer monad.

## Architecture Components

### Core Monadic Infrastructure

1. **Result Monad** (`app/Support/Result.php`)

  - PHP 8.5 readonly class with property hooks
  - Combines Error (success/failure), Option (null handling), and Writer (audit logs)
  - Collection proxy via `__call` magic method
  - `flatMap`, `map`, `match` methods for railway-oriented programming
  - `Result::try()` for exception lifting

2. **AsyncResult** (`app/Support/AsyncResult.php`)

  - Extends Result for concurrent operations
  - `AsyncResult::all()` for parallel command/query execution
  - Automatic log merging from parallel threads

3. **BaseHandler** (`app/Handlers/BaseHandler.php`)

  - Abstract base class for all CQRS handlers
  - `ensureFound()` for Option monad behavior (404 handling)
  - `guard()` for Error monad behavior (business rule validation)
  - Helper methods for common patterns

4. **Contracts** (`app/Contracts/`)

  - `CommandHandler` interface: `handle(object $command): Result`
  - `QueryHandler` interface: `ask(object $query): Result`

### Service Providers & Integration

5. **ResultServiceProvider** (`app/Providers/ResultServiceProvider.php`)

  - Registers `logInternal()` macro
  - Automatic Writer log flushing to Laravel logs
  - Telescope integration hook

6. **MonadBladeServiceProvider** (`app/Providers/MonadBladeServiceProvider.php`)

  - `@success($result)` and `@failure($result)` directives
  - `@audit($result)` for Writer monad display
  - Clean view rendering without if/else spaghetti

7. **Global Exception Bridge** (`bootstrap/app.php`)

  - Converts unhandled exceptions to `Result::failure`
  - API response transformation
  - Maintains Writer log integrity

### Filament Integration

8. **Filament Action Integration**

  - Custom Filament Actions that return `Result` objects
  - `ResultAction` base class for Filament actions
  - Automatic notification/error handling from Result state
  - Integration with Filament's action modals

9. **Filament Resource Integration**

  - Resource pages (Create/Edit) use CQRS handlers
  - Form submission returns `Result`, handled in page classes
  - Table actions (delete, bulk actions) use Result monads
  - Error display via Filament notifications

10. **Filament Form Components**

  - Custom form components that work with Result
  - Validation integration (Form Request → Command → Result)
  - Real-time error display

### Livewire 4 SFC Integration

11. **Livewire Component Base** (`app/Livewire/Concerns/HandlesResults.php`)

  - Trait for Livewire components to handle Result objects
  - Automatic error display via Flux UI notifications
  - Loading states via `wire:loading` directives
  - Writer log display in debug mode

12. **Folio + Livewire Bridge Enhancement**

  - Update `FolioServiceProvider` to handle Result objects
  - Automatic Result → HTTP response conversion
  - Integration with existing Folio/Livewire SFC bridge

13. **Livewire Actions with Result**

  - Example: `MoveTeam` Livewire component refactored to use Result
  - Form submission handlers return Result
  - Real-time validation feedback

### Testing & Quality

14. **Pest Test Suite** (`tests/Unit/Support/ResultTest.php`)

  - Monadic law validation (Identity, Associativity)
  - Writer log accumulation tests
  - Collection proxy tests
  - AsyncResult parallel execution tests

15. **Mago Configuration** (`mago.toml`)

  - Enforce Result return types on handlers
  - Prevent null returns
  - Prevent exception throwing in handlers
  - Architecture enforcement rules

### Documentation

16. **Developer Onboarding Guide** (`docs/monadic-cqrs/onboarding.md`)

  - Core philosophy explanation
  - Railway-oriented programming concepts
  - Common patterns and examples
  - Migration guide from Actions to CQRS

17. **Architecture Documentation** (`docs/monadic-cqrs/architecture.md`)

  - System overview and design decisions
  - Component relationships
  - Data flow diagrams
  - Integration points

18. **API Reference** (`docs/monadic-cqrs/api-reference.md`)

  - Result class methods
  - BaseHandler methods
  - Filament integration examples
  - Livewire integration examples

19. **Cheat Sheet** (`docs/monadic-cqrs/cheat-sheet.md`)

  - Quick reference for common patterns
  - Method comparison table
  - Common mistakes to avoid

20. **Migration Guide** (`docs/monadic-cqrs/migration.md`)

  - Converting existing Actions to CQRS handlers
  - Filament resource migration examples
  - Livewire component migration examples
  - Coexistence strategy (Actions + CQRS)

## Implementation Phases

### Phase 1: Core Infrastructure (Foundation)

- [ ] Create `Result` class with PHP 8.5 features
- [ ] Create `AsyncResult` class
- [ ] Create `BaseHandler` abstract class
- [ ] Create `CommandHandler` and `QueryHandler` interfaces
- [ ] Write Pest tests for monadic laws
- [ ] Configure Mago rules

### Phase 2: Service Providers & Global Integration

- [ ] Create `ResultServiceProvider` with logging macros
- [ ] Create `MonadBladeServiceProvider` with directives
- [ ] Update `bootstrap/app.php` exception handler
- [ ] Add Telescope integration (optional)
- [ ] Test global exception → Result conversion

### Phase 3: Filament Integration

- [ ] Create `ResultAction` base class for Filament
- [ ] Create example Filament resource using CQRS
- [ ] Update existing `UserResource` to use Result handlers
- [ ] Update existing `TeamResource` to use Result handlers
- [ ] Create Filament form validation → Result bridge
- [ ] Document Filament integration patterns

### Phase 4: Livewire 4 SFC Integration

- [ ] Create `HandlesResults` trait for Livewire
- [ ] Update `FolioServiceProvider` for Result handling
- [ ] Refactor `MoveTeam` Livewire component as example
- [ ] Create example Folio page using Result
- [ ] Document Livewire integration patterns
- [ ] Test Flux UI notification integration

### Phase 5: Documentation & Examples

- [ ] Write comprehensive onboarding guide
- [ ] Create architecture documentation with diagrams
- [ ] Write API reference documentation
- [ ] Create cheat sheet
- [ ] Write migration guide with examples
- [ ] Add inline code documentation (PHPDoc)

### Phase 6: Example Migrations

- [ ] Migrate `CreateTeam` Action to CQRS handler
- [ ] Migrate `RegisterUser` Action to CQRS handler
- [ ] Create example Query handlers
- [ ] Demonstrate Filament resource using handlers
- [ ] Demonstrate Livewire component using handlers

## Key Design Decisions

### Result Monad Design

- **Single unified object**: Combines Error, Option, and Writer to avoid monad stacking complexity
- **Collection proxy**: Allows direct use of Laravel Collection methods while preserving monad state
- **Property hooks**: PHP 8.5 feature for computed properties (`isSuccess`, `isFailure`)
- **Readonly class**: Ensures immutability

### Filament Integration Strategy

- **Page-level integration**: Filament resource pages call CQRS handlers
- **Action-level integration**: Custom Filament actions return Result objects
- **Notification bridge**: Automatic conversion of Result → Filament notifications
- **Form validation**: Form Requests → Commands → Result pipeline

### Livewire Integration Strategy

- **Trait-based approach**: `HandlesResults` trait provides common functionality
- **Automatic error display**: Result failures → Flux UI notifications
- **Loading states**: Preserved via `wire:loading` directives
- **Folio compatibility**: Works with existing Folio/Livewire SFC bridge

### Migration Strategy

- **Coexistence**: Actions and CQRS handlers can coexist during migration
- **Gradual adoption**: Start with new features, migrate existing code incrementally
- **Backward compatibility**: Existing Actions continue to work
- **Clear examples**: Provide migration examples for common patterns

## File Structure

```
app/
├── Contracts/
│   ├── CommandHandler.php
│   └── QueryHandler.php
├── Handlers/
│   ├── BaseHandler.php
│   ├── Commands/
│   │   ├── Teams/
│   │   │   └── CreateTeamHandler.php
│   │   └── Users/
│   │       └── RegisterUserHandler.php
│   └── Queries/
│       ├── Teams/
│       │   └── GetTeamHandler.php
│       └── Users/
│           └── GetUserHandler.php
├── Livewire/
│   └── Concerns/
│       └── HandlesResults.php
├── Providers/
│   ├── MonadBladeServiceProvider.php
│   └── ResultServiceProvider.php
└── Support/
    ├── Result.php
    └── AsyncResult.php

docs/
└── monadic-cqrs/
    ├── architecture.md
    ├── onboarding.md
    ├── api-reference.md
    ├── cheat-sheet.md
    ├── migration.md
    └── examples/
        ├── filament-integration.md
        └── livewire-integration.md

tests/
└── Unit/
    └── Support/
        ├── ResultTest.php
        └── AsyncResultTest.php
```

## Success Criteria

1. **Core Infrastructure**: Result and AsyncResult classes pass all monadic law tests
2. **Filament Integration**: At least one Filament resource fully uses CQRS handlers
3. **Livewire Integration**: At least one Livewire component uses Result monads
4. **Documentation**: Comprehensive docs covering all integration points
5. **Migration Path**: Clear examples showing how to migrate existing code
6. **Quality**: Mago rules enforce the architecture, Pest tests validate correctness

## Risks & Mitigations

- **Risk**: Learning curve for team members unfamiliar with monads
  - **Mitigation**: Comprehensive onboarding guide, cheat sheet, gradual migration

- **Risk**: Performance overhead of Result wrapping
  - **Mitigation**: PHP 8.5 readonly classes are optimized, minimal overhead in practice

- **Risk**: Integration complexity with Filament/Livewire
  - **Mitigation**: Trait-based approach, clear examples, backward compatibility

- **Risk**: Over-engineering for simple CRUD
  - **Mitigation**: Coexistence strategy allows selective adoption, start with complex business logic
