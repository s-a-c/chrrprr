# Architecture Decision Record: CQRS Refactor for User Module

**Context:** Laravel 12, Livewire v4, Filament v5, Flux Pro
**Topic:** Transitioning the `User` model to CQRS, specifically managing lifecycle (`State`) and availability (`Status`).

---

## 1. Executive Summary & Reasoning

The `User` model often becomes the heaviest class in a Laravel application. It attracts logic for authentication, authorization (Roles/Permissions), notifications, profile management, and state transitions (e.g., banning a user, verifying email).

**The "Anti-CQRS" Friction Points:**

1. **Implicit Side Effects:** Banning a user isn't just changing a database column; it requires revoking API tokens, killing sessions, and sending notifications. Active Record setters (`setStatusAttribute`) hide this complexity.
2. **State Management:** Complex lifecycles (e.g., `Applicant` -> `Verified` -> `Employee`) managed by string columns lead to "spaghetti code" logic checks (`if ($user->status === 'verified' && $user->hasRole('admin'))`).
3. **Password Hashing:** Often scattered between Controllers, Livewire components, and Observers.

**The Strategy:**

* **Reads (Queries):** A `UserBuilder` to encapsulate filtering logic (e.g., "Active Admins").
* **Writes (Commands):** Dedicated Actions for high-risk operations: `RegisterUser`, `BanUser`, `TransitionUserState`.
* **State Machine:** Use `spatie/laravel-model-states` for the lifecycle (`State`) and native PHP Enums for availability (`Status`).

---

## 2. Definitions: State vs. Status

To design this effectively, we must distinguish between the two:

* **Status (Availability):** A simple flag indicating if the user *can* log in. (e.g., `Active`, `Banned`, `Suspended`).
* **State (Lifecycle):** Where the user is in their journey. (e.g., `Onboarding`, `PendingApproval`, `Active`, `Offboarded`).

---

## 3. The Read Model (Builder)

We move scoping logic out of the model.

### `app/Models/Builders/UserBuilder.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\Builders;

use App\Enums\UserStatus;
use App\States\User\Active;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \App\Models\User
 * @extends Builder<TModelClass>
 */
class UserBuilder extends Builder
{
    public function active(): self
    {
        return $this->where('status', UserStatus::ACTIVE);
    }

    public function banned(): self
    {
        return $this->where('status', UserStatus::BANNED);
    }

    /**
     * Scope to users who have completed onboarding (State check).
     */
    public function onboarded(): self
    {
        // Querying JSON state columns (if using spatie/model-states)
        // or simple string columns depending on config.
        return $this->whereState('state', Active::class);
    }

    public function withRole(string $role): self
    {
        return $this->whereHas('roles', fn ($q) => $q->where('name', $role));
    }
}

```

---

## 4. The Write Side (Actions)

These actions encapsulate the business logic and side effects.

### `app/Actions/Users/RegisterUser.php`

Handles the creation, password hashing, and initial role assignment.

```php
<?php

namespace App\Actions\Users;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterUser
{
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // 1. Hash Password Explicitly (Don't rely on Mutators)
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            // 2. Set Defaults
            $data['status'] = UserStatus::PENDING; // Default status

            // 3. Create User
            $user = User::create($data);

            // 4. Assign Default Role (Spatie)
            // If the role depends on the input (e.g. 'type'), handle it here.
            $role = $data['role'] ?? 'customer';
            $user->assignRole($role);

            // 5. Trigger Onboarding (State Transition)
            // If using model-states, it might default to 'New',
            // but we can force a transition if needed.

            return $user;
        });
    }
}

```

### `app/Actions/Users/BanUser.php`

This action proves the value of CQRS. Banning is never just a database update.

```php
<?php

namespace App\Actions\Users;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BanUser
{
    public function handle(User $user, ?string $reason = null): void
    {
        // Idempotency check
        if ($user->status === UserStatus::BANNED) {
            return;
        }

        DB::transaction(function () use ($user, $reason) {
            // 1. Update Status
            $user->update([
                'status' => UserStatus::BANNED,
                'ban_reason' => $reason,
                'banned_at' => now(),
            ]);

            // 2. Side Effect: Revoke all Sessions & Tokens (Sanctum/Passport)
            $user->tokens()->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();

            // 3. Side Effect: Dispatch Event (for Emails/Notifications)
            // UserBanned::dispatch($user);
        });
    }
}

```

### `app/Actions/Users/UpdateUserProfile.php`

Separates "profile updates" (safe) from "credential updates" (sensitive).

```php
<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateUserProfile
{
    public function handle(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            // Handle Password Rotation separately if present
            if (isset($data['password']) && filled($data['password'])) {
                $data['password'] = Hash::make($data['password']);
                // Ideally, revoking tokens should happen here too
            } else {
                unset($data['password']);
            }

            $user->update($data);

            // Handle Role Syncing if provided in data (common in Admin panels)
            if (isset($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            return $user;
        });
    }
}

```

---

## 5. Integration: Filament v5

We integrate these actions into the `UserResource` to ensure the Admin Panel adheres to the same rules as the rest of the app.

### `app/Filament/Resources/UserResource.php`

```php
<?php

namespace App\Filament\Resources;

use App\Actions\Users\RegisterUser;
use App\Actions\Users\UpdateUserProfile;
use App\Actions\Users\BanUser;
use App\Enums\UserStatus;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Notifications\Notification;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('email')->email()->required(),

                // Password only required on creation
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context) => $context === 'create'),

                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),

                // Status is read-only here, managed via Actions (see below)
                Forms\Components\TextInput::make('status')
                    ->disabled()
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => UserStatus::ACTIVE->value,
                        'danger' => UserStatus::BANNED->value,
                    ]),
                Tables\Columns\TextColumn::make('state')->label('Lifecycle'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->using(function (User $record, array $data, UpdateUserProfile $action) {
                        return $action->handle($record, $data);
                    }),

                // Custom Action for "Banning" (Status Change)
                Tables\Actions\Action::make('ban')
                    ->label('Ban User')
                    ->color('danger')
                    ->icon('heroicon-o-no-symbol')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')->required(),
                    ])
                    ->action(function (User $record, array $data, BanUser $action) {
                        $action->handle($record, $data['reason']);
                        Notification::make()->success()->title('User Banned')->send();
                    })
                    ->visible(fn (User $record) => $record->status !== UserStatus::BANNED),
            ]);
    }

    // ... Pages definition
}

```

### `app/Filament/Resources/UserResource/Pages/CreateUser.php`

```php
<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Actions\Users\RegisterUser;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(RegisterUser::class)->handle($data);
    }
}

```

---

## 6. Integration: Livewire v4 (Profile Component)

In the user-facing app (Flux Pro), we use the exact same Actions.

### `app/Livewire/Settings/Profile.php`

```php
<?php

namespace App\Livewire\Settings;

use App\Actions\Users\UpdateUserProfile;
use Livewire\Component;
use Flux; // Assuming Flux UI

class Profile extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = ''; // Optional

    public function mount()
    {
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
    }

    public function save(UpdateUserProfile $action)
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password) {
            $data['password'] = $this->password;
        }

        $action->handle(auth()->user(), $data);

        Flux::toast('Profile updated successfully.');
    }

    public function render()
    {
        return view('livewire.settings.profile');
    }
}

```

---

## 7. Model State Implementation

For the `State` (Lifecycle), we use `spatie/laravel-model-states`. This keeps logic regarding "Can I move from Applied to Verified?" inside State classes, not the User model.

**User Model:**

```php
class User extends Authenticatable
{
    use HasStates;

    protected $casts = [
        'state' => UserState::class,
        'status' => UserStatus::class, // Standard PHP Enum
    ];
}

```

**State Configuration:**

```php
abstract class UserState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Pending::class)
            ->allowTransition(Pending::class, Active::class)
            ->allowTransition(Active::class, Offboarded::class);
    }
}

```

**Action for Transition:**

```php
class TransitionUserState
{
    public function handle(User $user, string $toState): void
    {
        // Spatie Model States handles the validation "Can I transition?"
        if ($user->state->canTransitionTo($toState)) {
             $user->state->transitionTo($toState);

             // Side effects can be handled via Spatie State Events
             // or explicitly here if you prefer strict CQRS control.
        }
    }
}

```
