<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserState;
use App\Enums\UserStatus;
use App\Models\Builders\UserBuilder;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Concerns\HasUlid;
use App\Models\Concerns\ProtectsKeyRoles;
use App\Observers\UserObserver;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Override;
use Spatie\LaravelMarkdown\MarkdownRenderer;
use Spatie\Permission\Traits\HasRoles;
use Stevebauman\Purify\Facades\Purify;

#[ObservedBy(UserObserver::class)]
final class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    // Must be before HasRoles to run before role detachment
    // @phpstan-ignore-next-line
    use ProtectsKeyRoles;
    use HasRoles;
    use HasTranslatableAttributes;
    use HasUlid;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /** @var array<int, string> */
    public array $translatable = [
        'bio',
    ];

    /** @var list<string> */
    protected $appends = [
        'bio_html',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'state',
        'status',
        'bio',
        'ulid',
        'tenant_id',
        'current_context_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(static fn (string $word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Determine if the user is protected from deletion.
     */
    public function isProtectable(): bool
    {
        $pivotTable = config('permission.table_names.model_has_roles');
        $rolesTable = config('permission.table_names.roles');
        $teamKey = config('permission.column_names.team_foreign_key');

        // Get all key roles held by this user, including their team context
        $userKeyRoles = $this
            ->getConnection()
            ->table($pivotTable)
            ->join($rolesTable, "{$pivotTable}.role_id", '=', "{$rolesTable}.id")
            ->where("{$pivotTable}.model_id", $this->getKey())
            ->where("{$pivotTable}.model_type", $this->getMorphClass())
            ->where("{$rolesTable}.is_key", true)
            ->select("{$rolesTable}.id as role_id", "{$pivotTable}.{$teamKey} as team_id")
            ->get();

        foreach ($userKeyRoles as $row) {
            // Count users in this specific (role, team) combination
            $count = $this
                ->getConnection()
                ->table($pivotTable)
                ->where('role_id', $row->role_id)
                ->where($teamKey, $row->team_id)
                ->count();

            if ($count <= 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the enterprise tenant this user belongs to.
     *
     * @psalm-return BelongsTo<Enterprise>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'tenant_id');
    }

    /**
     * Get the current organisational context of the user.
     *
     * @psalm-return BelongsTo<Organisation>
     */
    public function currentContext(): BelongsTo
    {
        return $this->belongsTo(Organisation::class, 'current_context_id');
    }

    /**
     * Get the enterprises this user belongs to (many-to-many).
     *
     * @psalm-return BelongsToMany<Enterprise>
     */
    public function enterprises(): BelongsToMany
    {
        return $this
            ->belongsToMany(Enterprise::class, 'user_enterprise', 'user_id', 'enterprise_id')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    /**
     * Get the organisations this user has access to.
     *
     * @psalm-return BelongsToMany<Organisation>
     */
    public function accessibleOrganisations(): BelongsToMany
    {
        return $this
            ->belongsToMany(Organisation::class, 'user_organisation_access', 'user_id', 'organisation_id')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    /**
     * Switch the user's current context to a specific organisation.
     */
    public function switchContext(Organisation $organisation): bool
    {
        if (! $this->accessibleOrganisations()->where('organisation_id', $organisation->id)->exists()) {
            return false;
        }

        return $this->update(['current_context_id' => $organisation->id]);
    }

    /**
     * Validate the user's current context and default if necessary.
     */
    public function validateContext(): void
    {
        if (
            ! $this->current_context_id
            || ! $this->accessibleOrganisations()->where('organisation_id', $this->current_context_id)->exists()
        ) {
            $firstOrg = $this->accessibleOrganisations()->first();
            if ($firstOrg) {
                $this->update(['current_context_id' => $firstOrg->id]);

                return;
            }

            $this->update(['current_context_id' => null]);
        }
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  Builder  $query
     */
    #[Override]
    public function newEloquentBuilder($query): UserBuilder
    {
        return new UserBuilder($query);
    }

    #[Override]
    protected static function booted(): void
    {
        // Deletion prevention is handled by ProtectsKeyRoles trait
        // which must run before HasRoles to check before role detachment
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return string[]
     *
     * @psalm-return array{email_verified_at: 'datetime', password: 'hashed', state: UserState::class, status: UserStatus::class, tenant_id: 'integer', current_context_id: 'integer', bio: 'array'}
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'state' => UserState::class,
            'status' => UserStatus::class,
            'tenant_id' => 'integer',
            'current_context_id' => 'integer',
            'bio' => 'array',
        ];
    }

    /**
     * Get the bio HTML attribute (rendered from markdown).
     */
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
