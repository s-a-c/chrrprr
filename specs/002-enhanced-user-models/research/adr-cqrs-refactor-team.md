# Architecture Decision Record: CQRS Refactor for Teams Module

**Context:** Laravel 12, Livewire v4, Filament v5, Flux Pro
**Topic:** Transitioning a complex "God Model" (STI, Self-Referential) to a CQRS Architecture.

Here is the complete architectural discussion, reasoning, and implementation plan combined into a single "Architecture Decision Record" (ADR) style transcript.

This captures the "Why" (reasoning), the "What" (the specific bottlenecks in your current model), and the "How" (the code).

<details><summary>Click to expand the table of contents</summary>

- [Architecture Decision Record: CQRS Refactor for Teams Module](#architecture-decision-record-cqrs-refactor-for-teams-module)
  - [1. Executive Summary \& Reasoning](#1-executive-summary--reasoning)
  - [2. Implementation: The Read Model](#2-implementation-the-read-model)
    - [2.1. `app/Models/Builders/TeamBuilder.php`](#21-appmodelsbuildersteambuilderphp)
  - [3. Shared Logic: Hierarchy Validation](#3-shared-logic-hierarchy-validation)
    - [3.1. `app/Support/Validation/TeamHierarchyValidator.php`](#31-appsupportvalidationteamhierarchyvalidatorphp)
  - [4. The Write Side (Actions)](#4-the-write-side-actions)
    - [4.1. `app/Actions/Teams/CreateTeam.php`](#41-appactionsteamscreateteamphp)
    - [4.2. `app/Actions/Teams/MoveTeam.php`](#42-appactionsteamsmoveteamphp)
    - [4.3. `app/Actions/Teams/UpdateTeam.php`](#43-appactionsteamsupdateteamphp)
    - [4.4. `app/Actions/Teams/AssignExecutive.php`](#44-appactionsteamsassignexecutivephp)
  - [5. Integration: Filament v5](#5-integration-filament-v5)
    - [5.1. `app/Filament/Resources/TeamResource.php`](#51-appfilamentresourcesteamresourcephp)
    - [5.2. `app/Filament/Resources/TeamResource/Pages/CreateTeam.php`](#52-appfilamentresourcesteamresourcepagescreateteamphp)

</details>

---

## 1. Executive Summary & Reasoning

The current `Team.php` model follows a robust "Active Record" pattern, handling validation, state transitions, and side effects (like permission assignment and tenant propagation) within the model itself.

While convenient for standard CRUD, this creates significant friction in a **Filament v5 + CQRS** context for the following reasons:

1. **Implicit Side Effects:** Logic inside `booted()` or `save()` runs on *every* interaction. In a complex hierarchy (STI + self-referential parents), this makes simple updates expensive and unpredictable.
2. **Coupling:** The Model currently knows too much about the "Write" context (validating hierarchy, assigning executives) and the "Read" context (scoping).
3. **Filament Friction:** Filament expects to mutate models directly. To implement CQRS (Command Query Responsibility Segregation), we must intercept these mutations to run distinct "Actions" instead.

**The Strategy:**
We will "hollow out" the `Team` model.

- **Reads (Queries):** Moved to a `TeamBuilder` (specifically the complex Recursive CTE).
- **Writes (Commands):** Moved to dedicated `Action` classes (Create, Move, AssignExecutive).
- **Validation:** Extracted to a pure Service/Validator, decoupling business rules from the database transaction.

---

## 2. Implementation: The Read Model

**Reasoning:** The `scopeInContext` logic uses a Recursive CTE. This is complex SQL that pollutes the model class. Moving it to a custom Builder keeps the Model clean and allows for better type hinting.

### 2.1. `app/Models/Builders/TeamBuilder.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\Builders;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @template TModelClass of \App\Models\Team
 * @extends Builder<TModelClass>
 */
class TeamBuilder extends Builder
{
    /**
     * Scope the query to the current user's context using a Recursive CTE.
     * This replaces the complex logic previously found in the Model scope.
     */
    public function inContext(?User $user = null): self
    {
        $user = $user ?? auth()->user();

        if (! $user instanceof User || ! $user->current_context_id) {
            return $this;
        }

        $contextId = (int) $user->current_context_id;

        return $this->whereIn('id', function ($query) use ($contextId) {
            // Recursive Common Table Expression (CTE)
            $query->select('id')
                ->from(DB::raw("(
                    WITH RECURSIVE descendants AS (
                        SELECT id, parent_id FROM teams WHERE id = {$contextId}
                        UNION ALL
                        SELECT t.id, t.parent_id FROM teams t
                        JOIN descendants d ON t.parent_id = d.id
                    )
                    SELECT id FROM descendants
                ) as contextual_teams"));
        });
    }

    public function withoutContextScope(): self
    {
        // Explicitly communicates intent, even if it does nothing functionally
        // in a custom builder (vs a global scope).
        return $this;
    }
}

```

**Update `Team.php` to use this builder:**

```php
public function newEloquentBuilder($query): TeamBuilder
{
    return new TeamBuilder($query);
}

```

---

## 3. Shared Logic: Hierarchy Validation

**Reasoning:** Hierarchy rules (depth limits, type compatibility) apply during both `Creation` and `Moving`. Previously, this was in `validateHierarchy()` inside the model. We extract it to a pure validator so it can be called *before* we even open a database transaction.

### 3.1. `app/Support/Validation/TeamHierarchyValidator.php`

```php
<?php

namespace App\Support\Validation;

use App\Enums\TeamType;
use App\Models\Team;
use Illuminate\Validation\ValidationException;

class TeamHierarchyValidator
{
    public static function validate(TeamType $childType, ?Team $parent): void
    {
        // 1. Enterprise Rule
        if ($childType === TeamType::ENTERPRISE) {
            if ($parent !== null) {
                throw ValidationException::withMessages(['parent_id' => 'Enterprises cannot have a parent.']);
            }
            return;
        }

        // 2. Orphan Rule
        if ($parent === null) {
            throw ValidationException::withMessages(['parent_id' => 'This team type requires a parent.']);
        }

        // 3. Type Compatibility Rule
        $validParentType = match ($childType) {
            TeamType::ORGANISATION => TeamType::ENTERPRISE,
            TeamType::DIVISION     => TeamType::ORGANISATION,
            TeamType::DEPARTMENT   => TeamType::DIVISION,
            TeamType::PROJECT      => TeamType::DEPARTMENT,
            default                => null,
        };

        if ($validParentType && $parent->type !== $validParentType) {
            throw ValidationException::withMessages([
                'parent_id' => "{$childType->value} must belong to a {$validParentType->value}.",
            ]);
        }

        // 4. Depth Rule
        if ($parent->getDepth() >= 10) {
            throw ValidationException::withMessages(['parent_id' => 'Max hierarchy depth (10) reached.']);
        }
    }
}

```

---

## 4. The Write Side (Actions)

**Reasoning:**

1. **Transaction Control:** Actions define the transaction boundaries explicitly.
2. **Side Effect Management:** Actions trigger side effects (tenant propagation) explicitly, rather than implicit model observers "magically" doing it.
3. **Cycle Detection:** Moving a team requires checking for cycles, which is distinct from creating a team.

### 4.1. `app/Actions/Teams/CreateTeam.php`

```php
<?php

namespace App\Actions\Teams;

use App\Enums\TeamType;
use App\Models\Team;
use App\Support\Validation\TeamHierarchyValidator;
use Illuminate\Support\Facades\DB;

class CreateTeam
{
    public function handle(array $data): Team
    {
        return DB::transaction(function () use ($data) {
            $type = $data['type'] instanceof TeamType ? $data['type'] : TeamType::from($data['type']);
            $parentId = $data['parent_id'] ?? null;

            // Resolve Parent
            $parent = $parentId ? Team::find($parentId) : null;

            // Run Validation
            TeamHierarchyValidator::validate($type, $parent);

            // Determine Tenant (Enterprise logic)
            $tenantId = null;
            if ($parent) {
                $tenantId = $parent->tenant_id;
            } elseif ($type === TeamType::ENTERPRISE) {
                $tenantId = null; // Will be set to self ID after creation
            }

            // Create
            $team = Team::query()->create([
                ...$data,
                'tenant_id' => $tenantId,
            ]);

            // Fix Enterprise Self-Referential Tenant ID
            if ($type === TeamType::ENTERPRISE) {
                $team->updateQuietly(['tenant_id' => $team->id]);
            }

            return $team;
        });
    }
}

```

### 4.2. `app/Actions/Teams/MoveTeam.php`

```php
<?php

namespace App\Actions\Teams;

use App\Models\Team;
use App\Support\Validation\TeamHierarchyValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MoveTeam
{
    public function handle(Team $team, ?int $newParentId): void
    {
        if ($team->parent_id === $newParentId) {
            return;
        }

        DB::transaction(function () use ($team, $newParentId) {
            $newParent = $newParentId ? Team::find($newParentId) : null;

            // 1. Basic Hierarchy Validation
            TeamHierarchyValidator::validate($team->type, $newParent);

            // 2. Cycle Detection (Specific to Moving)
            if ($newParent && $newParent->isDescendantOf($team)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Cannot move a team into its own descendant.',
                ]);
            }

            // 3. Perform Move
            $team->parent_id = $newParentId;

            // Update Tenant ID immediately for this node
            if ($newParent) {
                $team->tenant_id = $newParent->tenant_id;
            }

            $team->save();

            // 4. Handle Side Effects (Recursion)
            if ($team->wasChanged('tenant_id')) {
                // Ideally dispatch a job here: PropagateTenantId::dispatch($team);
                $team->updateDescendantTenants((string)$team->tenant_id);
            }
        });
    }
}

```

### 4.3. `app/Actions/Teams/UpdateTeam.php`

This is the main entry point for the Filament "Edit" page. It detects if a move is required and delegates to `MoveTeam`.

```php
<?php

namespace App\Actions\Teams;

use App\Exceptions\OptimisticLockingException;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class UpdateTeam
{
    public function handle(Team $team, array $data): Team
    {
        return DB::transaction(function () use ($team, $data) {
            // Manual Optimistic Lock Check (if not using Filament's native)
            if (isset($data['lock_version'])) {
                $currentDbVersion = DB::table('teams')->where('id', $team->id)->value('lock_version');
                if ((int)$currentDbVersion !== (int)$data['lock_version']) {
                    throw new OptimisticLockingException();
                }
            }

            // If parent_id is changing, delegate to MoveTeam action
            if (array_key_exists('parent_id', $data) && $data['parent_id'] !== $team->parent_id) {
                app(MoveTeam::class)->handle($team, $data['parent_id']);
                unset($data['parent_id']); // Remove so we don't double-update
            }

            // Standard Update
            $team->update($data);

            return $team;
        });
    }
}

```

### 4.4. `app/Actions/Teams/AssignExecutive.php`

**Fix:** Removed reliance on `setPermissionsTeamId` global state manipulation, which is dangerous in async/job contexts.

```php
<?php

namespace App\Actions\Teams;

use App\Models\Team;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AssignExecutive
{
    public function handle(Team $team, User $user): void
    {
        // 1. Check if user is already executive
        if ($user->hasRole('executive', $team)) {
            return;
        }

        // 2. Check if team already has an executive
        $existingExec = User::role('executive', $team)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($existingExec) {
            throw ValidationException::withMessages([
                'executive' => ['This team already has an executive assigned.'],
            ]);
        }

        // 3. Assign using Spatie's team-aware method
        $user->assignRole('executive', $team);
    }
}

```

---

## 5. Integration: Filament v5

**Reasoning:** Filament is inherently "Active Record" driven. To use CQRS, we must override the default `CreateAction` and `EditAction` to use our new classes instead of standard model saving.

### 5.1. `app/Filament/Resources/TeamResource.php`

```php
<?php

namespace App\Filament\Resources;

use App\Actions\Teams\UpdateTeam;
use App\Models\Team;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\TeamResource\Pages;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),

                Forms\Components\Select::make('type')
                    ->options(\App\Enums\TeamType::class)
                    ->live(),

                Forms\Components\Select::make('parent_id')
                    ->label('Parent Team')
                    ->relationship('parent', 'name')
                    // Prevent cycles in UI: Simple exclusion of self
                    ->getOptionLabelFromRecordUsing(fn (Team $record) => $record->name)
                    ->options(function (?Team $record) {
                        $query = Team::query();
                        if ($record) {
                            $query->where('id', '!=', $record->id);
                        }
                        return $query->pluck('name', 'id');
                    })
                    ->required(fn (Forms\Get $get) => $get('type') !== 'enterprise')
                    ->hidden(fn (Forms\Get $get) => $get('type') === 'enterprise'),

                Forms\Components\Hidden::make('lock_version'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('parent.name')->label('Parent'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    // KEY INTEGRATION POINT: Intercept Save
                    ->using(function (Team $record, array $data, UpdateTeam $action): Team {
                        return $action->handle($record, $data);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
        ];
    }
}

```

### 5.2. `app/Filament/Resources/TeamResource/Pages/CreateTeam.php`

```php
<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Actions\Teams\CreateTeam as CreateTeamAction;
use App\Filament\Resources\TeamResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Inject and use the Action
        return app(CreateTeamAction::class)->handle($data);
    }
}

```

---
