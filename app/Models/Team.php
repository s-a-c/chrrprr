<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamStatus;
use App\Enums\TeamType;
use App\Models\Builders\TeamBuilder;
use App\Models\Concerns\HasTeamHierarchy;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Concerns\HasTranslatableSlug;
use App\Models\Concerns\HasUlid;
use App\Models\Concerns\ManagesTeamRoles;
use App\States\Team\TeamState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
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
    use HasTeamHierarchy;
    use HasTranslatableAttributes;
    use HasTranslatableSlug;
    use HasUlid;
    use ManagesTeamRoles;
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
     * @return string[][]
     *
     * @psalm-return array{slug: array{source: 'name'}}
     */
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

    protected function getBioHtmlAttribute(): ?string
    {
        $bio = $this->getTranslation('bio', app()->getLocale());

        if ($bio === null || $bio === '') {
            return null;
        }

        $html = resolve(MarkdownRenderer::class)->toHtml($bio);

        return Purify::clean($html);
    }
}
