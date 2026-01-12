# Livewire 4 SFC Integration Examples

Examples of integrating Result monads with Livewire 4 Single-File Components.

## Basic Component Integration

### Using HandlesResults Trait

```php
<?php

namespace App\Livewire\Teams;

use App\Handlers\Commands\Teams\MoveTeamCommand;
use App\Handlers\Commands\Teams\MoveTeamHandler;
use App\Livewire\Concerns\HandlesResults;
use Livewire\Component;

final class MoveTeam extends Component
{
    use HandlesResults;
    
    public string $teamUlid = '';
    public ?string $parent_id = null;
    public string $reason = '';

    public function mount(string $ulid): void
    {
        $team = Team::query()->where('ulid', $ulid)->firstOrFail();
        $this->teamUlid = $ulid;
        $this->parent_id = $team->parent_id ? (string) $team->parent_id : null;
    }

    public function submit(): void
    {
        $this->validate([
            'parent_id' => 'nullable|exists:teams,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $handler = resolve(MoveTeamHandler::class);
        $command = new MoveTeamCommand(
            teamUlid: $this->teamUlid,
            parentId: $this->parent_id ? (int) $this->parent_id : null,
            reason: $this->reason ?: null,
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

    public function render()
    {
        return view('livewire.teams.move-team');
    }
}
```

## Query Handler Integration

### Loading Data with Queries

```php
<?php

namespace App\Livewire\Users;

use App\Handlers\Queries\Users\GetUserQuery;
use App\Handlers\Queries\Users\GetUserHandler;
use App\Livewire\Concerns\HandlesResults;
use Livewire\Component;

final class ShowUser extends Component
{
    use HandlesResults;
    
    public ?User $user = null;
    public string $error = '';

    public function mount(string $ulid): void
    {
        $handler = resolve(GetUserHandler::class);
        $query = new GetUserQuery($ulid);
        
        $result = $this->executeQuery($handler, $query);
        
        if ($result !== null) {
            $this->user = $result;
        } else {
            $this->error = $this->getResultError(
                $handler->ask($query)
            ) ?? 'User not found';
        }
    }

    public function render()
    {
        return view('livewire.users.show');
    }
}
```

## Real-time Validation

### Using Result for Validation Feedback

```php
public function updatedParentId(): void
{
    if (empty($this->parent_id)) {
        return;
    }

    $handler = resolve(ValidateTeamMoveHandler::class);
    $command = new ValidateTeamMoveCommand(
        teamUlid: $this->teamUlid,
        parentId: (int) $this->parent_id
    );

    $result = $handler->handle($command);
    
    if ($result->isFailure) {
        $this->addError('parent_id', $result->error);
    } else {
        $this->resetErrorBag('parent_id');
    }
}
```

## Loading States

### Wire Loading with Result

```blade
<div>
    <button wire:click="submit" wire:loading.attr="disabled">
        <span wire:loading.remove>Submit</span>
        <span wire:loading>Processing...</span>
    </button>
    
    @if($error)
        <div class="text-red-500">{{ $error }}</div>
    @endif
</div>
```

## Flux UI Notifications

The `HandlesResults` trait automatically dispatches Flux UI notifications:

```php
// Success notification is automatically shown
$this->executeCommand($handler, $command, 'Operation successful');

// Error notification is automatically shown on failure
```

### Custom Notification Handling

```php
public function submit(): void
{
    $handler = resolve(MyHandler::class);
    $result = $handler->handle(new MyCommand());
    
    $result->match(
        onSuccess: function ($value, $logs) {
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Custom success message',
            ]);
        },
        onFailure: function ($error, $logs) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Custom error: {$error}",
            ]);
        }
    );
}
```

## Folio Page Integration

### Folio Page Using Result

```php
<?php

use function Laravel\Folio\name;
use App\Handlers\Queries\Teams\GetTeamQuery;
use App\Handlers\Queries\Teams\GetTeamHandler;

name('teams.show');

$handler = resolve(GetTeamHandler::class);
$result = $handler->ask(new GetTeamQuery(request()->route('id')))
    ->logInternal();
?>

@success($result)
    <div>
        <h1>{{ $result->value->name }}</h1>
        <p>{{ $result->value->description }}</p>
    </div>
@endsuccess

@failure($result)
    <div class="alert alert-danger">
        {{ $result->error }}
    </div>
@endfailure

@audit($result)
```

## Best Practices

1. **Use `HandlesResults` trait** for common Result handling patterns
2. **Call `executeCommand()` or `executeQuery()`** instead of manual Result handling
3. **Let the trait handle notifications** - don't manually dispatch unless needed
4. **Use `wire:loading`** for loading states
5. **Handle Result in component methods**, not in views
6. **Use `unwrapResult()`** when you need the value but don't want to throw

## Testing Livewire Components with Result

```php
test('move team component handles success', function (): void {
    Livewire::test(MoveTeam::class, ['ulid' => $team->ulid])
        ->set('parent_id', $newParent->id)
        ->set('reason', 'Test reason')
        ->call('submit')
        ->assertRedirect(route('teams.index'))
        ->assertDispatched('notify', [
            'type' => 'success',
            'message' => 'Team move request submitted successfully.'
        ]);
});

test('move team component handles failure', function (): void {
    Livewire::test(MoveTeam::class, ['ulid' => $team->ulid])
        ->set('parent_id', 99999) // Invalid parent
        ->call('submit')
        ->assertDispatched('notify', [
            'type' => 'error'
        ]);
});
```
