# Migration Guide: Actions to CQRS Handlers

This guide shows how to migrate existing Actions to CQRS handlers while maintaining backward compatibility.

## Migration Strategy

### Coexistence Approach

Actions and CQRS handlers can coexist during migration:

1. **New Features**: Use CQRS handlers from the start
2. **Existing Code**: Migrate incrementally
3. **Backward Compatibility**: Existing Actions continue to work
4. **Gradual Adoption**: No big-bang migration required

## Example 1: Migrating CreateTeam Action

### Before (Action)

```php
// app/Actions/Teams/CreateTeam.php
final class CreateTeam
{
    public function handle(array $data): Team
    {
        return DB::transaction(static function () use ($data): Team {
            // ... validation and creation logic
            return $team;
        });
    }
}
```

### After (CQRS Handler)

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

        return Result::try(
            fn() => DB::transaction(fn() => $this->createTeam($command)),
            ['Starting team creation']
        );
    }

    private function createTeam(CreateTeamCommand $command): Team
    {
        // ... validation and creation logic
        // Use $this->guard() and $this->ensureFound() for validation
        return $team;
    }
}
```

### Usage in Filament

**Before:**
```php
protected function handleRecordCreation(array $data): Model
{
    return resolve(CreateTeam::class)->handle($data);
}
```

**After:**
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

## Example 2: Migrating RegisterUser Action

### Before (Action)

```php
final class RegisterUser
{
    public function handle(array $data): User
    {
        return DB::transaction(static function () use ($data): ?User {
            $user = User::query()->create([...]);
            // ... additional logic
            return $user->fresh();
        });
    }
}
```

### After (CQRS Handler)

```php
// app/Handlers/Commands/Users/RegisterUserCommand.php
readonly class RegisterUserCommand
{
    public function __construct(
        public array $data
    ) {}
}

// app/Handlers/Commands/Users/RegisterUserHandler.php
final class RegisterUserHandler extends BaseHandler implements CommandHandler
{
    public function handle(object $command): Result
    {
        if (!$command instanceof RegisterUserCommand) {
            return Result::failure('Invalid command type');
        }

        return Result::try(
            fn() => DB::transaction(fn() => $this->registerUser($command)),
            ['Starting user registration']
        )->flatMap(fn($user) => $this->setBioIfProvided($user, $command));
    }

    private function registerUser(RegisterUserCommand $command): User
    {
        return User::query()->create([
            'name' => $command->data['name'],
            'email' => $command->data['email'],
            'password' => Hash::make($command->data['password']),
            'state' => $command->data['state'] ?? UserState::PENDING,
            'tenant_id' => $command->data['tenant_id'] ?? null,
        ]);
    }

    private function setBioIfProvided(User $user, RegisterUserCommand $command): Result
    {
        if (!isset($command->data['bio'])) {
            return Result::success($user->fresh());
        }

        $locale = app()->getLocale();
        $user->setTranslation('bio', $locale, $command->data['bio']);
        $user->save();

        return Result::success($user->fresh(), ['Bio set']);
    }
}
```

## Example 3: Creating a Query Handler

### New Query Handler

```php
// app/Handlers/Queries/Users/GetUserQuery.php
readonly class GetUserQuery
{
    public function __construct(
        public int|string $id  // ID or ULID
    ) {}
}

// app/Handlers/Queries/Users/GetUserHandler.php
final class GetUserHandler extends BaseHandler implements QueryHandler
{
    public function ask(object $query): Result
    {
        if (!$query instanceof GetUserQuery) {
            return Result::failure('Invalid query type');
        }

        $user = is_string($query->id)
            ? User::query()->where('ulid', $query->id)->first()
            : User::query()->find($query->id);

        return $this->ensureFound($user, "User not found: {$query->id}");
    }
}
```

### Usage in Controller

```php
public function show(string $id, GetUserHandler $handler)
{
    return $handler->ask(new GetUserQuery($id))
        ->logInternal()
        ->match(
            onSuccess: fn($user) => view('users.show', compact('user')),
            onFailure: fn($error) => abort(404, $error)
        );
}
```

## Example 4: Migrating Livewire Component

### Before (MoveTeam Component)

```php
public function submit(): void
{
    try {
        $team = Team::query()->where('ulid', $this->teamUlid)->firstOrFail();
        $service = resolve(TeamMoveRequestService::class);
        
        $this->result = $service->requestMove(...);
        session()->flash('status', 'Team moved successfully.');
        $this->redirect(route('teams.index'));
    } catch (Exception $e) {
        $this->errorMessage = 'An error occurred: '.$e->getMessage();
    }
}
```

### After (Using Result Monads)

```php
use App\Livewire\Concerns\HandlesResults;

class MoveTeam extends Component
{
    use HandlesResults;
    
    public function submit(): void
    {
        $handler = resolve(MoveTeamHandler::class);
        $command = new MoveTeamCommand(
            teamUlid: $this->teamUlid,
            parentId: $this->parent_id,
            reason: $this->reason,
            user: auth()->user()
        );
        
        $result = $this->executeCommand(
            $handler,
            $command,
            'Team move request submitted successfully.'
        );
        
        if ($result !== null) {
            $this->redirect(route('teams.index'));
        }
    }
}
```

## Migration Checklist

### For Each Action to Migrate:

- [ ] Create Command/Query DTO class
- [ ] Create Handler class extending BaseHandler
- [ ] Implement CommandHandler or QueryHandler interface
- [ ] Convert validation to use `guard()` and `ensureFound()`
- [ ] Wrap operations in `Result::try()` for exception safety
- [ ] Use `flatMap()` for chaining operations
- [ ] Add audit logs to Result creation
- [ ] Update callers to use `match()` for Result handling
- [ ] Write tests for the new handler
- [ ] Update documentation

### For Filament Resources:

- [ ] Update `handleRecordCreation()` to use handler
- [ ] Update `handleRecordUpdate()` to use handler
- [ ] Handle Result in page classes
- [ ] Test form submission with Result handling

### For Livewire Components:

- [ ] Add `HandlesResults` trait
- [ ] Replace try/catch with `executeCommand()` or `executeQuery()`
- [ ] Update error handling to use Result
- [ ] Test component with Result handling

## Coexistence Pattern

During migration, you can wrap existing Actions in Result:

```php
final class CreateTeamHandler extends BaseHandler implements CommandHandler
{
    public function handle(object $command): Result
    {
        return Result::try(
            fn() => resolve(CreateTeam::class)->handle($command->data),
            ['Using legacy CreateTeam action']
        );
    }
}
```

This allows gradual migration: start with wrapping, then refactor the Action logic into the handler.

## Common Patterns

### Pattern: Find or Create

```php
public function handle(CreateOrGetUserCommand $command): Result
{
    return $this->ensureFound(
        User::where('email', $command->email)->first(),
        'User not found'
    )->flatMap(
        fn($user) => Result::success($user, ['User found'])
    )->getOrElse(
        fn() => $this->createUser($command)
    );
}
```

### Pattern: Validate Then Process

```php
public function handle(ProcessOrderCommand $command): Result
{
    return $this->guard($command->amount > 0, 'Amount must be positive')
        ->flatMap(fn() => $this->guard($command->items->isNotEmpty(), 'Order must have items'))
        ->flatMap(fn() => $this->checkStock($command))
        ->flatMap(fn() => $this->createOrder($command));
}
```

### Pattern: Parallel Operations

```php
public function handle(CheckoutCommand $command): Result
{
    return AsyncResult::all([
        'payment' => fn() => $this->processPayment($command),
        'inventory' => fn() => $this->reserveInventory($command),
        'shipping' => fn() => $this->calculateShipping($command),
    ])->flatMap(fn($results) => $this->createOrder($command, $results));
}
```

## Testing Migrated Handlers

### Example Test

```php
test('create team handler returns success with valid data', function (): void {
    $handler = new CreateTeamHandler();
    $command = new CreateTeamCommand([
        'name' => 'Test Team',
        'type' => TeamType::ORGANISATION,
    ]);
    
    $result = $handler->handle($command);
    
    expect($result->isSuccess)->toBeTrue()
        ->and($result->value)->toBeInstanceOf(Team::class)
        ->and($result->value->name)->toBe('Test Team');
});

test('create team handler returns failure with invalid data', function (): void {
    $handler = new CreateTeamHandler();
    $command = new CreateTeamCommand([]);
    
    $result = $handler->handle($command);
    
    expect($result->isFailure)->toBeTrue()
        ->and($result->error)->not->toBeEmpty();
});
```

## Benefits of Migration

1. **Predictable Error Handling**: No unexpected exceptions
2. **Automatic Audit Trails**: Writer monad captures execution path
3. **Type Safety**: Explicit return types enforce correctness
4. **Testability**: Easier to test with Result objects
5. **Composability**: Chain operations with `flatMap()`
6. **Observability**: Logs travel with the Result

## Next Steps

1. Start with new features using CQRS
2. Migrate high-value Actions first
3. Gradually migrate remaining Actions
4. Remove legacy Actions once fully migrated
