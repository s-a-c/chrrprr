<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\SchemaScopedModel;
use App\Enums\TeamStatus;
use App\Enums\TeamType;
use App\Models\Builders\TeamBuilder;
use App\Models\Concerns\HasCustomSchema;
use App\Models\Concerns\HasTeamHierarchy;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Concerns\HasTranslatableSlug;
use App\Models\Concerns\HasUlid;
use App\Models\Concerns\ManagesTeamRoles;
use App\States\Team\TeamState;
use App\Support\Html\TeamBioRenderer;
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
use Spatie\Sluggable\SlugOptions;

/**
 * @property self|null $parent
 * @property TeamType $type
 * @property TeamState $state
 * @property TeamStatus $status
 * @property int<0, max> $lock_version
 *
 * @use HasFactory<Factory>
 */
class Team extends Model implements SchemaScopedModel
{
    use HasChildren;
    use HasCustomSchema;
    use HasFactory;
    use HasStates;
    use HasTeamHierarchy;
    use HasTranslatableAttributes;
    use HasTranslatableSlug;
    use HasUlid;
    use ManagesTeamRoles;
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
     * @psalm-return \Illuminate\Database\Eloquent\Builder<TRelatedModel>
     */
    public function parent(): \Illuminate\Database\Eloquent\Builder
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
            ->saveSlugsTo('slug');
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
     * Typo-tolerant fuzzy search scope using pg_trgm.
     */
    public function scopeFuzzySearch($query, string $term)
    {
        // Extract English name from JSON for comparison
        return $query->whereRaw("(name->>'en') % ?", [$term])
            ->orderByRaw("similarity((name->>'en'), ?) DESC", [$term]);
    }

    /**
     * Full-text search scope using weighted search_vector.
     */
    public function scopeFullTextSearch($query, string $term)
    {
        return $query->whereRaw('search_vector @@ to_tsquery(?, ?)', ['english', $term])
            ->orderByRaw('ts_rank(search_vector, to_tsquery(?, ?)) DESC', ['english', $term]);
    }

    /**
     * Scout: Define the indexable data array.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'name' => $this->getTranslation('name', 'en'),
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
        $bio = $this->getTranslation('bio', app()->getLocale());

        return resolve(TeamBioRenderer::class)->render($bio, app()->getLocale());
    }
}
