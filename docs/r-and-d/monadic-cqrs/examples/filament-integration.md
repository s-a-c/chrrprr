# Filament Integration Examples

Examples of integrating Result monads with Filament v5 resources.

## Basic Resource Page Integration

### Create Page

```php
// app/Filament/Tenant/Resources/Teams/Pages/CreateTeam.php
use App\Handlers\Commands\Teams\CreateTeamCommand;
use App\Handlers\Commands\Teams\CreateTeamHandler;

final class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $handler = resolve(CreateTeamHandler::class);
        $command = new CreateTeamCommand($data);
        
        $result = $handler->handle($command)->logInternal();
        
        return $result->match(
            onSuccess: fn($team) => $team,
            onFailure: fn($error, $logs) => throw new \RuntimeException($error)
        );
    }
}
```

### Edit Page

```php
// app/Filament/Tenant/Resources/Teams/Pages/EditTeam.php
use App\Handlers\Commands\Teams\UpdateTeamCommand;
use App\Handlers\Commands\Teams\UpdateTeamHandler;

final class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected function handleRecordUpdate(Model|Team $record, array $data): Model|Team
    {
        $handler = resolve(UpdateTeamHandler::class);
        $command = new UpdateTeamCommand($record->id, $data);
        
        $result = $handler->handle($command)->logInternal();
        
        return $result->match(
            onSuccess: fn($team) => $team,
            onFailure: fn($error) => throw new \RuntimeException($error)
        );
    }
}
```

## Custom Filament Actions with Result

### Using ResultAction Base Class

```php
// app/Filament/Actions/ApproveTeamAction.php
use App\Filament\Actions\ResultAction;
use App\Handlers\Commands\Teams\ApproveTeamCommand;
use App\Handlers\Commands\Teams\ApproveTeamHandler;

class ApproveTeamAction extends ResultAction
{
    public static function make(string $name = 'approve'): static
    {
        return parent::make($name)
            ->label('Approve Team')
            ->icon('heroicon-o-check')
            ->color('success')
            ->requiresConfirmation()
            ->action(function (Team $record) {
                $handler = resolve(ApproveTeamHandler::class);
                $command = new ApproveTeamCommand($record->id);
                
                $this->executeCommand($handler, $command, 'Team approved successfully');
            });
    }
}
```

### Using in Resource

```php
// app/Filament/Tenant/Resources/Teams/TeamResource.php
protected function getHeaderActions(): array
{
    return [
        ApproveTeamAction::make(),
        // ... other actions
    ];
}
```

## Table Actions with Result

### Bulk Action Example

```php
// app/Filament/Tenant/Resources/Teams/Tables/TeamsTable.php
use App\Filament\Actions\ResultAction;
use Filament\Tables\Actions\BulkAction;

BulkAction::make('activate')
    ->label('Activate Selected')
    ->icon('heroicon-o-check-circle')
    ->action(function ($records) {
        $handler = resolve(ActivateTeamsHandler::class);
        
        foreach ($records as $record) {
            $command = new ActivateTeamCommand($record->id);
            $result = $handler->handle($command);
            
            $result->match(
                onSuccess: fn() => Notification::make()
                    ->title('Team activated')
                    ->success()
                    ->send(),
                onFailure: fn($error) => Notification::make()
                    ->title('Activation failed')
                    ->body($error)
                    ->danger()
                    ->send()
            );
        }
    })
```

## Form Validation Integration

### Form Request → Command → Result Pipeline

```php
// In Filament Page
protected function mutateFormDataBeforeSave(array $data): array
{
    // Validation happens via Form Request
    // Data is passed to Command
    return $data;
}

protected function handleRecordCreation(array $data): Model
{
    // Create command from validated data
    $command = new CreateTeamCommand($data);
    
    // Handler returns Result
    $handler = resolve(CreateTeamHandler::class);
    $result = $handler->handle($command);
    
    // Handle Result
    return $result->match(
        onSuccess: fn($team) => $team,
        onFailure: fn($error) => throw ValidationException::withMessages([
            'form' => [$error]
        ])
    );
}
```

## Error Display in Filament

### Automatic Notifications

The `ResultAction` base class automatically converts Result states to Filament notifications:

- **Success**: Green success notification
- **Failure**: Red danger notification with error message

### Custom Error Handling

```php
protected function handleRecordCreation(array $data): Model
{
    $handler = resolve(CreateTeamHandler::class);
    $result = $handler->handle(new CreateTeamCommand($data));
    
    return $result->match(
        onSuccess: fn($team) => $team,
        onFailure: function ($error, $logs) {
            Notification::make()
                ->title('Creation Failed')
                ->body($error)
                ->danger()
                ->persistent()
                ->send();
            
            throw new \RuntimeException($error);
        }
    );
}
```

## Best Practices

1. **Always call `logInternal()`** before `match()` to flush audit logs
2. **Use ResultAction** for custom actions that need Result handling
3. **Handle Result in page classes**, not in resource classes
4. **Provide user-friendly error messages** in Result failures
5. **Use Filament notifications** for user feedback
