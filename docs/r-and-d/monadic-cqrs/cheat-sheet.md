# Monadic CQRS Cheat Sheet

Quick reference for the Monadic CQRS architecture patterns.

## The Result Anatomy

Every operation returns a `Result`. It holds three distinct states:

- **The Value (Option):** The data you want (or `null` if "None")
- **The Error (Result):** A string explaining why the railway derailed
- **The Logs (Writer):** An array of strings representing the audit trail

## Common Patterns & Syntax

### The "Railway" Chain

Sequential business logic. If `step1` fails, `step2` and `step3` are never executed.

```php
return $this->step1()                               // Returns Result
    ->flatMap(fn($data) => $this->step2($data))     // Returns Result
    ->flatMap(fn($data) => $this->step3($data));    // Returns Result
```

### The "Collection" Shortcut

You don't need to unwrap the result to use Laravel's Collection power.

```php
return Result::success($userArray)
    ->where('is_active', true)  // Direct Collection call
    ->sortBy('created_at')      // Direct Collection call
    ->pluck('email');           // Result wraps the final Collection
```

### The Async Power-Up

Use `AsyncResult` when tasks don't depend on each other. It merges the **Writer** logs from all threads automatically.

```php
$result = AsyncResult::all([
    'fedex' => fn() => $this->getFedexRate(),
    'ups'   => fn() => $this->getUpsRate(),
]);
```

## Quick-Reference Table

| Task | Method | Example |
| --- | --- | --- |
| **Start a Railway** | `Result::success($val)` | `return Result::success($order);` |
| **Handle "Not Found"** | `Result::failure($msg)` | `return Result::failure("User #1 not found");` |
| **Transform Data** | `map()` | `->map(fn($user) => $user->fullName)` |
| **Chain Logics** | `flatMap()` | `->flatMap(fn($u) => $this->validate($u))` |
| **Catch Crashes** | `Result::try()` | `Result::try(fn() => $api->call())` |
| **Final Output** | `match()` | `->match(success: ..., failure: ...)` |
| **Get Value or Default** | `getOrElse($default)` | `$result->getOrElse(null)` |
| **Check Success** | `$result->isSuccess` | `if ($result->isSuccess) { ... }` |
| **Check Failure** | `$result->isFailure` | `if ($result->isFailure) { ... }` |
| **Get Error** | `getError()` | `$result->getError()` |
| **Get Logs** | `getLogs()` | `$result->getLogs()` |
| **Log Internally** | `logInternal()` | `->logInternal()` |

## Mago & Quality Rules

- **No `null` returns.** Always `Result::failure()` if data is missing.
- **No `try/catch` in Handlers.** Wrap the entry point in `Result::try()` instead.
- **No `Log::info()`.** Add to the `$logs` array within your `Result::success()` or `Result::failure()` calls.

## How to Read a Result

When debugging, check the `logs` property before the `value`. The "how" is just as important as the "what."

```json
{
  "isSuccess": false,
  "error": "Insufficient Funds",
  "logs": [
    "Found Account #123",
    "Applied collection method: filter",
    "Guard failed: Balance (50) is less than Price (100)"
  ]
}
```

## BaseHandler Helpers

| Method | Purpose | Example |
| --- | --- | --- |
| `ensureFound($model, $message)` | Option monad: Convert null to Failure | `$this->ensureFound(User::find($id))` |
| `guard($condition, $error)` | Error monad: Validate business rule | `$this->guard($balance >= $amount, "Insufficient funds")` |
| `guardAll($conditions)` | Validate multiple conditions | `$this->guardAll(['Rule 1' => true, 'Rule 2' => false])` |

## Integration Patterns

### Filament Resource Page

```php
protected function handleRecordCreation(array $data): Model
{
    $handler = resolve(CreateTeamHandler::class);
    $result = $handler->handle(new CreateTeamCommand($data));
    
    return $result->match(
        onSuccess: fn($team) => $team,
        onFailure: fn($error) => throw new \RuntimeException($error)
    );
}
```

### Livewire Component

```php
use App\Livewire\Concerns\HandlesResults;

class MyComponent extends Component
{
    use HandlesResults;
    
    public function submit(): void
    {
        $this->executeCommand($handler, $command, 'Success message');
    }
}
```

### Controller

```php
public function store(Request $request, CreateTeamHandler $handler)
{
    return $handler->handle(new CreateTeamCommand($request->all()))
        ->logInternal()
        ->match(
            onSuccess: fn($team) => response()->json($team, 201),
            onFailure: fn($error) => response()->json(['error' => $error], 422)
        );
}
```

### Blade View

```blade
@success($result)
    <h1>{{ $result->value->name }}</h1>
@endsuccess

@failure($result)
    <div class="alert alert-danger">{{ $result->error }}</div>
@endfailure

@audit($result)
```

## Common Mistakes to Avoid

1. **Don't throw exceptions in handlers** - Use `Result::failure()` instead
2. **Don't return null** - Use `Result::failure("Not found")` instead
3. **Don't use `Log::info()` in handlers** - Add to `$logs` array in Result
4. **Don't forget `logInternal()`** - Call it before `match()` to flush logs
5. **Don't unwrap too early** - Stay in the Result monad as long as possible
