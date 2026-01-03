---
name: Replace Exceptions with Monads
overview: Review the codebase to identify all opportunities where exceptions can be replaced with the existing Result monad pattern, following the established monadic CQRS architecture.
todos: []
---

# Replace Exceptions with Monads - Codebase Review Plan

## Current State Analysis

The codebase already has a well-established monadic CQRS architecture with:

- `Result` monad class (`app/Support/Result.php`) combining Error, Option, and Writer patterns
- `BaseHandler` with `guard()` and `ensureFound()` helpers
- Handlers that return `Result` (e.g., `CreateTeamHandler`, `UpdateTeamHandler`, `MoveTeamHandler`)
- Integration patterns for Filament and Livewire

However, there are still many places using exceptions that could benefit from monadic error handling.

## Opportunities Identified

### 1. Actions Layer (High Priority)

**Files to migrate:**

- `app/Actions/Teams/CreateTeam.php` - Throws `InvalidArgumentException`, uses `throw_if`
- `app/Actions/Teams/UpdateTeam.php` - Throws `OptimisticLockingException`, `RuntimeException`, uses `throw_unless`
- `app/Actions/Teams/MoveTeam.php` - Throws `ValidationException` for cycle detection
- `app/Actions/Fortify/CreateNewUser.php` - Converts `Result` to `RuntimeException` (anti-pattern)
- `app/Actions/Fortify/ResetUserPassword.php` - Likely similar pattern
- `app/Actions/Users/RegisterUser.php` - Check for exception usage
- `app/Actions/Users/UpdateUserProfile.php` - Check for exception usage
- `app/Actions/Teams/AssignExecutive.php` - Check for exception usage

**Pattern to replace:**

```php
// Current (exception-based)
throw_if($condition, ExceptionClass::class, 'message');
throw_unless($condition, ExceptionClass::class, 'message');

// Target (monadic)
return $this->guard($condition, 'message')
    ->flatMap(fn() => /* continue */);
```

### 2. Services Layer (High Priority)

**Files to migrate:**

- `app/Services/TeamMove/TeamMoveRequestService.php` - Throws `RuntimeException` when handler fails (line 50)
- Other services in `app/Services/` that don't return `Result`

**Pattern to replace:**

```php
// Current
if ($moveResult->isFailure) {
    throw new RuntimeException($moveResult->error);
}

// Target
return $moveResult; // Already a Result, just return it
```

### 3. Validation Layer (Medium Priority)

**Files to migrate:**

- `app/Support/Validation/TeamHierarchyValidator.php` - Throws `ValidationException` (static methods)
- `app/Support/Validation/TeamNameValidator.php` - Throws `ValidationException` in `validateUnique()`

**Pattern to replace:**

```php
// Current
public static function validate(TeamType $childType, ?Team $parent): void
{
    if ($invalid) {
        throw ValidationException::withMessages([...]);
    }
}

// Target
public static function validate(TeamType $childType, ?Team $parent): Result
{
    if ($invalid) {
        return Result::failure('Error message', ['Validation context']);
    }
    return Result::success(true);
}
```

**Note:** Handlers already use `Result::try()` to catch these exceptions, but direct `Result` returns would be cleaner.

### 4. Model Concerns (Medium Priority)

**Files to migrate:**

- `app/Models/Concerns/ProtectsKeyRoles.php` - Throws `CannotDeleteKeyUserException` in observer

**Challenge:** Model observers are event-driven and don't have a return value. Options:

1. Use a service/action that returns `Result` and call it before deletion
2. Create a validation service that returns `Result`
3. Keep exception for observer pattern (document as intentional)

### 5. Handlers - Internal Exception Throwing (Medium Priority)

**Files to review:**

- `app/Handlers/Commands/Teams/UpdateTeamHandler.php` - Throws `RuntimeException` when move fails (line 96), throws `OptimisticLockingException` (line 77), uses `throw_unless` (line 63)
- `app/Handlers/Commands/Teams/MoveTeamHandler.php` - Throws `ValidationException` (line 64), uses `throw_unless` (line 77)

**Pattern to replace:**

```php
// Current (inside Result::try())
$moveResult = $this->moveTeamHandler->handle($moveCommand);
if ($moveResult->isFailure) {
    throw new RuntimeException($moveResult->error);
}

// Target
return $this->moveTeamHandler->handle($moveCommand)
    ->flatMap(fn($team) => /* continue with team */);
```

**Note:** Since these are already inside `Result::try()`, exceptions are caught, but using `flatMap` would be more idiomatic.

### 6. Controllers (Low Priority - Integration Layer)

**Files to review:**

- `app/Http/Controllers/Teams/BulkTeamController.php` - Catches exceptions and converts to arrays (line 89), throws `RuntimeException` when Result fails (lines 116, 135)

**Pattern to replace:**

```php
// Current
try {
    return $this->processTeam($teamData, $index);
} catch (Exception $e) {
    return ['success' => false, 'error' => $e->getMessage()];
}

// Target
$result = $handler->handle($command);
return $result->match(
    onSuccess: fn($team) => ['success' => true, 'team' => $team],
    onFailure: fn($error) => ['success' => false, 'error' => $error]
);
```

### 7. Fortify Actions Bridge (High Priority)

**Files to migrate:**

- `app/Actions/Fortify/CreateNewUser.php` - Converts `Result` to exception (anti-pattern)

**Current anti-pattern:**

```php
$result = $handler->handle($command)->logInternal();
return $result->match(
    onSuccess: static fn (User $user): User => $user,
    onFailure: static fn (string $error) => throw new RuntimeException($error)
);
```

**Note:** Fortify interface requires returning `User`, not `Result`. Options:

1. Keep exception conversion but document it as a boundary pattern
2. Modify Fortify integration to handle `Result` (if possible)
3. Create a wrapper that handles the conversion more gracefully

## Migration Strategy

### Phase 1: Low-Hanging Fruit (Services)

1. **TeamMoveRequestService** - Return `Result` directly instead of throwing when handler fails
2. **Other services** - Audit and convert to `Result` returns

### Phase 2: Validation Layer

1. **TeamHierarchyValidator** - Convert to return `Result` instead of throwing
2. **TeamNameValidator** - Convert `validateUnique()` to return `Result`
3. Update handlers to use `flatMap` instead of `Result::try()` for validation

### Phase 3: Handlers Internal Cleanup

1. **UpdateTeamHandler** - Replace internal exception throwing with `flatMap` chains
2. **MoveTeamHandler** - Replace internal exception throwing with `flatMap` chains
3. Use `guard()` and `ensureFound()` helpers more consistently

### Phase 4: Actions Layer

1. **CreateTeam** - Migrate to handler pattern (already exists: `CreateTeamHandler`)
2. **UpdateTeam** - Already has handler, deprecate Action
3. **MoveTeam** - Already has handler, deprecate Action
4. **Fortify Actions** - Document boundary pattern or create Result-aware wrappers

### Phase 5: Controllers & Integration

1. **BulkTeamController** - Use `Result::match()` instead of try/catch
2. Other controllers - Audit for exception handling

## Benefits

1. **Consistency** - All business logic uses the same error handling pattern
2. **Composability** - `flatMap` chains enable railway-oriented programming
3. **Audit Trails** - Writer monad captures execution paths automatically
4. **Type Safety** - Explicit success/failure states in type system
5. **Testability** - Easier to test success and failure paths

## Considerations

1. **Laravel Framework Boundaries** - Some Laravel interfaces (like Fortify) require exceptions
2. **Model Observers** - Event-driven pattern may need exceptions for cancellation
3. **ValidationException** - Laravel's form validation expects this exception type
4. **Backward Compatibility** - Deprecate Actions gradually, keep handlers

## Files Requiring Detailed Review

- All files in `app/Actions/` directory (12 files)
- All files in `app/Services/` directory (23 files)
- Validation classes in `app/Support/Validation/`
- Controllers that catch exceptions
- Handlers that throw exceptions internally

## Testing Strategy

1. Ensure all existing tests pass after migration
2. Add tests for `Result` failure paths
3. Verify audit logs are captured correctly
4. Test Filament and Livewire integrations still work
