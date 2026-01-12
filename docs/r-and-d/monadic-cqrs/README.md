# Monadic CQRS Architecture Documentation

Complete documentation for the Monadic CQRS architecture implementation in Laravel 12.

## Overview

This architecture implements a comprehensive monadic CQRS (Command Query Responsibility Segregation) system that combines three functional programming patterns:

- **Error Monad**: Handles success/failure states without exceptions
- **Option Monad**: Handles null/missing data without null checks  
- **Writer Monad**: Carries audit logs alongside computation results

All three are unified in a single `Result` class, providing a pragmatic approach to functional programming in Laravel.

## Documentation Structure

### Core Documentation

- **[Onboarding Guide](onboarding.md)**: Start here for new developers
  - Core philosophy and principles
  - Railway-oriented programming concepts
  - Common patterns and examples
  - Integration with Filament and Livewire

- **[Architecture Documentation](architecture.md)**: System design and decisions
  - Component relationships
  - Data flow diagrams
  - Integration strategies
  - Performance considerations

- **[API Reference](api-reference.md)**: Complete method documentation
  - Result class methods
  - BaseHandler methods
  - Integration trait methods
  - Filament action methods

- **[Cheat Sheet](cheat-sheet.md)**: Quick reference guide
  - Common patterns
  - Method comparison table
  - Integration examples
  - Common mistakes to avoid

- **[Migration Guide](migration.md)**: Converting existing code
  - Action to Handler migration
  - Filament resource migration
  - Livewire component migration
  - Coexistence strategies

### Integration Examples

- **[Filament Integration](examples/filament-integration.md)**: Filament v5 examples
  - Resource page integration
  - Custom actions with Result
  - Table actions
  - Form validation

- **[Livewire Integration](examples/livewire-integration.md)**: Livewire 4 SFC examples
  - Component integration
  - Query handler usage
  - Real-time validation
  - Flux UI notifications

- **[Verbs Event Sourcing Integration](examples/verbs-integration.md)**: Event sourcing with hirethunk/verbs
  - Event creation and validation
  - Command handler integration
  - Projections and read models
  - Writer monad vs Verbs events

## Quick Start

### 1. Create a Command Handler

```php
// app/Handlers/Commands/Teams/CreateTeamCommand.php
readonly class CreateTeamCommand
{
    public function __construct(
        public array $data
    ) {}
}

// app/Handlers/Commands/Teams/CreateTeamHandler.php
final class CreateTeamHandler extends BaseHandler implements CommandHandler
{
    public function handle(object $command): Result
    {
        if (!$command instanceof CreateTeamCommand) {
            return Result::failure('Invalid command type');
        }

        // Tenant context is already initialized via middleware
        // BelongsToTenant trait automatically scopes queries
        // Handler sets tenant_id correctly (inherited from parent)
        return Result::try(
            fn() => $this->createTeam($command),
            ['Starting team creation']
        );
    }

    private function createTeam(CreateTeamCommand $command): Team
    {
        // Use $this->guard() and $this->ensureFound() for validation
        // Team creation automatically sets tenant_id from tenant context
        return Team::create($command->data);
    }
}
```

### 2. Use in Filament

```php
protected function handleRecordCreation(array $data): Model
{
    $handler = resolve(CreateTeamHandler::class);
    $result = $handler->handle(new CreateTeamCommand($data))->logInternal();
    
    return $result->match(
        onSuccess: fn($team) => $team,
        onFailure: fn($error) => throw new \RuntimeException($error)
    );
}
```

### 3. Use in Livewire

```php
use App\Livewire\Concerns\HandlesResults;

class MyComponent extends Component
{
    use HandlesResults;
    
    public function submit(): void
    {
        $this->executeCommand($handler, $command, 'Success!');
    }
}
```

## Key Features

- ✅ **PHP 8.5 Property Hooks**: Computed properties without method calls
- ✅ **Collection Proxy**: Direct Laravel Collection method calls
- ✅ **Automatic Audit Trails**: Writer monad captures execution path
- ✅ **Exception Safety**: `Result::try()` lifts exceptions into Result
- ✅ **Concurrent Operations**: `AsyncResult::all()` for parallel tasks
- ✅ **Event Sourcing**: Verbs integration for persistent audit trails
- ✅ **Filament Integration**: Automatic notification handling
- ✅ **Livewire Integration**: Trait-based Result handling
- ✅ **Blade Directives**: `@success`, `@failure`, `@audit` directives
- ✅ **Global Exception Bridge**: API exceptions → Result failures

## Architecture Components

```
app/
├── Support/
│   ├── Result.php          # Core Result monad
│   └── AsyncResult.php     # Concurrent operations
├── Events/
│   └── Teams/
│       └── TeamCreated.php  # Verbs events
├── Projections/
│   └── Teams/
│       ├── TeamProjection.php  # Read model projection
│       └── TeamProjector.php   # Event handler/projector
├── Handlers/
│   ├── BaseHandler.php      # Common handler helpers
│   ├── Commands/            # Command handlers
│   └── Queries/             # Query handlers
├── Contracts/
│   ├── CommandHandler.php   # Command interface
│   └── QueryHandler.php     # Query interface
├── Livewire/
│   └── Concerns/
│       └── HandlesResults.php  # Livewire trait
├── Filament/
│   └── Actions/
│       └── ResultAction.php     # Filament base action
└── Providers/
    ├── ResultServiceProvider.php      # Logging & Telescope
    └── MonadBladeServiceProvider.php   # Blade directives
```

## Testing

All monadic laws are validated with Pest tests:

```bash
php artisan test tests/Unit/Support/ResultTest.php
```

Tests validate:
- Left Identity Law
- Right Identity Law  
- Associativity Law
- Writer log accumulation
- Collection proxy
- AsyncResult parallel execution

## Quality Enforcement

Mago configuration enforces:
- Handler return types (must return Result)
- Handler structure (must extend BaseHandler)
- Handler interfaces (must implement CommandHandler/QueryHandler)
- Namespace dependencies

## Next Steps

1. Read the [Onboarding Guide](onboarding.md) for core concepts
2. Review [Architecture Documentation](architecture.md) for system design
3. Check [API Reference](api-reference.md) for method details
4. Keep [Cheat Sheet](cheat-sheet.md) handy while coding
5. Follow [Migration Guide](migration.md) when converting code

## Related Documentation

- [Monadic CQRS Conversation](./monadic-cqrs-in-laravel-conversation.md): Original design discussion
- [Folio & Livewire Integration](../folio-livewire-sfc-integration/readme.md): SFC integration details
