# Laravel 12, Filament 4, Polymorphic STI Team Model with Spatie Packages

<details><summary>Table of Contents</summary>

## Table of Contents

- [Laravel 12, Filament 4, Polymorphic STI Team Model with Spatie Packages](#laravel-12-filament-4-polymorphic-sti-team-model-with-spatie-packages)
  - [Table of Contents](#table-of-contents)
  - [1. Prompt](#1-prompt)
    - [1.1. Response](#11-response)
      - [1.1.1. Base Team Model with STI and Adjacency List](#111-base-team-model-with-sti-and-adjacency-list)
      - [1.1.2. STI Child Models](#112-sti-child-models)
      - [1.1.3. Enums](#113-enums)
      - [1.1.4. States](#114-states)
      - [1.1.5. DTOs](#115-dtos)
      - [1.1.6. Value Objects](#116-value-objects)
      - [1.1.7. Migration](#117-migration)
      - [1.1.8. Filament Resource](#118-filament-resource)
    - [1.2. Summary](#12-summary)
  - [2. Prompt](#2-prompt)
    - [2.1. Response](#21-response)
      - [2.1.1. Updated Team Model](#211-updated-team-model)
      - [2.1.2. Updated Migration](#212-updated-migration)
      - [2.1.3. Updated DTOs](#213-updated-dtos)
      - [2.1.4. Updated Filament Resource](#214-updated-filament-resource)
      - [2.1.5. Routes for Public Access](#215-routes-for-public-access)
      - [2.1.6. Team Controller](#216-team-controller)
      - [2.1.7. Factory for Testing](#217-factory-for-testing)
    - [2.2. Summary](#22-summary)
  - [3. Prompt](#3-prompt)
    - [3.1. Response](#31-response)
      - [3.1.1. Updated Team Model](#311-updated-team-model)
      - [3.1.2. Updated Migration with Unique Constraint](#312-updated-migration-with-unique-constraint)
      - [3.1.3. Custom Validation Rule for Graph Uniqueness](#313-custom-validation-rule-for-graph-uniqueness)
      - [3.1.4. Updated Filament Resource](#314-updated-filament-resource)
      - [3.1.5. Updated Factory](#315-updated-factory)
    - [3.2. Summary](#32-summary)
  - [4. Prompt](#4-prompt)
    - [4.1. Response](#41-response)
      - [4.1.1. Updated Migration with Tenant Support](#411-updated-migration-with-tenant-support)
      - [4.1.2. Tenant Model (Enterprise)](#412-tenant-model-enterprise)
      - [4.1.3. Updated Base Team Model with Single-Database Tenancy](#413-updated-base-team-model-with-single-database-tenancy)
      - [4.1.4. Enterprise Model (Tenant Implementation)](#414-enterprise-model-tenant-implementation)
      - [4.1.5. Tenancy Configuration](#415-tenancy-configuration)
      - [4.1.6. Tenancy Service Provider](#416-tenancy-service-provider)
      - [4.1.7. Routes Configuration](#417-routes-configuration)
      - [4.1.8. Filament Panel Provider (Tenant Panel)](#418-filament-panel-provider-tenant-panel)
      - [4.1.9. Updated Filament Resource for Teams (Filament v5 Syntax)](#419-updated-filament-resource-for-teams-filament-v5-syntax)
      - [4.1.10. Livewire v4 Team Hierarchy Component](#4110-livewire-v4-team-hierarchy-component)
      - [4.1.11. Team Node Blade Component](#4111-team-node-blade-component)
      - [4.1.12. Updated Enums with Filament v5 Contracts](#4112-updated-enums-with-filament-v5-contracts)
      - [4.1.13. Bootstrap File Updates](#4113-bootstrap-file-updates)
    - [4.2. Summary](#42-summary)
      - [4.2.1. Key Updates Made:](#421-key-updates-made)

</details>

---

## 1. Prompt

in a PHP 8.3, Laravel 12 application:
using:

- binafy/laravel-user-monitoring
- filament/filament:^5.0
- laravel-auditing/auditing
- livewire/livewire:^4.0
- spatie/laravel-activitylog
- spatie/laravel-data
- spatie/laravel-model-states
- spatie/laravel-model-status
- spatie/laravel-permission
- spatie/laravel-sluggable
- spatie/laravel-tags
- stancl/tenancy
- staudenmeir/laravel-adjacency-list
- symfony/uid
- tightenco/parental
- weston-flores/laravel-impersonatable-guard

A self-referential, polymorphic, STI model, `Team`
Hierarchy: Enterprise → Organisation → Division → Department
Root constraint: Only Enterprise can be root-level
Additional types: Project (non-root)
Only `Team` is allowed in any graph more than once
Self-referential: Each entity can have a parent of the same base type
Polymorphic STI: All types share the same table with type discrimination
All models have a mandatory `Executive` and optional `Deputy`, both related to `User` model
Executive and Deputy may not be equal
A `Team` has a lifecycle, managed using `spatie/laravel-model-states` and `spatie/laravel-model-status`
incorporate filament helpers to facilitate colours and labels for the types and statuses and include avatars for Team models

Make use of:

- Enums
- DTOs
- Value Objects

wherever appropriate

### 1.1. Response

I'll help you create a comprehensive Laravel application structure with the specifications you've outlined. Let me break this down into the key components:

#### 1.1.1. Base Team Model with STI and Adjacency List

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamType;
use App\States\TeamState;
use App\ValueObjects\TeamHierarchy;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\LaravelData\WithData;
use Spatie\ModelStates\HasStates;
use Spatie\ModelStatus\HasStatuses;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;
use Tightenco\Parental\HasParent;

class Team extends Model
{
    use HasRecursiveRelationships,
        HasStates,
        HasStatuses,
        SoftDeletes,
        WithData;

    protected $fillable = [
        'name',
        'description',
        'type',
        'parent_id',
        'executive_id',
        'deputy_id',
    ];

    protected $casts = [
        'type' => TeamType::class,
        'state' => TeamState::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (Team $team) {
            $team->validateHierarchyRules();
        });

        static::updating(function (Team $team) {
            $team->validateHierarchyRules();
            $team->validateExecutiveDeputyConstraint();
        });
    }

    // Relationships
    public function executive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executive_id');
    }

    public function deputy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deputy_id');
    }

    // Validation Methods
    protected function validateHierarchyRules(): void
    {
        $hierarchy = new TeamHierarchy($this);
        $hierarchy->validate();
    }

    protected function validateExecutiveDeputyConstraint(): void
    {
        if ($this->executive_id && $this->deputy_id &&
            $this->executive_id === $this->deputy_id) {
            throw new \InvalidArgumentException(
                'Executive and Deputy cannot be the same person'
            );
        }
    }

    // Filament Helpers
    public function getTypeColor(): string
    {
        return match ($this->type) {
            TeamType::Enterprise => Color::Purple[500],
            TeamType::Organisation => Color::Blue[500],
            TeamType::Division => Color::Green[500],
            TeamType::Department => Color::Orange[500],
            TeamType::Project => Color::Gray[500],
        };
    }

    public function getTypeLabel(): string
    {
        return $this->type->getLabel();
    }

    public function getStatusColor(): string
    {
        return $this->latestStatus()?->color ?? Color::Gray[400];
    }

    public function getAvatarUrl(): ?string
    {
        return $this->executive?->avatar_url;
    }

    // Scopes
    public function scopeOfType($query, TeamType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRootLevel($query)
    {
        return $query->whereNull('parent_id');
    }
}

```

#### 1.1.2. STI Child Models

```php
<?php

declare(strict_types=1);

namespace App\Models\Teams;

use App\Enums\TeamType;
use App\Models\Team;
use Tightenco\Parental\HasChildren;

class Enterprise extends Team
{
    use HasChildren;

    protected $childTypes = [
        'organisation' => Organisation::class,
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Enterprise $enterprise) {
            $enterprise->type = TeamType::Enterprise;
            $enterprise->parent_id = null; // Enforce root constraint
        });
    }

    public function canHaveParent(): bool
    {
        return false;
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Models\Teams;

use App\Enums\TeamType;
use App\Models\Team;
use Tightenco\Parental\HasParent;

class Organisation extends Team
{
    use HasParent;

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Organisation $organisation) {
            $organisation->type = TeamType::Organisation;
        });
    }

    public function getAllowedParentTypes(): array
    {
        return [TeamType::Enterprise];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Models\Teams;

use App\Enums\TeamType;
use App\Models\Team;
use Tightenco\Parental\HasParent;

class Division extends Team
{
    use HasParent;

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Division $division) {
            $division->type = TeamType::Division;
        });
    }

    public function getAllowedParentTypes(): array
    {
        return [TeamType::Organisation, TeamType::Division];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Models\Teams;

use App\Enums\TeamType;
use App\Models\Team;
use Tightenco\Parental\HasParent;

class Department extends Team
{
    use HasParent;

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Department $department) {
            $department->type = TeamType::Department;
        });
    }

    public function getAllowedParentTypes(): array
    {
        return [TeamType::Division, TeamType::Department];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Models\Teams;

use App\Enums\TeamType;
use App\Models\Team;
use Tightenco\Parental\HasParent;

class Project extends Team
{
    use HasParent;

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Project $project) {
            $project->type = TeamType::Project;
        });
    }

    public function getAllowedParentTypes(): array
    {
        return [
            TeamType::Enterprise,
            TeamType::Organisation,
            TeamType::Division,
            TeamType::Department,
        ];
    }

    public function canBeRoot(): bool
    {
        return false;
    }
}

```

#### 1.1.3. Enums

```php
<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TeamType: string implements HasLabel, HasColor
{
    case Enterprise = 'enterprise';
    case Organisation = 'organisation';
    case Division = 'division';
    case Department = 'department';
    case Project = 'project';

    public function getLabel(): string
    {
        return match ($this) {
            self::Enterprise => 'Enterprise',
            self::Organisation => 'Organisation',
            self::Division => 'Division',
            self::Department => 'Department',
            self::Project => 'Project',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Enterprise => 'purple',
            self::Organisation => 'blue',
            self::Division => 'green',
            self::Department => 'orange',
            self::Project => 'gray',
        };
    }

    public function canBeRoot(): bool
    {
        return $this === self::Enterprise;
    }

    public function getAllowedParentTypes(): array
    {
        return match ($this) {
            self::Enterprise => [],
            self::Organisation => [self::Enterprise],
            self::Division => [self::Organisation, self::Division],
            self::Department => [self::Division, self::Department],
            self::Project => [
                self::Enterprise,
                self::Organisation,
                self::Division,
                self::Department,
            ],
        };
    }
}

```

#### 1.1.4. States

```php
<?php

declare(strict_types=1);

namespace App\States;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class TeamState extends State
{
    abstract public function color(): string;
    abstract public function label(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, Active::class)
            ->allowTransition(Active::class, Inactive::class)
            ->allowTransition(Inactive::class, Active::class)
            ->allowTransition([Draft::class, Inactive::class], Archived::class);
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\States;

class Draft extends TeamState
{
    public function color(): string
    {
        return 'gray';
    }

    public function label(): string
    {
        return 'Draft';
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\States;

class Active extends TeamState
{
    public function color(): string
    {
        return 'success';
    }

    public function label(): string
    {
        return 'Active';
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\States;

class Inactive extends TeamState
{
    public function color(): string
    {
        return 'warning';
    }

    public function label(): string
    {
        return 'Inactive';
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\States;

class Archived extends TeamState
{
    public function color(): string
    {
        return 'danger';
    }

    public function label(): string
    {
        return 'Archived';
    }
}

```

#### 1.1.5. DTOs

```php
<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TeamType;
use App\Models\User;
use Spatie\LaravelData\Data;

class TeamData extends Data
{
    public function __construct(
        public string $name,
        public ?string $description,
        public TeamType $type,
        public ?int $parent_id,
        public int $executive_id,
        public ?int $deputy_id,
    ) {}

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'enum:' . TeamType::class],
            'parent_id' => ['nullable', 'exists:teams,id'],
            'executive_id' => ['required', 'exists:users,id'],
            'deputy_id' => ['nullable', 'exists:users,id', 'different:executive_id'],
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TeamType;
use Spatie\LaravelData\Data;

class TeamHierarchyData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public TeamType $type,
        public ?int $parent_id,
        public int $depth,
        public array $children = [],
    ) {}
}

```

#### 1.1.6. Value Objects

```php
<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\TeamType;
use App\Models\Team;
use InvalidArgumentException;

class TeamHierarchy
{
    public function __construct(private Team $team) {}

    public function validate(): void
    {
        $this->validateRootConstraint();
        $this->validateParentTypeConstraint();
        $this->validateCircularReference();
    }

    private function validateRootConstraint(): void
    {
        if (!$this->team->parent_id && !$this->team->type->canBeRoot()) {
            throw new InvalidArgumentException(
                "Only Enterprise teams can be root-level. {$this->team->type->getLabel()} must have a parent."
            );
        }
    }

    private function validateParentTypeConstraint(): void
    {
        if (!$this->team->parent_id) {
            return;
        }

        $parent = Team::find($this->team->parent_id);
        if (!$parent) {
            throw new InvalidArgumentException('Parent team does not exist.');
        }

        $allowedParentTypes = $this->team->type->getAllowedParentTypes();
        if (!in_array($parent->type, $allowedParentTypes)) {
            throw new InvalidArgumentException(
                "Invalid parent type. {$this->team->type->getLabel()} can only have parents of type: " .
                implode(', ', array_map(fn($type) => $type->getLabel(), $allowedParentTypes))
            );
        }
    }

    private function validateCircularReference(): void
    {
        if (!$this->team->parent_id) {
            return;
        }

        $ancestors = $this->team->ancestors()->pluck('id')->toArray();
        if (in_array($this->team->id, $ancestors)) {
            throw new InvalidArgumentException(
                'Circular reference detected in team hierarchy.'
            );
        }
    }

    public function getDepth(): int
    {
        return $this->team->ancestors()->count();
    }

    public function getPath(): array
    {
        return $this->team->ancestorsAndSelf()
            ->orderBy('depth')
            ->pluck('name')
            ->toArray();
    }
}

```

#### 1.1.7. Migration

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type');
            $table->foreignId('parent_id')->nullable()->constrained('teams');
            $table->foreignId('executive_id')->constrained('users');
            $table->foreignId('deputy_id')->nullable()->constrained('users');
            $table->string('state')->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'parent_id']);
            $table->index('state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};

```

#### 1.1.8. Filament Resource

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Data\TeamData;
use App\Enums\TeamType;
use App\Filament\Resources\TeamResource\Pages;
use App\Models\Team;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

            Forms\Components\Textarea::make('description')
                ->maxLength(65535),

            Forms\Components\Select::make('type')
                ->options(TeamType::class)
                ->required()
                ->reactive()
                ->afterStateUpdated(fn($state, callable $set) =>
                    $state === TeamType::Enterprise->value ? $set('parent_id', null) : null
                ),

            Forms\Components\Select::make('parent_id')
                ->relationship('parent', 'name')
                ->searchable()
                ->hidden(fn(callable $get) =>
                    $get('type') === TeamType::Enterprise->value
                ),

            Forms\Components\Select::make('executive_id')
                ->relationship('executive', 'name')
                ->searchable()
                ->required(),

            Forms\Components\Select::make('deputy_id')
                ->relationship('deputy', 'name')
                ->searchable()
                ->different('executive_id'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->color(fn(Team $record) => $record->getTypeColor())
                    ->formatStateUsing(fn($state) => $state->getLabel()),

                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent')
                    ->sortable(),

                Tables\Columns\TextColumn::make('executive.name')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('state')
                    ->color(fn(Team $record) => $record->state->color())
                    ->formatStateUsing(fn($state) => $state->label()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(TeamType::class),
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

### 1.2. Summary

This implementation provides:

1. Self-referential polymorphic STI with proper hierarchy validation
2. Adjacency list support for efficient tree operations
3. State management with Spatie packages
4. Filament integration with colors, labels, and avatars
5. Proper validation for business rules
6. DTOs and Value Objects for clean data handling
7. Enums for type safety

The structure enforces all your constraints while maintaining clean, maintainable code.

---

## 2. Prompt

incorporate slugs by `spatie/laravel-sluggable`
incorporate ULID as secondary unique key, from `symfony/uid`

### 2.1. Response

I'll incorporate slugs and ULIDs into the existing structure. Here are the updated components:

#### 2.1.1. Updated Team Model

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamType;
use App\States\TeamState;
use App\ValueObjects\TeamHierarchy;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\LaravelData\WithData;
use Spatie\ModelStates\HasStates;
use Spatie\ModelStatus\HasStatuses;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;
use Symfony\Component\Uid\Ulid;
use Tightenco\Parental\HasParent;

class Team extends Model
{
    use HasRecursiveRelationships,
        HasStates,
        HasStatuses,
        HasSlug,
        SoftDeletes,
        WithData;

    protected $fillable = [
        'name',
        'slug',
        'ulid',
        'description',
        'type',
        'parent_id',
        'executive_id',
        'deputy_id',
    ];

    protected $casts = [
        'type' => TeamType::class,
        'state' => TeamState::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (Team $team) {
            if (!$team->ulid) {
                $team->ulid = (string) new Ulid();
            }
            $team->validateHierarchyRules();
        });

        static::updating(function (Team $team) {
            $team->validateHierarchyRules();
            $team->validateExecutiveDeputyConstraint();
        });
    }

    // Slug Configuration
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['name', 'type'])
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate()
            ->slugsShouldBeNoLongerThan(100)
            ->usingSeparator('-')
            ->usingLanguage('en')
            ->preventOverwrite();
    }

    public function generateSlugFrom(): string
    {
        $parentSlug = $this->parent?->slug ?? '';
        $typePrefix = strtolower($this->type->value);

        return $parentSlug
            ? "{$parentSlug}-{$typePrefix}-{$this->name}"
            : "{$typePrefix}-{$this->name}";
    }

    // Route Key Name for URL binding
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    // Alternative route binding by slug
    public function resolveRouteBinding($value, $field = null)
    {
        return match ($field) {
            'slug' => $this->where('slug', $value)->first(),
            'ulid' => $this->where('ulid', $value)->first(),
            default => parent::resolveRouteBinding($value, $field),
        };
    }

    // Relationships
    public function executive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executive_id');
    }

    public function deputy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deputy_id');
    }

    // Validation Methods
    protected function validateHierarchyRules(): void
    {
        $hierarchy = new TeamHierarchy($this);
        $hierarchy->validate();
    }

    protected function validateExecutiveDeputyConstraint(): void
    {
        if ($this->executive_id && $this->deputy_id &&
            $this->executive_id === $this->deputy_id) {
            throw new \InvalidArgumentException(
                'Executive and Deputy cannot be the same person'
            );
        }
    }

    // Filament Helpers
    public function getTypeColor(): string
    {
        return match ($this->type) {
            TeamType::Enterprise => Color::Purple[500],
            TeamType::Organisation => Color::Blue[500],
            TeamType::Division => Color::Green[500],
            TeamType::Department => Color::Orange[500],
            TeamType::Project => Color::Gray[500],
        };
    }

    public function getTypeLabel(): string
    {
        return $this->type->getLabel();
    }

    public function getStatusColor(): string
    {
        return $this->latestStatus()?->color ?? Color::Gray[400];
    }

    public function getAvatarUrl(): ?string
    {
        return $this->executive?->avatar_url;
    }

    public function getFullSlugPath(): string
    {
        return $this->ancestorsAndSelf()
            ->orderBy('depth')
            ->pluck('slug')
            ->filter()
            ->implode('/');
    }

    public function getPublicUrl(): string
    {
        return route('teams.show', ['team' => $this->ulid]);
    }

    public function getSlugUrl(): string
    {
        return route('teams.show-by-slug', ['slug' => $this->slug]);
    }

    // Scopes
    public function scopeOfType($query, TeamType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRootLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeByUlid($query, string $ulid)
    {
        return $query->where('ulid', $ulid);
    }

    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }
}

```

#### 2.1.2. Updated Migration

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type');
            $table->foreignId('parent_id')->nullable()->constrained('teams');
            $table->foreignId('executive_id')->constrained('users');
            $table->foreignId('deputy_id')->nullable()->constrained('users');
            $table->string('state')->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'parent_id']);
            $table->index('state');
            $table->index('ulid');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};

```

#### 2.1.3. Updated DTOs

php

```php
<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TeamType;
use Spatie\LaravelData\Data;

class TeamData extends Data
{
    public function __construct(
        public string $name,
        public ?string $slug,
        public ?string $ulid,
        public ?string $description,
        public TeamType $type,
        public ?int $parent_id,
        public int $executive_id,
        public ?int $deputy_id,
    ) {}

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:teams,slug'],
            'ulid' => ['nullable', 'string', 'size:26', 'unique:teams,ulid'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'enum:' . TeamType::class],
            'parent_id' => ['nullable', 'exists:teams,id'],
            'executive_id' => ['required', 'exists:users,id'],
            'deputy_id' => ['nullable', 'exists:users,id', 'different:executive_id'],
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TeamType;
use Spatie\LaravelData\Data;

class TeamHierarchyData extends Data
{
    public function __construct(
        public int $id,
        public string $ulid,
        public string $name,
        public string $slug,
        public TeamType $type,
        public ?int $parent_id,
        public int $depth,
        public string $full_slug_path,
        public array $children = [],
    ) {}
}

```

```php
<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class TeamIdentifierData extends Data
{
    public function __construct(
        public int $id,
        public string $ulid,
        public string $slug,
        public string $name,
    ) {}

    public function getPublicUrl(): string
    {
        return route('teams.show', ['team' => $this->ulid]);
    }

    public function getSlugUrl(): string
    {
        return route('teams.show-by-slug', ['slug' => $this->slug]);
    }
}

```

#### 2.1.4. Updated Filament Resource

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Data\TeamData;
use App\Enums\TeamType;
use App\Filament\Resources\TeamResource\Pages;
use App\Models\Team;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic Information')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                            if ($operation !== 'create') {
                                return;
                            }
                            $set('slug', \Str::slug($state));
                        }),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(100)
                        ->unique(ignoreRecord: true)
                        ->rules(['alpha_dash'])
                        ->helperText('Auto-generated from name, but can be customized'),

                    Forms\Components\Textarea::make('description')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Hierarchy')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->options(TeamType::class)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state === TeamType::Enterprise->value) {
                                $set('parent_id', null);
                            }
                        }),

                    Forms\Components\Select::make('parent_id')
                        ->relationship(
                            'parent',
                            'name',
                            fn(Builder $query, Forms\Get $get) => $query
                                ->when($get('type'), function ($query, $type) {
                                    $allowedParentTypes = TeamType::from($type)
                                        ->getAllowedParentTypes();
                                    if (!empty($allowedParentTypes)) {
                                        $query->whereIn('type', $allowedParentTypes);
                                    }
                                })
                        )
                        ->searchable()
                        ->preload()
                        ->hidden(fn(Forms\Get $get) =>
                            $get('type') === TeamType::Enterprise->value
                        )
                        ->helperText(function (Forms\Get $get) {
                            if (!$get('type')) return null;

                            $allowedTypes = TeamType::from($get('type'))
                                ->getAllowedParentTypes();

                            return empty($allowedTypes)
                                ? 'This type cannot have a parent'
                                : 'Allowed parent types: ' .
                                  implode(', ', array_map(
                                      fn($type) => $type->getLabel(),
                                      $allowedTypes
                                  ));
                        }),
                ]),

            Forms\Components\Section::make('Leadership')
                ->schema([
                    Forms\Components\Select::make('executive_id')
                        ->relationship('executive', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),

                    Forms\Components\Select::make('deputy_id')
                        ->relationship('deputy', 'name')
                        ->searchable()
                        ->preload()
                        ->different('executive_id')
                        ->helperText('Deputy must be different from Executive'),
                ]),

            Forms\Components\Section::make('System Information')
                ->schema([
                    Forms\Components\TextInput::make('ulid')
                        ->label('ULID')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Auto-generated unique identifier'),
                ])
                ->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Team $record) => $record->slug),

                Tables\Columns\BadgeColumn::make('type')
                    ->color(fn(Team $record) => $record->type->getColor())
                    ->formatStateUsing(fn($state) => $state->getLabel()),

                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent')
                    ->sortable()
                    ->placeholder('Root Level'),

                Tables\Columns\TextColumn::make('executive.name')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('deputy.name')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Not assigned'),

                Tables\Columns\BadgeColumn::make('state')
                    ->color(fn(Team $record) => $record->state->color())
                    ->formatStateUsing(fn($state) => $state->label()),

                Tables\Columns\TextColumn::make('ulid')
                    ->label('ULID')
                    ->copyable()
                    ->copyMessage('ULID copied')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(TeamType::class),

                Tables\Filters\SelectFilter::make('state')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'archived' => 'Archived',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view_public')
                    ->label('View Public')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Team $record) => $record->getPublicUrl())
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('copy_ulid')
                    ->label('Copy ULID')
                    ->icon('heroicon-o-clipboard')
                    ->action(function (Team $record) {
                        // This would typically use JavaScript to copy to clipboard
                        // For now, we'll just show a notification
                        \Filament\Notifications\Notification::make()
                            ->title('ULID: ' . $record->ulid)
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['parent', 'executive', 'deputy']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'ulid', 'description'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Type' => $record->type->getLabel(),
            'Parent' => $record->parent?->name ?? 'Root Level',
            'Executive' => $record->executive->name,
            'ULID' => $record->ulid,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record:ulid}/edit'),
            'view' => Pages\ViewTeam::route('/{record:ulid}'),
        ];
    }
}

```

#### 2.1.5. Routes for Public Access

```php
<?php

// routes/web.php

declare(strict_types=1);

use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::prefix('teams')->name('teams.')->group(function () {
    Route::get('/{team:ulid}', [TeamController::class, 'show'])
        ->name('show');

    Route::get('/slug/{slug}', [TeamController::class, 'showBySlug'])
        ->name('show-by-slug');

    Route::get('/{team:ulid}/hierarchy', [TeamController::class, 'hierarchy'])
        ->name('hierarchy');
});

```

#### 2.1.6. Team Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\TeamHierarchyData;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function show(Team $team)
    {
        $team->load(['parent', 'children', 'executive', 'deputy']);

        return view('teams.show', compact('team'));
    }

    public function showBySlug(string $slug)
    {
        $team = Team::where('slug', $slug)->firstOrFail();

        return $this->show($team);
    }

    public function hierarchy(Team $team)
    {
        $hierarchyData = TeamHierarchyData::from([
            'id' => $team->id,
            'ulid' => $team->ulid,
            'name' => $team->name,
            'slug' => $team->slug,
            'type' => $team->type,
            'parent_id' => $team->parent_id,
            'depth' => $team->ancestors()->count(),
            'full_slug_path' => $team->getFullSlugPath(),
            'children' => $team->children()
                ->with(['children', 'executive'])
                ->get()
                ->map(fn($child) => TeamHierarchyData::from($child))
                ->toArray(),
        ]);

        return response()->json($hierarchyData);
    }
}

```

#### 2.1.7. Factory for Testing

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TeamType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Symfony\Component\Uid\Ulid;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'ulid' => (string) new Ulid(),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(TeamType::cases()),
            'executive_id' => User::factory(),
            'deputy_id' => $this->faker->boolean(70) ? User::factory() : null,
        ];
    }

    public function enterprise(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => TeamType::Enterprise,
            'parent_id' => null,
        ]);
    }

    public function organisation(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => TeamType::Organisation,
        ]);
    }

    public function division(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => TeamType::Division,
        ]);
    }

    public function department(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => TeamType::Department,
        ]);
    }

    public function project(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => TeamType::Project,
        ]);
    }
}

```

### 2.2. Summary

Key Features Added:

1. ULID Integration: 26-character unique identifiers for public URLs
2. Slug Generation: Hierarchical slugs with type prefixes
3. Dual Route Binding: Support for both ULID and slug-based URLs
4. Enhanced Filament UI: Better form organization and validation
5. Public URLs: Clean URLs for external access
6. Search Enhancement: Global search includes ULID and slug
7. Factory Support: Testing with proper ULID generation

The implementation maintains all previous functionality while adding robust slug and ULID support for better URL management and external access patterns.

## 3. Prompt

names must be unique within their graph
slugs are generated from name and name of parent

### 3.1. Response

I'll update the implementation to enforce unique names within each graph and generate slugs from name + parent name. Here are the key changes:

#### 3.1.1. Updated Team Model

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamType;
use App\States\TeamState;
use App\ValueObjects\TeamHierarchy;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\WithData;
use Spatie\ModelStates\HasStates;
use Spatie\ModelStatus\HasStatuses;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;
use Symfony\Component\Uid\Ulid;
use Tightenco\Parental\HasParent;

class Team extends Model
{
    use HasRecursiveRelationships,
        HasStates,
        HasStatuses,
        HasSlug,
        SoftDeletes,
        WithData;

    protected $fillable = [
        'name',
        'slug',
        'ulid',
        'description',
        'type',
        'parent_id',
        'executive_id',
        'deputy_id',
    ];

    protected $casts = [
        'type' => TeamType::class,
        'state' => TeamState::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (Team $team) {
            if (!$team->ulid) {
                $team->ulid = (string) new Ulid();
            }
            $team->validateUniqueNameInGraph();
            $team->validateHierarchyRules();
        });

        static::updating(function (Team $team) {
            if ($team->isDirty('name') || $team->isDirty('parent_id')) {
                $team->validateUniqueNameInGraph();
            }
            $team->validateHierarchyRules();
            $team->validateExecutiveDeputyConstraint();
        });

        static::saved(function (Team $team) {
            // Regenerate slugs for descendants when parent name changes
            if ($team->isDirty('name')) {
                $team->updateDescendantSlugs();
            }
        });
    }

    // Slug Configuration
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('generateSlugSource')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(150)
            ->usingSeparator('-')
            ->usingLanguage('en');
    }

    public function generateSlugSource(): string
    {
        $parentName = $this->parent?->name ?? '';

        return $parentName
            ? "{$parentName} {$this->name}"
            : $this->name;
    }

    // Route Key Name for URL binding
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    // Alternative route binding by slug
    public function resolveRouteBinding($value, $field = null)
    {
        return match ($field) {
            'slug' => $this->where('slug', $value)->first(),
            'ulid' => $this->where('ulid', $value)->first(),
            default => parent::resolveRouteBinding($value, $field),
        };
    }

    // Relationships
    public function executive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executive_id');
    }

    public function deputy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deputy_id');
    }

    // Validation Methods
    protected function validateUniqueNameInGraph(): void
    {
        $rootId = $this->getRootId();

        $query = static::query()
            ->where('name', $this->name)
            ->where(function ($query) use ($rootId) {
                if ($rootId) {
                    // For non-root teams, check within the same graph
                    $query->whereHas('ancestors', function ($ancestorQuery) use ($rootId) {
                        $ancestorQuery->where('id', $rootId);
                    })->orWhere('id', $rootId);
                } else {
                    // For root teams, check only other root teams
                    $query->whereNull('parent_id');
                }
            });

        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => ['The name must be unique within the team hierarchy graph.']
            ]);
        }
    }

    protected function getRootId(): ?int
    {
        if (!$this->parent_id) {
            return null; // This is a root team
        }

        // Get the root ancestor
        $root = $this->ancestors()->whereNull('parent_id')->first();
        return $root?->id;
    }

    protected function validateHierarchyRules(): void
    {
        $hierarchy = new TeamHierarchy($this);
        $hierarchy->validate();
    }

    protected function validateExecutiveDeputyConstraint(): void
    {
        if ($this->executive_id && $this->deputy_id &&
            $this->executive_id === $this->deputy_id) {
            throw new \InvalidArgumentException(
                'Executive and Deputy cannot be the same person'
            );
        }
    }

    protected function updateDescendantSlugs(): void
    {
        $this->descendants()->each(function (Team $descendant) {
            $descendant->generateSlug();
            $descendant->saveQuietly(); // Avoid triggering events
        });
    }

    // Helper Methods
    public function getGraphRoot(): ?Team
    {
        return $this->ancestors()->whereNull('parent_id')->first() ??
               ($this->parent_id ? null : $this);
    }

    public function getGraphTeams(): \Illuminate\Database\Eloquent\Collection
    {
        $root = $this->getGraphRoot();

        if (!$root) {
            return collect([$this]);
        }

        return $root->descendantsAndSelf();
    }

    public function isNameUniqueInGraph(string $name, ?int $excludeId = null): bool
    {
        $rootId = $this->getRootId();

        $query = static::query()
            ->where('name', $name)
            ->where(function ($query) use ($rootId) {
                if ($rootId) {
                    $query->whereHas('ancestors', function ($ancestorQuery) use ($rootId) {
                        $ancestorQuery->where('id', $rootId);
                    })->orWhere('id', $rootId);
                } else {
                    $query->whereNull('parent_id');
                }
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }

    // Filament Helpers
    public function getTypeColor(): string
    {
        return match ($this->type) {
            TeamType::Enterprise => Color::Purple[500],
            TeamType::Organisation => Color::Blue[500],
            TeamType::Division => Color::Green[500],
            TeamType::Department => Color::Orange[500],
            TeamType::Project => Color::Gray[500],
        };
    }

    public function getTypeLabel(): string
    {
        return $this->type->getLabel();
    }

    public function getStatusColor(): string
    {
        return $this->latestStatus()?->color ?? Color::Gray[400];
    }

    public function getAvatarUrl(): ?string
    {
        return $this->executive?->avatar_url;
    }

    public function getFullSlugPath(): string
    {
        return $this->ancestorsAndSelf()
            ->orderBy('depth')
            ->pluck('slug')
            ->filter()
            ->implode('/');
    }

    public function getPublicUrl(): string
    {
        return route('teams.show', ['team' => $this->ulid]);
    }

    public function getSlugUrl(): string
    {
        return route('teams.show-by-slug', ['slug' => $this->slug]);
    }

    // Scopes
    public function scopeOfType($query, TeamType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRootLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeByUlid($query, string $ulid)
    {
        return $query->where('ulid', $ulid);
    }

    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeInGraph($query, Team $rootTeam)
    {
        return $query->where(function ($query) use ($rootTeam) {
            $query->whereHas('ancestors', function ($ancestorQuery) use ($rootTeam) {
                $ancestorQuery->where('id', $rootTeam->id);
            })->orWhere('id', $rootTeam->id);
        });
    }
}

```

#### 3.1.2. Updated Migration with Unique Constraint

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->string('name');
            $table->string('slug', 150)->unique();
            $table->text('description')->nullable();
            $table->string('type');
            $table->foreignId('parent_id')->nullable()->constrained('teams');
            $table->foreignId('executive_id')->constrained('users');
            $table->foreignId('deputy_id')->nullable()->constrained('users');
            $table->string('state')->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'parent_id']);
            $table->index('state');
            $table->index('ulid');
            $table->index('slug');
            $table->index(['name', 'parent_id']);
        });

        // Add a partial unique constraint for root teams (where parent_id is null)
        DB::statement('CREATE UNIQUE INDEX teams_name_root_unique ON teams (name) WHERE parent_id IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};

```

#### 3.1.3. Custom Validation Rule for Graph Uniqueness

```php
<?php

namespace App\Rules;

use App\Models\Team;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueNameInGraph implements ValidationRule
{
    public function __construct(
        private ?Team $team = null,
        private ?int $parentId = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        $query = Team::where('name', $value);

        // Exclude current team if updating
        if ($this->team) {
            $query->where('id', '!=', $this->team->id);
        }

        // Determine the root of the graph
        $rootId = null;
        if ($this->parentId) {
            $parent = Team::find($this->parentId);
            $rootId = $parent?->getGraphRoot()?->id;
        } elseif ($this->team && $this->team->parent_id) {
            $rootId = $this->team->getGraphRoot()?->id;
        }

        // Apply graph constraint
        $query->where(function ($query) use ($rootId) {
            if ($rootId) {
                // Check within the same graph
                $query->whereHas('ancestors', function ($ancestorQuery) use ($rootId) {
                    $ancestorQuery->where('id', $rootId);
                })->orWhere('id', $rootId);
            } else {
                // Check only root teams
                $query->whereNull('parent_id');
            }
        });

        if ($query->exists()) {
            $fail('The :attribute must be unique within the team hierarchy graph.');
        }
    }
}

```

#### 3.1.4. Updated Filament Resource

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Data\TeamData;
use App\Enums\TeamType;
use App\Filament\Resources\TeamResource\Pages;
use App\Models\Team;
use App\Rules\UniqueNameInGraph;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic Information')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->rules([
                            fn(Forms\Get $get, ?Model $record) => new UniqueNameInGraph(
                                $record,
                                $get('parent_id')
                            )
                        ])
                        ->validationMessages([
                            'unique_name_in_graph' => 'This name is already used within the team hierarchy.',
                        ])
                        ->helperText('Name must be unique within the team hierarchy graph'),

                    Forms\Components\TextInput::make('slug')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Auto-generated from name and parent name')
                        ->visibleOn('edit'),

                    Forms\Components\Textarea::make('description')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Hierarchy')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->options(TeamType::class)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state === TeamType::Enterprise->value) {
                                $set('parent_id', null);
                            }
                        }),

                    Forms\Components\Select::make('parent_id')
                        ->relationship(
                            'parent',
                            'name',
                            fn(Builder $query, Forms\Get $get) => $query
                                ->when($get('type'), function ($query, $type) {
                                    $allowedParentTypes = TeamType::from($type)
                                        ->getAllowedParentTypes();
                                    if (!empty($allowedParentTypes)) {
                                        $query->whereIn('type', $allowedParentTypes);
                                    }
                                })
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->hidden(fn(Forms\Get $get) =>
                            $get('type') === TeamType::Enterprise->value
                        )
                        ->helperText(function (Forms\Get $get) {
                            if (!$get('type')) return null;

                            $allowedTypes = TeamType::from($get('type'))
                                ->getAllowedParentTypes();

                            return empty($allowedTypes)
                                ? 'This type cannot have a parent'
                                : 'Allowed parent types: ' .
                                  implode(', ', array_map(
                                      fn($type) => $type->getLabel(),
                                      $allowedTypes
                                  ));
                        }),
                ]),

            Forms\Components\Section::make('Leadership')
                ->schema([
                    Forms\Components\Select::make('executive_id')
                        ->relationship('executive', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),

                    Forms\Components\Select::make('deputy_id')
                        ->relationship('deputy', 'name')
                        ->searchable()
                        ->preload()
                        ->different('executive_id')
                        ->helperText('Deputy must be different from Executive'),
                ]),

            Forms\Components\Section::make('System Information')
                ->schema([
                    Forms\Components\TextInput::make('ulid')
                        ->label('ULID')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Auto-generated unique identifier'),

                    Forms\Components\Placeholder::make('graph_info')
                        ->label('Graph Information')
                        ->content(function (?Model $record) {
                            if (!$record) return 'New team';

                            $root = $record->getGraphRoot();
                            $teamsInGraph = $record->getGraphTeams()->count();

                            return $root
                                ? "Part of '{$root->name}' graph ({$teamsInGraph} teams)"
                                : "Root team ({$teamsInGraph} teams in graph)";
                        }),
                ])
                ->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Team $record) => $record->slug),

                Tables\Columns\BadgeColumn::make('type')
                    ->color(fn(Team $record) => $record->type->getColor())
                    ->formatStateUsing(fn($state) => $state->getLabel()),

                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent')
                    ->sortable()
                    ->placeholder('Root Level')
                    ->description(function (Team $record) {
                        $root = $record->getGraphRoot();
                        return $root && $root->id !== $record->id
                            ? "Graph: {$root->name}"
                            : ($record->parent_id ? null : 'Graph Root');
                    }),

                Tables\Columns\TextColumn::make('executive.name')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('deputy.name')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Not assigned'),

                Tables\Columns\BadgeColumn::make('state')
                    ->color(fn(Team $record) => $record->state->color())
                    ->formatStateUsing(fn($state) => $state->label()),

                Tables\Columns\TextColumn::make('graph_size')
                    ->label('Graph Size')
                    ->getStateUsing(fn(Team $record) => $record->getGraphTeams()->count())
                    ->suffix(' teams')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(TeamType::class),

                Tables\Filters\SelectFilter::make('state')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'archived' => 'Archived',
                    ]),

                Tables\Filters\Filter::make('root_only')
                    ->label('Root Teams Only')
                    ->query(fn(Builder $query) => $query->whereNull('parent_id'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_graph')
                    ->label('View Graph')
                    ->icon('heroicon-o-share')
                    ->url(fn(Team $record) => route('teams.hierarchy', $record->ulid))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->groups([
                Tables\Grouping\Group::make('parent.name')
                    ->label('Parent Team')
                    ->collapsible(),

                Tables\Grouping\Group::make('type')
                    ->label('Team Type')
                    ->collapsible(),
            ]);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['parent', 'executive', 'deputy']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'ulid', 'description'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $root = $record->getGraphRoot();

        return [
            'Type' => $record->type->getLabel(),
            'Parent' => $record->parent?->name ?? 'Root Level',
            'Graph' => $root?->name ?? 'Independent',
            'Executive' => $record->executive->name,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record:ulid}/edit'),
            'view' => Pages\ViewTeam::route('/{record:ulid}'),
        ];
    }
}

```

#### 3.1.5. Updated Factory

```php
<?php

namespace Database\Factories;

use App\Enums\TeamType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Symfony\Component\Uid\Ulid;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'ulid' => (string) new Ulid(),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(TeamType::cases()),
            'executive_id' => User::factory(),
            'deputy_id' => $this->faker->boolean(70) ? User::factory() : null,
        ];
    }

    public function withUniqueNameInGraph(?Team $parent = null): static
    {
        return $this->state(function (array $attributes) use ($parent) {
            $baseName = $this->faker->company();
            $name = $baseName;
            $counter = 1;

            // Ensure uniqueness within the graph
            while (!$this->isNameUniqueInGraph($name, $parent)) {
                $name = "{$baseName} {$counter}";
                $counter++;
            }

            return ['name' => $name];
        });
    }

    private function isNameUniqueInGraph(string $name, ?Team $parent): bool
    {
        $query = Team::where('name', $name);

        if ($parent) {
            $rootId = $parent->getGraphRoot()?->id;
            if ($rootId) {
                $query->where(function ($query) use ($rootId) {
                    $query->whereHas('ancestors', function ($ancestorQuery) use ($rootId) {
                        $ancestorQuery->where('id', $rootId);
                    })->orWhere('id', $rootId);
                });
            }
        } else {
            $query->whereNull('parent_id');
        }

        return !$query->exists();
    }

    public function enterprise(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => TeamType::Enterprise,
            'parent_id' => null,
        ]);
    }

    public function organisation(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => TeamType::Organisation,
        ]);
    }

    public function division(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => TeamType::Division,
        ]);
    }

    public function department(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => TeamType::Department,
        ]);
    }

    public function project(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => TeamType::Project,
        ]);
    }
}

```

### 3.2. Summary

Key Changes Made:

1. Unique Names in Graph: Added validation to ensure names are unique within each hierarchy graph
2. Slug Generation: Slugs now generate from `parent_name + team_name`
3. Database Constraints: Added partial unique index for root team names
4. Custom Validation Rule: UniqueNameInGraph rule for Filament forms
5. Graph Helpers: Methods to identify graph root and all teams in a graph
6. Cascade Slug Updates: When parent names change, descendant slugs update automatically
7. Enhanced UI: Filament resource shows graph information and validation messages
8. Factory Updates: Ensures unique names when creating test data

The implementation now enforces that team names must be unique within their respective hierarchy graphs, and slugs are properly generated from the combination of parent and team names.

## 4. Prompt

update this design:

- livewire v4
- filament v5
- stancl/tenancy
  - "Enterprise" is the "tenant"
  - single db
  - tenancy partition by sub-domain

### 4.1. Response

Based on the search results, I can see that:

- **Filament v5** is currently in beta (v5.0.0-beta7 as of Dec 2025), while v4 is the latest stable
- **Livewire v4** is also in beta
- **stancl/tenancy** documentation is available for single-database tenancy

I'll update the design for these versions. Since Filament v5 is in beta and the user requested it specifically, I'll adapt the code accordingly:

#### 4.1.1. Updated Migration with Tenant Support

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->string('name');
            $table->string('slug', 150)->unique();
            $table->string('subdomain')->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('type');
            $table->foreignId('parent_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->foreignId('executive_id')->constrained('users');
            $table->foreignId('deputy_id')->nullable()->constrained('users');
            $table->string('state')->default('draft');
            $table->json('data')->nullable(); // For tenant-specific configuration
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'parent_id']);
            $table->index('state');
            $table->index('subdomain');
        });

        // Partial unique index for root teams
        DB::statement(
            'CREATE UNIQUE INDEX teams_name_root_unique ON teams (name) WHERE parent_id IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};

```

#### 4.1.2. Tenant Model (Enterprise)

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\TenantCollection;

class Tenant extends Team implements TenantContract
{
    use HasDomains;

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Tenant $tenant) {
            $tenant->type = TeamType::Enterprise;
            $tenant->parent_id = null;
        });
    }

    public function getTenantKeyName(): string
    {
        return 'ulid';
    }

    public function getTenantKey(): string
    {
        return $this->ulid;
    }

    public function newCollection(array $models = []): TenantCollection
    {
        return new TenantCollection($models);
    }

    public function getIntKey(): int
    {
        return $this->id;
    }

    public function run(callable $callback): mixed
    {
        $originalTenant = tenant();

        tenancy()->initialize($this);

        try {
            return $callback($this);
        } finally {
            if ($originalTenant) {
                tenancy()->initialize($originalTenant);
            } else {
                tenancy()->end();
            }
        }
    }
}

```

#### 4.1.3. Updated Base Team Model with Single-Database Tenancy

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamType;
use App\States\TeamState;
use App\ValueObjects\TeamHierarchy;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\WithData;
use Spatie\ModelStates\HasStates;
use Spatie\ModelStatus\HasStatuses;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;
use Symfony\Component\Uid\Ulid;
use Tightenco\Parental\HasChildren;

class Team extends Model
{
    use BelongsToTenant,
        HasChildren,
        HasRecursiveRelationships,
        HasStates,
        HasStatuses,
        HasSlug,
        SoftDeletes,
        WithData;

    protected $fillable = [
        'name',
        'slug',
        'ulid',
        'subdomain',
        'description',
        'type',
        'parent_id',
        'executive_id',
        'deputy_id',
        'data',
    ];

    protected $casts = [
        'type' => TeamType::class,
        'state' => TeamState::class,
        'data' => 'array',
    ];

    protected array $childTypes = [
        'enterprise' => Teams\Enterprise::class,
        'organisation' => Teams\Organisation::class,
        'division' => Teams\Division::class,
        'department' => Teams\Department::class,
        'project' => Teams\Project::class,
    ];

    public function getChildTypes(): array
    {
        return $this->childTypes;
    }

    protected static function booted(): void
    {
        static::creating(function (Team $team) {
            if (!$team->ulid) {
                $team->ulid = (string) new Ulid();
            }
            $team->validateUniqueNameInGraph();
            $team->validateHierarchyRules();
        });

        static::updating(function (Team $team) {
            if ($team->isDirty('name') || $team->isDirty('parent_id')) {
                $team->validateUniqueNameInGraph();
            }
            $team->validateHierarchyRules();
            $team->validateExecutiveDeputyConstraint();
        });

        static::saved(function (Team $team) {
            if ($team->isDirty('name')) {
                $team->updateDescendantSlugs();
            }
        });
    }

    // Tenant Relationship - Override BelongsToTenant for self-referential
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function getTenantIdColumn(): string
    {
        return 'tenant_id';
    }

    // Get the Enterprise (root) for this team
    public function enterprise(): ?Tenant
    {
        if ($this->type === TeamType::Enterprise) {
            return $this instanceof Tenant ? $this : Tenant::find($this->id);
        }

        return $this->ancestors()
            ->where('type', TeamType::Enterprise)
            ->first();
    }

    // Slug Configuration
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('generateSlugSource')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(150)
            ->usingSeparator('-')
            ->usingLanguage('en');
    }

    public function generateSlugSource(): string
    {
        $parentName = $this->parent?->name ?? '';

        return $parentName
            ? "{$parentName} {$this->name}"
            : $this->name;
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return match ($field) {
            'slug' => $this->where('slug', $value)->first(),
            'ulid' => $this->where('ulid', $value)->first(),
            default => parent::resolveRouteBinding($value, $field),
        };
    }

    // Relationships
    public function executive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executive_id');
    }

    public function deputy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deputy_id');
    }

    // Validation Methods
    protected function validateUniqueNameInGraph(): void
    {
        $rootId = $this->getRootId();

        $query = static::query()
            ->where('name', $this->name)
            ->where(function ($query) use ($rootId) {
                if ($rootId) {
                    $query->whereHas('ancestors', function ($ancestorQuery) use ($rootId) {
                        $ancestorQuery->where('id', $rootId);
                    })->orWhere('id', $rootId);
                } else {
                    $query->whereNull('parent_id');
                }
            });

        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => ['The name must be unique within the team hierarchy graph.']
            ]);
        }
    }

    protected function getRootId(): ?int
    {
        if (!$this->parent_id) {
            return null;
        }

        $root = $this->ancestors()->whereNull('parent_id')->first();
        return $root?->id;
    }

    protected function validateHierarchyRules(): void
    {
        $hierarchy = new TeamHierarchy($this);
        $hierarchy->validate();
    }

    protected function validateExecutiveDeputyConstraint(): void
    {
        if ($this->executive_id && $this->deputy_id &&
            $this->executive_id === $this->deputy_id) {
            throw new \InvalidArgumentException(
                'Executive and Deputy cannot be the same person'
            );
        }
    }

    protected function updateDescendantSlugs(): void
    {
        $this->descendants()->each(function (Team $descendant) {
            $descendant->generateSlug();
            $descendant->saveQuietly();
        });
    }

    // Filament Helpers
    public function getTypeColor(): string
    {
        return match ($this->type) {
            TeamType::Enterprise => Color::Purple[500],
            TeamType::Organisation => Color::Blue[500],
            TeamType::Division => Color::Green[500],
            TeamType::Department => Color::Orange[500],
            TeamType::Project => Color::Gray[500],
        };
    }

    public function getTypeLabel(): string
    {
        return $this->type->getLabel();
    }

    public function getStatusColor(): string
    {
        return $this->latestStatus()?->color ?? Color::Gray[400];
    }

    public function getAvatarUrl(): ?string
    {
        return $this->executive?->avatar_url;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->getAvatarUrl();
    }

    // Scopes
    public function scopeOfType($query, TeamType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRootLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeByUlid($query, string $ulid)
    {
        return $query->where('ulid', $ulid);
    }

    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }
}

```

#### 4.1.4. Enterprise Model (Tenant Implementation)

```php
<?php

declare(strict_types=1);

namespace App\Models\Teams;

use App\Enums\TeamType;
use App\Models\Team;
use App\Models\Tenant;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\TenantCollection;
use Tightenco\Parental\HasParent;

class Enterprise extends Team implements TenantContract
{
    use HasDomains, HasParent;

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Enterprise $enterprise) {
            $enterprise->type = TeamType::Enterprise;
            $enterprise->parent_id = null;
        });
    }

    public function canHaveParent(): bool
    {
        return false;
    }

    // Tenant Contract Implementation
    public function getTenantKeyName(): string
    {
        return 'ulid';
    }

    public function getTenantKey(): string
    {
        return $this->ulid;
    }

    public function newCollection(array $models = []): TenantCollection
    {
        return new TenantCollection($models);
    }

    public function getIntKey(): int
    {
        return $this->id;
    }

    public function run(callable $callback): mixed
    {
        $originalTenant = tenant();

        tenancy()->initialize($this);

        try {
            return $callback($this);
        } finally {
            if ($originalTenant) {
                tenancy()->initialize($originalTenant);
            } else {
                tenancy()->end();
            }
        }
    }

    // Subdomain accessor
    public function getSubdomainAttribute(): ?string
    {
        return $this->domains()->first()?->domain;
    }
}

```

#### 4.1.5. Tenancy Configuration

```php
<?php

// config/tenancy.php

declare(strict_types=1);

return [
    'tenant_model' => \App\Models\Teams\Enterprise::class,

    'id_generator' => null,

    'domain_model' => \Stancl\Tenancy\Database\Models\Domain::class,

    /**
     * Single database tenancy configuration
     */
    'database' => [
        'managers' => [],
        'template_tenant_connection' => null,
    ],

    /**
     * Bootstrappers are executed when tenancy is initialized.
     * We disable DatabaseTenancyBootstrapper for single-db tenancy.
     */
    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
        // Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class, // Disabled
    ],

    /**
     * Features
     */
    'features' => [
        // Stancl\Tenancy\Features\TenantConfig::class,
        // Stancl\Tenancy\Features\CrossDomainRedirect::class,
    ],

    /**
     * Central domains (non-tenant domains)
     */
    'central_domains' => [
        env('APP_CENTRAL_DOMAIN', 'localhost'),
        '127.0.0.1',
    ],

    /**
     * Identification middleware
     */
    'identification' => [
        'middleware' => \Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain::class,
    ],
];

```

#### 4.1.6. Tenancy Service Provider

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Teams\Enterprise;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenancyInitialized;
use Stancl\Tenancy\Listeners\BootstrapTenancy;
use Stancl\Tenancy\Listeners\RevertToCentralContext;

class TenancyServiceProvider extends ServiceProvider
{
    public array $singletons = [
        \Stancl\Tenancy\Contracts\Tenant::class => Enterprise::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureEvents();
    }

    protected function configureEvents(): void
    {
        // Initialize tenancy when tenant is identified
        Event::listen(TenancyInitialized::class, BootstrapTenancy::class);

        // When tenant is created, create domain
        Event::listen(TenantCreated::class, function (TenantCreated $event) {
            $tenant = $event->tenant;

            if ($tenant->subdomain) {
                $tenant->domains()->create([
                    'domain' => $tenant->subdomain,
                ]);
            }
        });
    }
}

```

#### 4.1.7. Routes Configuration

```php
<?php

// routes/tenant.php

declare(strict_types=1);

use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\TeamController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyBySubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('tenant.dashboard');

    Route::prefix('teams')->name('teams.')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('index');
        Route::get('/{team:ulid}', [TeamController::class, 'show'])->name('show');
        Route::get('/{team:ulid}/hierarchy', [TeamController::class, 'hierarchy'])->name('hierarchy');
    });
});

```

```php
<?php

// routes/web.php (Central routes)

declare(strict_types=1);

use App\Http\Controllers\Central\EnterpriseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    Route::prefix('enterprises')->name('enterprises.')->group(function () {
        Route::get('/', [EnterpriseController::class, 'index'])->name('index');
        Route::post('/', [EnterpriseController::class, 'store'])->name('store');
    });
});

```

#### 4.1.8. Filament Panel Provider (Tenant Panel)

```php
<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Models\Teams\Enterprise;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class TenantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tenant')
            ->path('admin')
            ->tenant(Enterprise::class, slugAttribute: 'ulid')
            ->tenantRoutePrefix('tenant')
            ->tenantMenu(fn () => tenant() !== null)
            ->tenantMenuItems([
                MenuItem::make()
                    ->label(fn () => tenant()?->name ?? 'Select Tenant')
                    ->icon('heroicon-o-building-office-2')
                    ->url(fn () => route('tenant.dashboard')),
            ])
            ->colors([
                'primary' => Color::Indigo,
                'enterprise' => Color::Purple,
                'organisation' => Color::Blue,
                'division' => Color::Green,
                'department' => Color::Orange,
                'project' => Color::Gray,
            ])
            ->discoverResources(in: app_path('Filament/Tenant/Resources'), for: 'App\\Filament\\Tenant\\Resources')
            ->discoverPages(in: app_path('Filament/Tenant/Pages'), for: 'App\\Filament\\Tenant\\Pages')
            ->discoverWidgets(in: app_path('Filament/Tenant/Widgets'), for: 'App\\Filament\\Tenant\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                InitializeTenancyBySubdomain::class,
                PreventAccessFromCentralDomains::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

```

#### 4.1.9. Updated Filament Resource for Teams (Filament v5 Syntax)

```php
<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Enums\TeamType;
use App\Filament\Tenant\Resources\TeamResource\Pages;
use App\Models\Team;
use App\Rules\UniqueNameInGraph;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->rules([
                                fn (Forms\Get $get, ?Model $record) => new UniqueNameInGraph(
                                    $record,
                                    $get('parent_id')
                                )
                            ])
                            ->helperText('Name must be unique within the team hierarchy graph'),

                        Forms\Components\TextInput::make('slug')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Auto-generated from name and parent name')
                            ->visibleOn('edit'),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),

                Section::make('Hierarchy')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options(TeamType::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state === TeamType::Enterprise->value) {
                                    $set('parent_id', null);
                                }
                            }),

                        Forms\Components\Select::make('parent_id')
                            ->relationship(
                                'parent',
                                'name',
                                fn (Builder $query, Forms\Get $get) => $query
                                    ->when($get('type'), function ($query, $type) {
                                        $allowedParentTypes = TeamType::from($type)
                                            ->getAllowedParentTypes();
                                        if (!empty($allowedParentTypes)) {
                                            $query->whereIn('type', $allowedParentTypes);
                                        }
                                    })
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->hidden(fn (Forms\Get $get) =>
                                $get('type') === TeamType::Enterprise->value
                            ),
                    ]),

                Section::make('Leadership')
                    ->schema([
                        Forms\Components\Select::make('executive_id')
                            ->relationship('executive', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('deputy_id')
                            ->relationship('deputy', 'name')
                            ->searchable()
                            ->preload()
                            ->different('executive_id')
                            ->helperText('Deputy must be different from Executive'),
                    ]),

                Section::make('System Information')
                    ->schema([
                        Forms\Components\TextInput::make('ulid')
                            ->label('ULID')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->visibleOn('edit'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Team Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name'),
                                Infolists\Components\TextEntry::make('slug'),
                                Infolists\Components\TextEntry::make('type')
                                    ->badge()
                                    ->color(fn (Team $record) => $record->type->getColor()),
                                Infolists\Components\TextEntry::make('state')
                                    ->badge()
                                    ->color(fn (Team $record) => $record->state->color()),
                            ]),
                        Infolists\Components\TextEntry::make('description')
                            ->columnSpanFull(),
                    ]),

                Section::make('Leadership')
                    ->schema([
                        Infolists\Components\TextEntry::make('executive.name'),
                        Infolists\Components\TextEntry::make('deputy.name')
                            ->placeholder('Not assigned'),
                    ])
                    ->columns(2),

                Section::make('Hierarchy')
                    ->schema([
                        Infolists\Components\TextEntry::make('parent.name')
                            ->placeholder('Root Level'),
                        Infolists\Components\TextEntry::make('children_count')
                            ->label('Direct Children')
                            ->getStateUsing(fn (Team $record) => $record->children()->count()),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Team $record) => $record->slug),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (Team $record) => $record->type->getColor())
                    ->formatStateUsing(fn ($state) => $state->getLabel()),

                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent')
                    ->sortable()
                    ->placeholder('Root Level'),

                Tables\Columns\TextColumn::make('executive.name')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('state')
                    ->badge()
                    ->color(fn (Team $record) => $record->state->color())
                    ->formatStateUsing(fn ($state) => $state->label()),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(TeamType::class),

                Tables\Filters\SelectFilter::make('state')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'archived' => 'Archived',
                    ]),

                Tables\Filters\Filter::make('root_only')
                    ->label('Root Teams Only')
                    ->query(fn (Builder $query) => $query->whereNull('parent_id'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'view' => Pages\ViewTeam::route('/{record:ulid}'),
            'edit' => Pages\EditTeam::route('/{record:ulid}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'ulid', 'description'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Type' => $record->type->getLabel(),
            'Parent' => $record->parent?->name ?? 'Root Level',
        ];
    }
}

```

#### 4.1.10. Livewire v4 Team Hierarchy Component

```php
<?php
// resources/views/livewire/team-hierarchy.blade.php

declare(strict_types=1);

use App\Models\Team;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;

new #[Lazy] class extends Component
{
    public ?string $teamUlid = null;
    public bool $expanded = true;

    public function mount(?string $teamUlid = null): void
    {
        $this->teamUlid = $teamUlid;
    }

    #[Computed]
    public function team(): ?Team
    {
        if (!$this->teamUlid) {
            return null;
        }

        return Team::with(['children', 'executive', 'deputy'])
            ->where('ulid', $this->teamUlid)
            ->first();
    }

    #[Computed]
    public function hierarchyTree(): array
    {
        if (!$this->team) {
            return [];
        }

        return $this->buildTree($this->team);
    }

    protected function buildTree(Team $team): array
    {
        return [
            'id' => $team->id,
            'ulid' => $team->ulid,
            'name' => $team->name,
            'type' => $team->type->value,
            'typeLabel' => $team->type->getLabel(),
            'typeColor' => $team->type->getColor(),
            'state' => $team->state->label(),
            'stateColor' => $team->state->color(),
            'executive' => $team->executive?->name,
            'deputy' => $team->deputy?->name,
            'children' => $team->children->map(
                fn (Team $child) => $this->buildTree($child)
            )->toArray(),
        ];
    }

    public function toggleExpanded(): void
    {
        $this->expanded = !$this->expanded;
    }

    #[On('team-updated')]
    public function refreshHierarchy(): void
    {
        unset($this->team, $this->hierarchyTree);
    }

    public function placeholder(): string
    {
        return <<<'HTML'
        <div class="animate-pulse">
            <div class="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
            <div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
            <div class="h-4 bg-gray-200 rounded w-2/3"></div>
        </div>
        HTML;
    }
}; ?>

<div class="space-y-4">
    @if($this->team)
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold">Team Hierarchy</h3>
            <button
                wire:click="toggleExpanded"
                class="text-sm text-gray-500 hover:text-gray-700"
            >
                {{ $expanded ? 'Collapse All' : 'Expand All' }}
            </button>
        </div>

        <div class="border rounded-lg p-4">
            <x-team-node
                :node="$this->hierarchyTree"
                :expanded="$expanded"
            />
        </div>
    @else
        <p class="text-gray-500">No team selected</p>
    @endif
</div>

```

#### 4.1.11. Team Node Blade Component

```php
{{-- resources/views/components/team-node.blade.php --}}

@props(['node', 'level' => 0, 'expanded' => true])

<div class="relative" style="margin-left: {{ $level * 1.5 }}rem">
    @if($level > 0)
        <div class="absolute left-0 top-0 h-full w-px bg-gray-200" style="left: -0.75rem"></div>
        <div class="absolute w-3 h-px bg-gray-200" style="left: -0.75rem; top: 1rem"></div>
    @endif

    <div
        class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-colors"
        x-data="{ open: @js($expanded) }"
    >
        @if(count($node['children'] ?? []) > 0)
            <button
                @click="open = !open"
                class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-gray-600"
            >
                <svg
                    class="w-4 h-4 transition-transform"
                    :class="{ 'rotate-90': open }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        @else
            <div class="w-5"></div>
        @endif

        <div class="flex-1 flex items-center gap-2">
            <span
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                style="background-color: {{ $node['typeColor'] }}20; color: {{ $node['typeColor'] }}"
            >
                {{ $node['typeLabel'] }}
            </span>

            <a
                href="{{ route('teams.show', $node['ulid']) }}"
                class="font-medium text-gray-900 hover:text-indigo-600"
            >
                {{ $node['name'] }}
            </a>

            <span
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                style="background-color: {{ $node['stateColor'] }}20; color: {{ $node['stateColor'] }}"
            >
                {{ $node['state'] }}
            </span>
        </div>

        @if($node['executive'])
            <span class="text-sm text-gray-500">
                {{ $node['executive'] }}
            </span>
        @endif
    </div>

    @if(count($node['children'] ?? []) > 0)
        <div x-show="open" x-collapse>
            @foreach($node['children'] as $child)
                <x-team-node
                    :node="$child"
                    :level="$level + 1"
                    :expanded="$expanded"
                />
            @endforeach
        </div>
    @endif
</div>

```

#### 4.1.12. Updated Enums with Filament v5 Contracts

```php
<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TeamType: string implements HasLabel, HasColor, HasIcon
{
    case Enterprise = 'enterprise';
    case Organisation = 'organisation';
    case Division = 'division';
    case Department = 'department';
    case Project = 'project';

    public function getLabel(): string
    {
        return match ($this) {
            self::Enterprise => 'Enterprise',
            self::Organisation => 'Organisation',
            self::Division => 'Division',
            self::Department => 'Department',
            self::Project => 'Project',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Enterprise => 'purple',
            self::Organisation => 'blue',
            self::Division => 'green',
            self::Department => 'orange',
            self::Project => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Enterprise => 'heroicon-o-building-office-2',
            self::Organisation => 'heroicon-o-building-office',
            self::Organisation => 'heroicon-o-rectangle-group',
            self::Department => 'heroicon-o-user-group',
            self::Project => 'heroicon-o-clipboard-document-list',
        };
    }

    public function canBeRoot(): bool
    {
        return $this === self::Enterprise;
    }

    public function getAllowedParentTypes(): array
    {
        return match ($this) {
            self::Enterprise => [],
            self::Organisation => [self::Enterprise],
            self::Division => [self::Organisation, self::Division],
            self::Department => [self::Division, self::Department],
            self::Project => [
                self::Enterprise,
                self::Organisation,
                self::Division,
                self::Department,
            ],
        };
    }

    public function isTenant(): bool
    {
        return $this === self::Enterprise;
    }
}

```

#### 4.1.13. Bootstrap File Updates

```php
<?php

// bootstrap/app.php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->group('tenant', [
            InitializeTenancyBySubdomain::class,
            PreventAccessFromCentralDomains::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

```

### 4.2. Summary

#### 4.2.1. Key Updates Made:

1. **Livewire v4**: Used Volt single-file components with new attributes like `#[Lazy]`, `#[Computed]`, `#[On]`
2. **Filament v5**: Updated to use unified Schemas namespace, new component structure
3. **stancl/tenancy**:
   - Enterprise model implements `TenantContract`
   - Single-database tenancy with `BelongsToTenant` trait
   - Subdomain-based identification via middleware
   - Tenant panel configuration
4. **Central vs Tenant Routes**: Separated routing for central and tenant contexts

The Enterprise serves as the tenant, and all other team types belong to an Enterprise through the hierarchy. Tenancy is identified via subdomain (e.g., `acme.yourdomain.com`).

---
