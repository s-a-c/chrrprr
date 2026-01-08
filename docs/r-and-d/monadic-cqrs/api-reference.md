# API Reference: Monadic CQRS

Complete API reference for the Monadic CQRS architecture.

## Result Class

### Static Methods

#### `Result::success(mixed $value = true, array $logs = []): self`

Create a successful Result.

**Parameters:**
- `$value` (mixed): The success value
- `$logs` (array<string>): Initial audit log entries

**Returns:** `Result` instance

**Example:**
```php
$result = Result::success($user, ['User created successfully']);
```

#### `Result::failure(string $error, array $logs = []): self`

Create a failed Result.

**Parameters:**
- `$error` (string): The error message
- `$logs` (array<string>): Initial audit log entries

**Returns:** `Result` instance

**Example:**
```php
$result = Result::failure('User not found', ['Lookup failed']);
```

#### `Result::try(callable $operation, array $context = []): self`

Lifts an impure execution into the Result Monad. Catches exceptions and converts them to `Result::failure`.

**Parameters:**
- `$operation` (callable): The operation to execute
- `$context` (array<string>): Context for the Writer monad

**Returns:** `Result` instance

**Example:**
```php
$result = Result::try(
    fn() => $api->call(),
    ['API call context']
);
```

### Instance Methods

#### `flatMap(Closure $callback): self`

Bind operation: chains operations that return Results. If current Result is failure, returns itself without executing callback.

**Parameters:**
- `$callback` (Closure(mixed): self): Function that returns a Result

**Returns:** `Result` instance

**Example:**
```php
$result = $this->findUser($id)
    ->flatMap(fn($user) => $this->activate($user));
```

#### `map(Closure $callback): self`

Transform the value if successful. Unlike `flatMap`, does not expect callback to return a Result.

**Parameters:**
- `$callback` (Closure(mixed): mixed): Function that transforms the value

**Returns:** `Result` instance

**Example:**
```php
$result = Result::success($user)
    ->map(fn($u) => $u->fullName);
```

#### `match(Closure $onSuccess, Closure $onFailure): mixed`

Pattern matching: executes the appropriate callback based on success/failure state.

**Parameters:**
- `$onSuccess` (Closure(mixed, array<string>): mixed): Callback for success
- `$onFailure` (Closure(string, array<string>): mixed): Callback for failure

**Returns:** mixed (return value of the executed callback)

**Example:**
```php
$result->match(
    onSuccess: fn($value, $logs) => response()->json($value),
    onFailure: fn($error, $logs) => response()->json(['error' => $error], 422)
);
```

#### `getOrElse(mixed $default = null): mixed`

Get the value, or return a default if failure.

**Parameters:**
- `$default` (mixed): Default value to return on failure

**Returns:** mixed

**Example:**
```php
$user = $result->getOrElse(new GuestUser());
```

#### `getError(): ?string`

Get the error message, or null if success.

**Returns:** `string|null`

**Example:**
```php
$error = $result->getError();
```

#### `getLogs(): array`

Get the audit logs.

**Returns:** `array<string>`

**Example:**
```php
$logs = $result->getLogs();
```

#### `logInternal(): self`

Log the Writer monad's audit trail to Laravel logs. Also integrates with Telescope if available.

**Returns:** `self` (for method chaining)

**Example:**
```php
$result->logInternal();
```

### Properties

#### `bool $isSuccess` (readonly)

Computed property: `true` if success, `false` if failure.

#### `bool $isFailure` (readonly)

Computed property: `true` if failure, `false` if success.

#### `mixed $value` (readonly)

The success value (null if failure).

#### `?string $error` (readonly)

The error message (null if success).

#### `array $logs` (readonly)

The audit log entries.

### Collection Proxy

The Result class proxies Laravel Collection methods via `__call`. Any Collection method can be called directly on a Result.

**Example:**
```php
$result = Result::success([1, 2, 3, 4, 5])
    ->filter(fn($n) => $n > 3)
    ->values();
```

## AsyncResult Class

### Static Methods

#### `AsyncResult::all(array $tasks): Result`

Executes an array of closures in parallel. Each closure MUST return a Result object. Follows "All-or-Nothing" principle.

**Parameters:**
- `$tasks` (array<string, callable(): Result>): Array of tasks to execute

**Returns:** `Result` instance

**Example:**
```php
$result = AsyncResult::all([
    'shipping' => fn() => $this->getShipping(),
    'tax' => fn() => $this->getTax(),
]);
```

#### `AsyncResult::allParallel(array $tasks): Result`

Executes tasks in parallel using Laravel's Concurrency facade (if available). Falls back to sequential execution.

**Parameters:**
- `$tasks` (array<string, callable(): Result>): Array of tasks to execute

**Returns:** `Result` instance

## BaseHandler Class

### Protected Methods

#### `ensureFound(mixed $model, string $message = 'Resource not found'): Result`

Standardizes the 'Option' monad behavior for missing resources.

**Parameters:**
- `$model` (mixed): The model instance or null
- `$message` (string): Custom error message if not found

**Returns:** `Result` instance

**Example:**
```php
return $this->ensureFound(User::find($id), 'User not found');
```

#### `guard(bool $condition, string $error): Result`

Standardizes the 'Error' monad behavior for business constraints.

**Parameters:**
- `$condition` (bool): The condition to validate
- `$error` (string): Error message if condition fails

**Returns:** `Result` instance

**Example:**
```php
return $this->guard($balance >= $amount, 'Insufficient funds');
```

#### `guardAll(array $conditions): Result`

Validates multiple conditions using guard logic. Returns the first failure encountered.

**Parameters:**
- `$conditions` (array<string, bool>): Array of condition name => condition result

**Returns:** `Result` instance

**Example:**
```php
return $this->guardAll([
    'Has balance' => $balance >= $amount,
    'Account active' => $account->isActive(),
]);
```

## CommandHandler Interface

### Methods

#### `handle(object $command): Result`

Handle a command.

**Parameters:**
- `$command` (object): The command object to handle

**Returns:** `Result` instance

## QueryHandler Interface

### Methods

#### `ask(object $query): Result`

Ask a query.

**Parameters:**
- `$query` (object): The query object to execute

**Returns:** `Result` instance

## HandlesResults Trait

### Protected Methods

#### `handleResult(Result $result, ?string $successMessage = null, ?string $errorMessage = null): mixed`

Handle a Result object, displaying notifications and managing state.

**Parameters:**
- `$result` (Result): The Result to handle
- `$successMessage` (string|null): Custom success message
- `$errorMessage` (string|null): Custom error message prefix

**Returns:** mixed (the value if successful, null if failed)

#### `executeCommand(CommandHandler $handler, object $command, ?string $successMessage = null): mixed`

Execute a command handler and handle the Result.

**Parameters:**
- `$handler` (CommandHandler): The command handler
- `$command` (object): The command object
- `$successMessage` (string|null): Custom success message

**Returns:** mixed

#### `executeQuery(QueryHandler $handler, object $query): mixed`

Execute a query handler and handle the Result.

**Parameters:**
- `$handler` (QueryHandler): The query handler
- `$query` (object): The query object

**Returns:** mixed

#### `unwrapResult(Result $result): mixed`

Get the value from a Result, or return null if failure.

**Parameters:**
- `$result` (Result): The Result to unwrap

**Returns:** mixed

#### `isResultSuccess(Result $result): bool`

Check if a Result is successful.

**Parameters:**
- `$result` (Result): The Result to check

**Returns:** bool

#### `getResultError(Result $result): ?string`

Get error message from a Result, or null if successful.

**Parameters:**
- `$result` (Result): The Result to check

**Returns:** string|null

## ResultAction Class (Filament)

### Protected Methods

#### `executeCommand(CommandHandler $handler, object $command): void`

Execute a command handler and handle the Result.

**Parameters:**
- `$handler` (CommandHandler): The command handler
- `$command` (object): The command object

#### `executeQuery(QueryHandler $handler, object $query): void`

Execute a query handler and handle the Result.

**Parameters:**
- `$handler` (QueryHandler): The query handler
- `$query` (object): The query object

#### `handleResult(Result $result): void`

Handle a Result object, converting it to Filament notifications.

**Parameters:**
- `$result` (Result): The Result to handle

#### `unwrapResult(Result $result): mixed`

Get the value from a Result, or throw if failure.

**Parameters:**
- `$result` (Result): The Result to unwrap

**Returns:** mixed

**Throws:** `RuntimeException` if the Result is a failure
