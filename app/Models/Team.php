<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\SchemaScopedModel;
use App\Enums\TeamStatus;
use App\Enums\TeamType;
use App\Models\Builders\TeamBuilder;
use App\Models\Concerns\HasCustomSchema;
use App\Models\Concerns\HasTeamHierarchy;
use App\Models\Concerns\HasTeamSearch;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Concerns\HasTranslatableSlug;
use App\Models\Concerns\HasUlid;
use App\Models\Concerns\ManagesTeamRoles;
use App\Models\Concerns\ProtectsKeyRoles;
use App\Observers\TeamObserver;
use App\States\Team\TeamState;
use App\Support\Html\TeamBioRenderer;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Laravel\Scout\Searchable;
use Override;
use Parental\HasChildren;
use Spatie\ModelStates\HasStates;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Sluggable\SlugOptions;

/**
 * @property string|null $parent_id
 * @property string|null $tenant_id
 * @property self|null $parent
 * @property TeamType $type
 * @property TeamState $state
 * @property TeamStatus $status
 * @property int<0, max> $lock_version
 * @property string $ulid
 *
 * @use HasFactory<Factory>
 */
#[ObservedBy(TeamObserver::class)]
class Team extends Model implements SchemaScopedModel
{
    use HasChildren;
    use HasCustomSchema;
    use HasFactory;
    use HasRoles;
    use HasStates;
    use HasTeamHierarchy;
    use HasTeamSearch;
    use HasTranslatableAttributes;
    use HasTranslatableSlug;
    use HasUlid;
    use ManagesTeamRoles;

    // Must be before HasRoles to run before role detachment
    use ProtectsKeyRoles;
    use Searchable;
    use SoftDeletes;

    /** @var array<int, string> */
    public array $translatable = [
        'name',
        'slug',
        'bio',
    ];

    /** @var list<string> */
    protected $fillable = [
        'type',
        'name',
        'slug',
        'state',
        'status',
        'bio',
        'ulid',
        'parent_id',
        'tenant_id',
        'move_approval_descendant_threshold',
        'move_approval_depth_change_threshold',
        'move_approval_require_cross_org',
        'bulk_operation_batch_size',
    ];

    /** @var array<string> */
    protected $guarded = [];

    /** @var array<string, class-string> */
    protected array $childTypes = [
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
     * @param  Builder  $query
     */
    #[Override]
    public function newEloquentBuilder($query): TeamBuilder
    {
        return new TeamBuilder($query);
    }

    /**
     * @psalm-return BelongsTo<self, self>
     */
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

    /**
     * Get the options for generating the slug.
     *
     * @psalm-return SlugOptions
     */
    #[Override]
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->allowDuplicateSlugs(); // Uniqueness is handled per-locale in HasTranslatableSlug trait
    }

    /**
     * Get the value used to index the model.
     */
    public function getScoutKey(): mixed
    {
        return $this->ulid;
    }

    /**
     * Get the key name used to index the model.
     */
    public function getScoutKeyName(): mixed
    {
        return 'ulid';
    }

    /**
     * Scout: Define the indexable data array.
     *
     * @return (mixed|string)[]
     *
     * @psalm-return array{id: mixed, ulid: string, name: mixed, slug: mixed, bio: mixed, type: string, status: string}
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'name' => $this->getTranslation('name', 'en'),
            'slug' => $this->slug,
            'bio' => $this->getTranslation('bio', 'en'),
            'type' => $this->type->value,
            'status' => $this->status->value,
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return string[]
     *
     * @psalm-return array{type: TeamType::class, state: TeamState::class, status: TeamStatus::class, name: 'array', slug: 'array', bio: 'array', lock_version: 'integer'}
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

    /**
     * Get the rendered HTML version of the team bio.
     *
     * Delegates to TeamBioRenderer for improved testability and separation of concerns.
     */
    protected function getBioHtmlAttribute(): ?string
    {
        // Get bio in current locale, with fallback to default locale
        // The third parameter (true) enables fallback to default locale
        $bio = $this->getTranslation('bio', app()->getLocale(), true);

        // If bio is still null/empty, try accessing it directly as an attribute
        // This handles cases where bio might be set as a string directly (Spatie should handle this, but this is a safety net)
        if (($bio === null || $bio === '') && $this->bio !== null && $this->bio !== '') {
            if (is_string($this->bio)) {
                $bio = $this->bio;
            } elseif (is_array($this->bio)) {
                // If bio is an array (from translatable cast), get the value for current locale
                $locale = app()->getLocale();
                $bio = $this->bio[$locale] ?? $this->bio['en'] ?? array_first($this->bio) ?? null;
            } else {
                $bio = (string) $this->bio;
            }
        }

        return resolve(TeamBioRenderer::class)->render($bio);
    }
}
