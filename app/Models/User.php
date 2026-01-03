<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\SchemaScopedModel;
use App\Enums\UserState;
use App\Enums\UserStatus;
use App\Models\Builders\UserBuilder;
use App\Models\Concerns\HasCustomSchema;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Concerns\HasTranslatableSlug;
use App\Models\Concerns\HasUlid;
use App\Models\Concerns\HasUserSearch;
use App\Models\Concerns\ManagesUserContext;
use App\Models\Concerns\ProtectsKeyRoles;
use App\Observers\UserObserver;
use App\Presenters\UserPresenter;
use App\Services\UserProtectionService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Scout\Searchable;
use Override;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Sluggable\SlugOptions;

#[ObservedBy(UserObserver::class)]
/**
 * @mago-expect All methods are necessary: relationship methods, required interface methods (Searchable, HasTranslatableSlug),
 * and model lifecycle methods. Methods have been organized into traits where possible (HasUserSearch).
 */
final class User extends Authenticatable implements MustVerifyEmail, SchemaScopedModel
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    // Must be before HasRoles to run before role detachment
    // @phpstan-ignore-next-line
    use ProtectsKeyRoles;
    use HasCustomSchema;
    use HasRoles;
    use HasTranslatableAttributes;
    use HasTranslatableSlug;
    use HasUlid;
    use HasUserSearch;
    use ManagesUserContext;
    use Notifiable;
    use Searchable;
    use TwoFactorAuthenticatable;

    /** @var array<int, string> */
    public array $translatable = [
        'bio',
        'slug',
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
        'slug',
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
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'bio_html',
    ];

    /**
     * Determine if the user is protected from deletion.
     *
     * Delegates to UserProtectionService for improved testability and separation of concerns.
     */
    public function isProtectable(): bool
    {
        return resolve(UserProtectionService::class)->isProtectable($this);
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
     * Get the user's initials.
     */
    public function initials(): string
    {
        return resolve(UserPresenter::class)->initials($this);
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
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'name' => $this->name,
            'email' => $this->email,
            'bio' => $this->getTranslation('bio', 'en'),
            'status' => $this->status->value,
        ];
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \Illuminate\Auth\Notifications\ResetPassword($token));
    }

    #[Override]
    protected static function booted(): void
    {
        // Deletion prevention is handled by ProtectsKeyRoles trait
        // which must run before HasRoles to check before role detachment
    }

    /**
     * Get the rendered HTML version of the user bio.
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
                $bio = $this->bio[$locale] ?? $this->bio['en'] ?? $this->bio[array_key_first($this->bio)] ?? null;
            } else {
                $bio = (string) $this->bio;
            }
        }

        return resolve(\App\Support\Html\TeamBioRenderer::class)->render($bio);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return string[]
     *
     * @psalm-return array{email_verified_at: 'datetime', password: 'hashed', state: UserState::class, status: UserStatus::class, tenant_id: 'integer', current_context_id: 'integer', bio: 'array', slug: 'array'}
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
            'slug' => 'array',
        ];
    }
}
