<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamStatus;
use App\Enums\TeamType;
use App\Exceptions\OptimisticLockingException;
use App\Models\Builders\TeamBuilder;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Concerns\HasTranslatableSlug;
use App\Models\Concerns\HasUlid;
use App\States\Team\TeamState;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Override;
use Parental\HasChildren;
use Spatie\LaravelMarkdown\MarkdownRenderer;
use Spatie\ModelStates\HasStates;
use Stevebauman\Purify\Facades\Purify;

/**
 * @property self|null $parent
 * @property TeamType $type
 * @property TeamState $state
 * @property TeamStatus $status
 * @property int<0, max> $lock_version
 */
class Team extends Model
{
    use HasChildren;
    use HasFactory;
    use HasStates;
    use HasTranslatableAttributes;
    use HasTranslatableSlug;
    use HasUlid;
    use SoftDeletes;

    /** @var array<int, string> */
    public $translatable = [
        'name',
        'slug',
        'bio',
    ];

    /** @var list<string> */
    protected $fillable = ['type', 'name', 'slug', 'state', 'status', 'bio', 'ulid', 'parent_id', 'tenant_id', 'move_approval_descendant_threshold', 'move_approval_depth_change_threshold', 'move_approval_require_cross_org', 'bulk_operation_batch_size'];

    /** @var array<string> */
    protected $guarded = [];

    /** @var array<string, class-string> */
    protected $childTypes = [
        'enterprise' => Enterprise::class,
        'organisation' => Organisation::class,
        'division' => Division::class,
        'department' => Department::class,
        'project' => Project::class,
    ];

    /** @var list<string> */
    protected $appends = [
        'bio_html',
    ];

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return TeamBuilder
     */
    public function newEloquentBuilder($query): TeamBuilder
    {
        return new TeamBuilder($query);
    }

    public function updateDescendantTenants(string $newTenantId): void
    {
        foreach ($this->children()->withoutGlobalScopes()->get() as $child) {
            $child->tenant_id = $newTenantId;
            $child->save(); // Triggers updated recursively
        }
    }

    public function validateHierarchy(): void
    {
        // Enterprise must not have a parent
        if ($this->type === TeamType::ENTERPRISE) {
            if ($this->parent_id !== null) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Enterprises cannot have a parent team.'],
                ]);
            }

            return;
        }

        // All other types must have a parent
        if ($this->parent_id === null) {
            throw ValidationException::withMessages([
                'parent_id' => ['This team type requires a parent team.'],
            ]);
        }

        $parent = $this->parent;
        if (! $parent) {
            // Use withoutGlobalScopes to avoid STI type constraints from Parental
            // We manually ensure it's not deleted.
            $parent = self::query()
                ->withoutGlobalScopes()
                ->where('id', $this->parent_id)
                ->whereNull('deleted_at')
                ->first();
        }

        if (! $parent) {
            // Parent not found?
            return;
        }

        $validParentType = match ($this->type) {
            TeamType::ORGANISATION => TeamType::ENTERPRISE,
            TeamType::DIVISION => TeamType::ORGANISATION,
            TeamType::DEPARTMENT => TeamType::DIVISION,
            TeamType::PROJECT => TeamType::DEPARTMENT,
            default => null,
        };

        if ($validParentType && $parent->type !== $validParentType) {
            throw ValidationException::withMessages([
                'parent_id' => [
                    "{$this->type->value} must belong to a {$validParentType->value}, but belongs to {$parent->type->value}.",
                ],
            ]);
        }

        // Cycle prevention
        if ($this->id) {
            if (((int) $this->parent_id) === ((int) $this->id)) {
                throw ValidationException::withMessages([
                    'parent_id' => ['A team cannot be its own parent.'],
                ]);
            }

            if ($parent->isDescendantOf($this)) {
                throw ValidationException::withMessages([
                    'parent_id' => ['A team cannot be moved into its own descendant (would create a cycle).'],
                ]);
            }
        }

        // Depth validation (hard limit 10)
        if ($parent->getDepth() >= 10) {
            throw ValidationException::withMessages([
                'parent_id' => ['Team hierarchy depth cannot exceed 10 levels.'],
            ]);
        }
    }

    public function isDescendantOf(self $team): bool
    {
        $currentParentId = $this->parent_id;

        while ($currentParentId) {
            if (((int) $currentParentId) === ((int) $team->id)) {
                return true;
            }

            $currentParentId = DB::table('teams')->where('id', $currentParentId)->value('parent_id');
        }

        return false;
    }

    /**
     * @psalm-return int<1, max>
     */
    public function getDepth(): int
    {
        $depth = 1;
        $currentParentId = $this->parent_id;

        while ($currentParentId) {
            $depth++;
            $currentParentId = DB::table('teams')->where('id', $currentParentId)->value('parent_id');
        }

        return $depth;
    }

    public function validateUniqueName(): void
    {
        // Check for siblings with same name (and same parent)
        // Use withoutGlobalScopes to avoid STI type constraints hiding siblings
        $query = self::query()
            ->withoutGlobalScopes()
            ->where('parent_id', $this->parent_id)
            ->where('type', $this->type)
            ->where('id', '!=', $this->id)
            ->whereNull('deleted_at');

        // Access raw name to avoid HasTranslatableAttributes returning partial string
        $nameRaw = $this->attributes['name'] ?? null;

        // Ensure we work with an array of translations
        $nameToCheck = $nameRaw;
        if (is_string($nameRaw)) {
            $decoded = json_decode($nameRaw, true);
            if (is_array($decoded)) {
                $nameToCheck = $decoded;
            }
        } else {
            // Fallback: use the cast property (always an array due to cast)
            $nameToCheck = $this->name;
        }

        if (is_array($nameToCheck)) {
            // Check if any provided locale name matches an existing sibling's name in that locale
            $query->where(static function ($q) use ($nameToCheck): void {
                foreach ($nameToCheck as $locale => $value) {
                    $q->orWhere("name->{$locale}", $value);
                }
            });
        } else {
            // Non-array (simple string) match
            $query->where('name', $nameToCheck ?? $this->name);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => ['The team name has already been taken within this scope.'],
            ]);
        }
    }

    /**
     * Validate executive/deputy constraints.
     *
     * Validates that:
     * - Executive and Deputy cannot be the same person
     * - Executive/Deputy must be from the same Enterprise as the team
     */
    public function validateExecutiveDeputyConstraints(User $user, string $role): void
    {
        // Validate same enterprise constraint
        if ($this->tenant_id && $user->tenant_id && $this->tenant_id !== $user->tenant_id) {
            throw ValidationException::withMessages([
                $role => ['The user must belong to the same enterprise as the team.'],
            ]);
        }

        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($this->id);

        try {
            // Validate executive and deputy cannot be the same person
            if ($role === 'executive') {
                // Check if user is already a deputy (only if deputy role exists)
                try {
                    $deputyIds = User::query()->role('deputy')->pluck('id');
                    if ($deputyIds->contains($user->id)) {
                        throw ValidationException::withMessages([
                            'executive' => ['A user cannot be both executive and deputy of the same team.'],
                        ]);
                    }
                } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist) {
                    // Role doesn't exist yet, so no deputies to check
                }
            } elseif ($role === 'deputy') {
                // Check if user is already the executive (only if executive role exists)
                try {
                    $executive = User::query()->role('executive')->first();
                    if ($executive && $executive->id === $user->id) {
                        throw ValidationException::withMessages([
                            'deputy' => ['A user cannot be both executive and deputy of the same team.'],
                        ]);
                    }
                } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist) {
                    // Role doesn't exist yet, so no executive to check
                }
            }
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }

    /**
     * Get the executive user for this team.
     */
    public function executive(): ?User
    {
        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($this->id);

        try {
            return User::query()->role('executive')->first();
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }

    public function assignExecutive(User $user): void
    {
        $this->validateExecutiveDeputyConstraints($user, 'executive');

        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($this->id);

        try {
            $existing = User::query()
                ->role('executive')
                ->where('id', '!=', $user->id)
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'executive' => ['This team already has an executive assigned.'],
                ]);
            }

            $user->assignRole('executive');
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }

    public function removeExecutive(): void
    {
        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($this->id);

        try {
            $executives = User::query()->role('executive')->get();
            foreach ($executives as $exec) {
                $exec->removeRole('executive');
            }
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }

    public function hasExecutive(): bool
    {
        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($this->id);

        try {
            return User::query()->role('executive')->exists();
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }

    public function assignDeputy(User $user): void
    {
        $this->validateExecutiveDeputyConstraints($user, 'deputy');

        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($this->id);

        try {
            $user->assignRole('deputy');
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }

    public function removeDeputy(User $user): void
    {
        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($this->id);

        try {
            $user->removeRole('deputy');
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }

    public function deputies(): Collection
    {
        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($this->id);

        try {
            return User::query()->role('deputy')->get();
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id')->withoutGlobalScopes();
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'tenant_id');
    }

    #[Override]
    /**
     * @return string[][]
     *
     * @psalm-return array{slug: array{source: 'name'}}
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
            ],
        ];
    }

    #[Override]
    protected static function booted(): void
    {
        $validationCallback = static function (Team $team): void {
            $team->validateHierarchy();
            $team->validateUniqueName();
        };

        self::creating(static function ($team): void {
            $team->validateHierarchy();
            $team->validateUniqueName();
        });

        self::updating(static function (Team $team) use ($validationCallback): void {
            $validationCallback($team);

            // Optimistic Locking
            $currentLockVersion = (int) $team->lock_version;

            // Check if the record in the database has a different lock_version
            $databaseLockVersion = (int) DB::table('teams')->where('id', $team->id)->value('lock_version');

            throw_if($databaseLockVersion !== $currentLockVersion, OptimisticLockingException::class);

            $team->lock_version = $currentLockVersion + 1;

            // Tenant Inheritance & Propagation
            if ($team->isDirty('parent_id')) {
                if ($team->parent_id) {
                    $parent = Team::query()->withoutGlobalScopes()->find($team->parent_id);
                    if ($parent) {
                        $newTenantId = $parent->type === TeamType::ENTERPRISE ? $parent->id : $parent->tenant_id;
                        $team->tenant_id = $newTenantId;
                    }
                } elseif ($team->type === TeamType::ENTERPRISE) {
                    // If parent_id becomes null, it must be an Enterprise (checked by validateHierarchy)
                    // and it becomes its own tenant.
                    $team->tenant_id = $team->id;
                }
            }
        });

        self::updated(static function (Team $team): void {
            if ($team->wasChanged('tenant_id')) {
                $team->updateDescendantTenants((string) $team->tenant_id);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'type' => TeamType::class,
            'state' => TeamState::class,
            'status' => TeamStatus::class,
            'name' => 'array',
            'slug' => 'array',
            'bio' => 'array',
            'lock_version' => 'integer',
        ];
    }

    protected function getBioHtmlAttribute(): ?string
    {
        $bio = $this->getTranslation('bio', app()->getLocale());

        if (empty($bio)) {
            return null;
        }

        $html = resolve(MarkdownRenderer::class)->toHtml($bio);

        return Purify::clean($html);
    }
}
