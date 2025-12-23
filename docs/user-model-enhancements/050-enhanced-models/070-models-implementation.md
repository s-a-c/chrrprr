# Models Implementation

This guide covers the implementation of the enhanced User model and Team model hierarchy.

## Overview

Models use trait-based composition rather than base model inheritance, allowing flexible composition of behaviors while extending appropriate base classes (User extends Authenticatable, Team extends Model).

## Enhanced User Model

### Basic Structure

```php
<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Concerns\HasTranslatableSlug;
use App\Enums\UserState;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\ModelStates\HasStates;
use Spatie\ModelStatus\HasStatuses;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasUlid;
    use HasTranslatableAttributes;
    use HasTranslatableSlug;
    use BelongsToTenant;
    use HasStates;
    use HasStatuses;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'state',
        'status',
        'tenant_id',
        'current_organisation_id',
        'current_division_id',
        'current_department_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Translatable attributes.
     *
     * @var array<int, string>
     */
    public array $translatable = ['name', 'slug'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'state' => UserState::class,
            'status' => UserStatus::class,
            'slug' => 'array',
        ];
    }

    /**
     * Register state machine.
     */
    protected function registerStates(): void
    {
        // State machine configuration
        // See Spatie Model States documentation
    }

    /**
     * Get status options.
     *
     * @return array<int, string>
     */
    public function getStatusOptions(): array
    {
        return array_column(UserStatus::cases(), 'value');
    }

    /**
     * Get the user's tenant (Enterprise).
     */
    public function tenant()
    {
        return $this->belongsTo(Team::class, 'tenant_id')
            ->where('type', TeamType::Enterprise->value);
    }

    /**
     * Get organisations the user belongs to.
     */
    public function organisations()
    {
        return $this->belongsToMany(
            Team::class,
            'user_organisation_access',
            'user_id',
            'organisation_id'
        )->where('type', TeamType::Organisation->value);
    }

    /**
     * Get current organisation from context.
     */
    public function currentOrganisation()
    {
        return $this->belongsTo(Team::class, 'current_organisation_id')
            ->where('type', TeamType::Organisation->value);
    }

    /**
     * Set current organisation context.
     *
     * @param  \App\Models\Team  $organisation
     * @return $this
     */
    public function setCurrentOrganisation(Team $organisation): self
    {
        if ($organisation->type !== TeamType::Organisation) {
            throw new \InvalidArgumentException('Must be an Organisation');
        }

        $this->update([
            'current_organisation_id' => $organisation->id,
            'current_division_id' => null,
            'current_department_id' => null,
        ]);

        session(['context.organisation_id' => $organisation->id]);

        return $this;
    }
}
```

## Team Model Hierarchy

### Base Team Model

```php
<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Concerns\HasTranslatableSlug;
use App\Enums\TeamType;
use App\Enums\TeamState;
use App\Enums\TeamStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStates\HasStates;
use Spatie\ModelStatus\HasStatuses;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;
use Tighten\Parental\HasChildren;
use Tighten\Parental\HasParent;

class Team extends Model
{
    use HasUlid;
    use HasTranslatableAttributes;
    use HasTranslatableSlug;
    use BelongsToTenant;
    use HasRecursiveRelationships;
    use HasChildren;
    use HasStates;
    use HasStatuses;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'name',
        'description',
        'slug',
        'parent_id',
        'executive_id',
        'deputy_id',
        'tenant_id',
        'state',
        'status',
    ];

    /**
     * Translatable attributes.
     *
     * @var array<int, string>
     */
    public array $translatable = ['name', 'description', 'slug'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TeamType::class,
            'state' => TeamState::class,
            'status' => TeamStatus::class,
            'name' => 'array',
            'description' => 'array',
            'slug' => 'array',
        ];
    }

    /**
     * Get status options.
     *
     * @return array<int, string>
     */
    public function getStatusOptions(): array
    {
        return array_column(TeamStatus::cases(), 'value');
    }

    /**
     * Get the parent team.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get child teams.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get the executive user.
     */
    public function executive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executive_id');
    }

    /**
     * Get the deputy user.
     */
    public function deputy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deputy_id');
    }

    /**
     * Get the tenant (Enterprise).
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(self::class, 'tenant_id')
            ->where('type', TeamType::Enterprise->value);
    }

    /**
     * Check if this team is the root (Enterprise).
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null && $this->type === TeamType::Enterprise;
    }

    /**
     * Validate hierarchy constraints.
     *
     * @throws \InvalidArgumentException
     */
    public function validateHierarchy(): void
    {
        if ($this->parent_id === null && $this->type !== TeamType::Enterprise) {
            throw new \InvalidArgumentException('Only Enterprise can be root-level');
        }

        if ($this->parent_id !== null) {
            $parent = $this->parent;
            $teamType = TeamType::from($this->type);

            if (!$teamType->canHaveParent(TeamType::from($parent->type))) {
                throw new \InvalidArgumentException(
                    "A {$teamType->getLabel()} cannot have a {$parent->type->getLabel()} as parent"
                );
            }
        }
    }
}
```

### Enterprise Model (STI)

```php
<?php

namespace App\Models;

use Stancl\Tenancy\Contracts\Tenant;
use Tighten\Parental\HasParent;

class Enterprise extends Team
{
    use HasParent;

    protected static function booted(): void
    {
        static::addGlobalScope('type', function ($query) {
            $query->where('type', TeamType::Enterprise->value);
        });
    }

    /**
     * Implement Tenant contract.
     */
    public function getTenantKey(): string
    {
        return $this->ulid;
    }

    public function getTenantKeyName(): string
    {
        return 'ulid';
    }
}
```

### Organisation, Division, Department, Project Models

```php
<?php

namespace App\Models;

use Tighten\Parental\HasParent;

class Organisation extends Team
{
    use HasParent;

    protected static function booted(): void
    {
        static::addGlobalScope('type', function ($query) {
            $query->where('type', TeamType::Organisation->value);
        });
    }
}

// Similar for Division, Department, Project
```

## Next Steps

- Review [Database Setup](040-database-setup.md) for schema requirements
- Check [Traits Implementation](050-traits-implementation.md) for trait details
- See [Enums, States & Statuses](060-enums-states-statuses.md) for enum definitions
- Review [Tenancy Setup](080-tenancy-setup.md) for tenant configuration
