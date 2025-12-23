# Enums, States & Statuses

This guide covers all enum classes, state machines, and status tracking implementations.

## Overview

All enums use native PHP 8.1+ backed enums and are enhanced with Filament colors and icons for UI display.

## UserState Enum

### Definition

```php
<?php

namespace App\Enums;

enum UserState: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';

    /**
     * Get the color for Filament badges.
     */
    public function getColor(): string
    {
        return match($this) {
            self::Draft => 'gray',
            self::Pending => 'yellow',
            self::Active => 'green',
            self::Suspended => 'orange',
            self::Archived => 'red',
        };
    }

    /**
     * Get the human-readable label.
     */
    public function getLabel(): string
    {
        return match($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Archived => 'Archived',
        };
    }

    /**
     * Get the icon name.
     */
    public function getIcon(): string
    {
        return match($this) {
            self::Draft => 'heroicon-o-pencil',
            self::Pending => 'heroicon-o-clock',
            self::Active => 'heroicon-o-check-circle',
            self::Suspended => 'heroicon-o-pause-circle',
            self::Archived => 'heroicon-o-archive-box',
        };
    }
}
```

## UserStatus Enum

### Definition

```php
<?php

namespace App\Enums;

enum UserStatus: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Away = 'away';
    case Busy = 'busy';

    public function getColor(): string
    {
        return match($this) {
            self::Online => 'green',
            self::Offline => 'gray',
            self::Away => 'yellow',
            self::Busy => 'red',
        };
    }

    public function getLabel(): string
    {
        return match($this) {
            self::Online => 'Online',
            self::Offline => 'Offline',
            self::Away => 'Away',
            self::Busy => 'Busy',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::Online => 'heroicon-o-signal',
            self::Offline => 'heroicon-o-signal-slash',
            self::Away => 'heroicon-o-moon',
            self::Busy => 'heroicon-o-x-circle',
        };
    }
}
```

## TeamType Enum

### Definition

```php
<?php

namespace App\Enums;

enum TeamType: string
{
    case Enterprise = 'enterprise';
    case Organisation = 'organisation';
    case Division = 'division';
    case Department = 'department';
    case Project = 'project';

    public function getColor(): string
    {
        return match($this) {
            self::Enterprise => 'purple',
            self::Organisation => 'blue',
            self::Division => 'indigo',
            self::Department => 'cyan',
            self::Project => 'green',
        };
    }

    public function getLabel(): string
    {
        return match($this) {
            self::Enterprise => 'Enterprise',
            self::Organisation => 'Organisation',
            self::Division => 'Division',
            self::Department => 'Department',
            self::Project => 'Project',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::Enterprise => 'heroicon-o-building-office',
            self::Organisation => 'heroicon-o-building-office-2',
            self::Division => 'heroicon-o-squares-2x2',
            self::Department => 'heroicon-o-folder',
            self::Project => 'heroicon-o-briefcase',
        };
    }

    /**
     * Get allowed parent types for this team type.
     *
     * @return array<self>
     */
    public function getAllowedParents(): array
    {
        return match($this) {
            self::Enterprise => [], // No parent (root)
            self::Organisation => [self::Enterprise],
            self::Division => [self::Organisation],
            self::Department => [self::Division],
            self::Project => [self::Enterprise, self::Organisation, self::Division, self::Department],
        };
    }

    /**
     * Check if this type can have the given parent type.
     */
    public function canHaveParent(self $parentType): bool
    {
        return in_array($parentType, $this->getAllowedParents(), true);
    }
}
```

## TeamState Enum

### Definition

```php
<?php

namespace App\Enums;

enum TeamState: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    public function getColor(): string
    {
        return match($this) {
            self::Draft => 'gray',
            self::Active => 'green',
            self::Inactive => 'yellow',
            self::Archived => 'red',
        };
    }

    public function getLabel(): string
    {
        return match($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Archived => 'Archived',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::Draft => 'heroicon-o-pencil',
            self::Active => 'heroicon-o-check-circle',
            self::Inactive => 'heroicon-o-pause-circle',
            self::Archived => 'heroicon-o-archive-box',
        };
    }
}
```

## TeamStatus Enum

### Definition

```php
<?php

namespace App\Enums;

enum TeamStatus: string
{
    case Operational = 'operational';
    case UnderReview = 'under_review';
    case Merging = 'merging';
    case Splitting = 'splitting';

    public function getColor(): string
    {
        return match($this) {
            self::Operational => 'green',
            self::UnderReview => 'orange',
            self::Merging => 'blue',
            self::Splitting => 'purple',
        };
    }

    public function getLabel(): string
    {
        return match($this) {
            self::Operational => 'Operational',
            self::UnderReview => 'Under Review',
            self::Merging => 'Merging',
            self::Splitting => 'Splitting',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::Operational => 'heroicon-o-check-circle',
            self::UnderReview => 'heroicon-o-eye',
            self::Merging => 'heroicon-o-arrow-path',
            self::Splitting => 'heroicon-o-scissors',
        };
    }
}
```

## State Machine Integration

### User Model State Machine

```php
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class UserState extends State
{
    abstract public function color(): string;
    abstract public function label(): string;
}

// In User model
protected function registerStates(): void
{
    $this
        ->addState('state', UserState::class)
        ->default(DraftUserState::class)
        ->allowTransition(DraftUserState::class, PendingUserState::class)
        ->allowTransition(PendingUserState::class, ActiveUserState::class)
        ->allowTransition(ActiveUserState::class, SuspendedUserState::class)
        ->allowTransition(SuspendedUserState::class, ActiveUserState::class)
        ->allowTransition([ActiveUserState::class, SuspendedUserState::class], ArchivedUserState::class);
}
```

## Status Tracking Integration

### User Model Status

```php
use Spatie\ModelStatus\HasStatuses;

class User extends Authenticatable
{
    use HasStatuses;

    public function getStatusOptions(): array
    {
        return [
            UserStatus::Online->value,
            UserStatus::Offline->value,
            UserStatus::Away->value,
            UserStatus::Busy->value,
        ];
    }
}
```

## Usage Examples

### Setting State

```php
$user->state = UserState::Active;
$user->save();

// Or using state machine
$user->state->transitionTo(ActiveUserState::class);
```

### Setting Status

```php
$user->setStatus(UserStatus::Online->value);
```

### Filament Display

```php
use Filament\Infolists\Components\TextEntry;

TextEntry::make('state')
    ->badge()
    ->color(fn (UserState $state): string => $state->getColor())
    ->label(fn (UserState $state): string => $state->getLabel())
    ->icon(fn (UserState $state): string => $state->getIcon())
```

## Database Storage

Enums are stored as strings in the database:

- `state` column: string (e.g., 'active', 'draft')
- `status` column: string (e.g., 'online', 'offline')

For PostgreSQL, you can use native enum types:

```php
// In migration
DB::statement("CREATE TYPE user_state AS ENUM ('draft', 'pending', 'active', 'suspended', 'archived')");
$table->enum('state', ['draft', 'pending', 'active', 'suspended', 'archived'])
    ->default('draft');
```

## Next Steps

- Review [Models Implementation](070-models-implementation.md) to see enums in use
- Check [Database Setup](040-database-setup.md) for enum column definitions
- See [Filament Integration](090-filament-integration.md) for UI display
