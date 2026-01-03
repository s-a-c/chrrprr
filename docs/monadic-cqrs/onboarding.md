# Developer Onboarding: The Monadic CQRS Paradigm

Welcome to the project. We use a **Monadic CQRS** architecture. This means we treat business logic as a "Railway" of data rather than a series of commands and exceptions.

## The Core Philosophy

In this project, we follow three strict rules to ensure the system is predictable and auditable:

1. **Never Return Null:** Use the `Result` as an **Option**. If something isn't found, return a `Failure`.
2. **Never Throw Exceptions for Logic:** Use the `Result` as an **Error** monad. Exceptions are only for "unrecoverable" system crashes (e.g., the database is literally on fire).
3. **Logs are Values:** Use the `Result` as a **Writer**. Do not call `Log::info()` inside your logic; append your notes to the `Result` object.

## Thinking in "Railways"

When you write a Handler, imagine you are building a train track. As long as things go well, the "Success" train stays on the main track. If a step fails, the train switches to the "Failure" track and stays there until the end.

### The "Standard" Way (Avoid This)

```php
public function handle($command) {
    $user = User::find($command->id);
    if (!$user) throw new Exception("User not found");

    Log::info("User found");
    $user->update(['status' => 'active']);
}
```

### The "Monadic" Way (Do This)

```php
public function handle($command): Result {
    return $this->ensureFound(User::find($command->id))
        ->flatMap(fn($user) => $this->activate($user))
        ->logInternal(); // The logs are carried inside the result!
}
```

## How to Use the Monad "Toolkit"

### `flatMap` (The Connector)

Use `flatMap` when the next function _also_ returns a `Result`. This "unwraps" the value, processes it, and merges any new logs into the chain.

```php
return $this->findAccount($id)
    ->flatMap(fn($account) => $this->checkBalance($account, $amount))
    ->flatMap(fn($account) => $this->performDebit($account, $amount));
```

### `map` (The Transformer)

Use `map` when you just want to change the data inside (like formatting a string) and you know it won't fail.

```php
return Result::success($user)
    ->map(fn($user) => $user->fullName);
```

### The Collection Proxy

Because our `Result` is a **Proxy Monad**, you can call Laravel Collection methods directly on it.

```php
return Result::success([1, 2, 3])
    ->filter(fn($i) => $i > 1) // No need to unwrap!
    ->first();
```

### `match` (The Unwrapper)

Use `match` in controllers or views to handle both success and failure cases.

```php
return $handler->handle($command)
    ->match(
        onSuccess: fn($data) => response()->json($data, 201),
        onFailure: fn($error) => response()->json(['error' => $error], 422)
    );
```

## Handling Concurrency

If you need to do multiple things at once (like calling three different price APIs), use `AsyncResult`. It handles the complexity of gathering all results and merging all their logs for you.

```php
$result = AsyncResult::all([
    'shipping' => fn() => $this->getShipping(),
    'tax'      => fn() => $this->getTax(),
]);
```

## Summary Table for New Devs

| If you want to... | Use this method |
| --- | --- |
| Chain two steps that might fail | `->flatMap(fn() => ...)` |
| Change the data format | `->map(fn() => ...)` |
| Filter/Pluck/Sort a list | Call it directly on the `Result` |
| Execute things in parallel | `AsyncResult::all([...])` |
| Get the data out in the Controller | `->match(onSuccess: ..., onFailure: ...)` |
| Handle exceptions safely | `Result::try(fn() => ...)` |
| Check if something exists | `$this->ensureFound($model)` |
| Validate a business rule | `$this->guard($condition, $error)` |

## Mago Enforcement

Before you push your code, run `mago lint`. It will check if your return types are correct and ensure you aren't accidentally throwing raw exceptions or using "impure" functions.

## Common Patterns

### Pattern 1: Find or Fail (Option Monad)

```php
public function ask(GetUserQuery $query): Result
{
    $user = User::find($query->id);
    return $this->ensureFound($user, "User not found");
}
```

### Pattern 2: Validate and Process (Error Monad)

```php
public function handle(CreateOrderCommand $command): Result
{
    return $this->guard($command->amount > 0, "Amount must be positive")
        ->flatMap(fn() => $this->checkStock($command->productId))
        ->flatMap(fn() => $this->createOrder($command));
}
```

### Pattern 3: Chain Multiple Operations

```php
public function handle(TransferFundsCommand $command): Result
{
    return $this->findAccount($command->fromId)
        ->flatMap(fn($from) => $this->ensureSolvency($from, $command->amount))
        ->flatMap(fn($from) => $this->findAccount($command->toId))
        ->flatMap(fn($to) => $this->executeTransfer($from, $to, $command->amount))
        ->map(fn($tx) => "Transaction #{$tx->id} completed.");
}
```

## Tenant Context Basics

The application uses **single-database tenancy** where Enterprise serves as the tenant. All handlers operate in a tenant context, which affects how you write queries and access data.

### Understanding Tenant Context

- **Tenant Identification**: Tenants are identified by subdomain (e.g., `acme.yourdomain.com`)
- **Automatic Scoping**: Models using `BelongsToTenant` trait automatically scope queries to the current tenant
- **Handler Context**: Most handlers assume tenant context is already initialized via middleware
- **Central Context**: Only handlers creating Enterprise (tenant) records operate in central context

### Tenant-Aware Handler Pattern

Most handlers you write will operate in tenant context. Queries are automatically scoped:

```php
final class GetTeamHandler extends BaseHandler implements QueryHandler
{
    public function ask(object $query): Result
    {
        // This query is automatically scoped to current tenant
        // You don't need to manually add WHERE tenant_id = ?
        $team = Team::find($query->id); // Auto-scoped to tenant
        return $this->ensureFound($team, 'Team not found');
    }
}
```

### Tenant-Aware Command Handler Pattern

Command handlers that create tenant-scoped resources automatically inherit tenant context:

```php
final class CreateTeamHandler extends BaseHandler implements CommandHandler
{
    public function handle(object $command): Result
    {
        // Tenant context is already initialized via middleware
        // The handler sets tenant_id correctly (inherited from parent)
        // BelongsToTenant trait handles query scoping automatically
        return Result::try(
            fn() => $this->createTeam($command),
            ['Starting team creation']
        );
    }
    
    private function createTeam(CreateTeamCommand $command): Team
    {
        // Team creation automatically sets tenant_id from context
        // No need to manually set tenant_id (handled by model observers/hierarchy)
        return Team::create($command->data);
    }
}
```

### Accessing Parent Relationships

When accessing parent relationships (like `$team->parent`), you may need to bypass tenant scoping:

```php
// If you need to access parent across tenant boundary (rare)
$parent = $team->parent()->withoutGlobalScopes()->first();

// Usually, parent relationships work fine within tenant context
$parent = $team->parent; // Works normally if parent is in same tenant
```

### Central Context Pattern (Enterprise Creation)

Only handlers creating Enterprise records operate in central context (no tenant initialized):

```php
final class CreateEnterpriseHandler extends BaseHandler implements CommandHandler
{
    public function handle(object $command): Result
    {
        // Operates in central context (no tenant initialized)
        // Creates Enterprise with domain
        // Tenant context initialized after creation
        return Result::try(
            fn() => $this->createEnterprise($command),
            ['Creating enterprise tenant']
        );
    }
}
```

### Key Tenant Context Rules

1. **Assume tenant context**: Most handlers should assume tenant context is active
2. **Automatic scoping**: Don't manually add `WHERE tenant_id = ?` - it's automatic
3. **Use tenant() helper**: Access current tenant when needed: `$enterprise = tenant()`
4. **Respect boundaries**: Never leak data across tenant boundaries
5. **Document exceptions**: Clearly document when handlers operate in central context

### Common Tenant Patterns

**Pattern: Tenant-Aware Query**
```php
public function ask(GetTeamQuery $query): Result
{
    // Automatically scoped to current tenant
    $team = Team::find($query->id);
    return $this->ensureFound($team, 'Team not found');
}
```

**Pattern: Tenant-Aware List Query**
```php
public function ask(ListTeamsQuery $query): Result
{
    // All queries automatically scoped to tenant
    $teams = Team::query()
        ->where('type', $query->type)
        ->get();
    
    return Result::success($teams, ['Teams retrieved']);
}
```

**Pattern: Access Current Tenant**
```php
public function handle(object $command): Result
{
    $enterprise = tenant(); // Get current Enterprise tenant
    // Use $enterprise for tenant-specific logic
    return Result::success($enterprise);
}
```

For more details, see the [Architecture Documentation](architecture.md#tenant-context-architecture).

## Integration with Filament

In Filament resources, use CQRS handlers in page classes:

```php
protected function handleRecordCreation(array $data): Model
{
    $handler = resolve(CreateTeamHandler::class);
    $command = new CreateTeamCommand($data);
    
    $result = $handler->handle($command);
    
    return $result->match(
        onSuccess: fn($team) => $team,
        onFailure: fn($error) => throw new \RuntimeException($error)
    );
}
```

## Integration with Livewire

Use the `HandlesResults` trait in Livewire components:

```php
use App\Livewire\Concerns\HandlesResults;

class MoveTeam extends Component
{
    use HandlesResults;
    
    public function submit(): void
    {
        $handler = resolve(MoveTeamHandler::class);
        $command = new MoveTeamCommand(...);
        
        $this->executeCommand($handler, $command, 'Team moved successfully');
    }
}
```

## Next Steps

1. Read the [Architecture Documentation](architecture.md) for system overview
2. Check the [API Reference](api-reference.md) for method details
3. Keep the [Cheat Sheet](cheat-sheet.md) handy while coding
4. Review the [Migration Guide](migration.md) when converting existing code
