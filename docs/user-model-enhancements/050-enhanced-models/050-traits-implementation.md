# Traits Implementation

This guide covers the implementation of reusable traits for ULID, translatable attributes, and translatable slugs.

## Trait Architecture

All traits are located in `app/Models/Concerns/` and follow Laravel conventions:

- Use `boot{TraitName}()` methods for initialization
- Single responsibility principle
- Composable and reusable across different model types

## HasUlid Trait

### Purpose
Generates ULID on model creation and sets route key binding to use ULID instead of ID.

### Implementation

```php
<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUlid
{
    /**
     * Boot the trait.
     */
    public static function bootHasUlid(): void
    {
        static::creating(function (Model $model): void {
            if (empty($model->ulid)) {
                $model->ulid = (string) Str::ulid();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * Scope a query to find by ULID.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $ulid
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUlid($query, string $ulid)
    {
        return $query->where('ulid', $ulid);
    }
}
```

### Usage

```php
use App\Models\Concerns\HasUlid;

class User extends Authenticatable
{
    use HasUlid;

    // Model automatically gets ULID on creation
    // Route model binding uses 'ulid' instead of 'id'
}
```

### Testing

```php
use App\Models\User;
use Tests\TestCase;

it('generates ulid on creation', function () {
    $user = User::factory()->create();

    expect($user->ulid)
        ->toBeString()
        ->toHaveLength(26)
        ->toMatch('/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/');
});

it('uses ulid for route key', function () {
    $user = User::factory()->create();

    expect($user->getRouteKeyName())->toBe('ulid');
});

it('can find model by ulid scope', function () {
    $user = User::factory()->create();

    $found = User::byUlid($user->ulid)->first();

    expect($found->id)->toBe($user->id);
});
```

## HasTranslatableAttributes Trait

### Purpose
Wrapper for `spatie/laravel-translatable` that configures translatable attributes and provides helper methods.

### Implementation

```php
<?php

namespace App\Models\Concerns;

use Spatie\Translatable\HasTranslations;

trait HasTranslatableAttributes
{
    use HasTranslations;

    /**
     * Get the translatable attributes.
     *
     * @return array<int, string>
     */
    public function getTranslatableAttributes(): array
    {
        return $this->translatable ?? [];
    }

    /**
     * Set a translation for a given attribute.
     *
     * @param  string  $attribute
     * @param  string  $locale
     * @param  mixed  $value
     * @return $this
     */
    public function setTranslation(string $attribute, string $locale, $value): self
    {
        $this->setTranslations($attribute, array_merge(
            $this->getTranslations($attribute),
            [$locale => $value]
        ));

        return $this;
    }

    /**
     * Get translation for current locale.
     *
     * @param  string  $attribute
     * @param  string|null  $locale
     * @param  bool  $useFallbackLocale
     * @return mixed
     */
    public function getTranslation(string $attribute, ?string $locale = null, bool $useFallbackLocale = true)
    {
        return parent::getTranslation($attribute, $locale, $useFallbackLocale);
    }
}
```

### Configuration

Set up locales in `config/translatable.php`:

```php
<?php

return [
    'locales' => [
        'en_GB' => 'English (UK)',
        'en_US' => 'English (US)',
        'de_DE' => 'Deutsch',
        'nl_NL' => 'Nederlands',
        'nl_BE' => 'Vlaams',
    ],

    'fallback_locale' => 'en_GB',

    'fallback_any_locale' => false,
];
```

### Usage

```php
use App\Models\Concerns\HasTranslatableAttributes;

class Team extends Model
{
    use HasTranslatableAttributes;

    public array $translatable = ['name', 'description'];

    // Usage:
    // $team->name = 'Sales Team';
    // $team->setTranslation('name', 'de_DE', 'Verkaufsteam');
    // $team->getTranslation('name', 'de_DE');
}
```

## HasTranslatableSlug Trait

### Purpose
Generates translatable slugs per locale from translatable name attributes, with hierarchical support.

### Implementation

```php
<?php

namespace App\Models\Concerns;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasTranslatableSlug
{
    use Sluggable;
    use HasTranslatableAttributes;

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array<string, mixed>
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => function (Model $model, string $key): ?string {
                    // Get name in current locale
                    $locale = app()->getLocale();
                    return $model->getTranslation('name', $locale, false);
                },
                'method' => function (string $string, string $separator): string {
                    return $this->generateHierarchicalSlug($string, $separator);
                },
                'onUpdate' => true,
            ],
        ];
    }

    /**
     * Generate hierarchical slug.
     *
     * @param  string  $string
     * @param  string  $separator
     * @return string
     */
    protected function generateHierarchicalSlug(string $string, string $separator = '-'): string
    {
        $baseSlug = Str::slug($string, $separator);

        // If model has parent, prepend parent slug
        if ($this->parent && method_exists($this->parent, 'getSlugAttribute')) {
            $parentSlug = $this->parent->slug;
            if ($parentSlug) {
                $locale = app()->getLocale();
                $parentSlugValue = is_array($parentSlug)
                    ? ($parentSlug[$locale] ?? $parentSlug['en_GB'] ?? '')
                    : $parentSlug;

                if ($parentSlugValue) {
                    return rtrim($parentSlugValue, '/') . '/' . $baseSlug;
                }
            }
        }

        return $baseSlug;
    }

    /**
     * Generate slugs for all configured locales.
     *
     * @return $this
     */
    public function generateSlugsForAllLocales(): self
    {
        $locales = config('translatable.locales', ['en_GB']);

        foreach ($locales as $locale) {
            app()->setLocale($locale);
            $this->generateSlug();
        }

        // Reset to default locale
        app()->setLocale(config('app.locale'));

        return $this;
    }

    /**
     * Get slug for current locale.
     *
     * @param  string|null  $locale
     * @return string|null
     */
    public function getSlugAttribute(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        $slug = $this->attributes['slug'] ?? null;

        if (is_array($slug)) {
            return $slug[$locale] ?? $slug[config('translatable.fallback_locale')] ?? null;
        }

        return $slug;
    }

    /**
     * Boot the trait.
     */
    public static function bootHasTranslatableSlug(): void
    {
        static::saving(function (Model $model): void {
            if ($model->isDirty('name') || $model->isDirty('parent_id')) {
                $model->generateSlugsForAllLocales();
            }
        });

        // When parent name changes, regenerate child slugs
        static::updated(function (Model $model): void {
            if ($model->isDirty('name')) {
                $model->children()->each(function ($child) {
                    $child->generateSlugsForAllLocales();
                    $child->saveQuietly();
                });
            }
        });
    }
}
```

### Slug Configuration

The slug column should be JSONB (PostgreSQL) or JSON (MySQL/SQLite) to store multiple locale values:

```php
// In migration
$table->jsonb('slug')->nullable(); // PostgreSQL
// or
$table->json('slug')->nullable(); // MySQL/SQLite
```

### Usage

```php
use App\Models\Concerns\HasTranslatableSlug;

class Team extends Model
{
    use HasTranslatableSlug;

    // Automatically generates slug from name
    // Hierarchical: parent-slug/child-slug
    // Translatable: different slug per locale

    // $team->slug; // Gets slug for current locale
    // $team->getSlugAttribute('de_DE'); // Gets slug for specific locale
}
```

## Trait Composition Order

When using multiple traits, compose them in this order:

```php
class Team extends Model
{
    use HasUlid;                    // 1. ULID first (no dependencies)
    use HasTranslatableAttributes;   // 2. Translatable attributes
    use HasTranslatableSlug;         // 3. Slug (depends on HasTranslatableAttributes)

    // Other traits...
}
```

## Testing Traits

### Test Structure

Create trait tests in `tests/Unit/Concerns/`:

```php
<?php

namespace Tests\Unit\Concerns;

use App\Models\User;
use Tests\TestCase;

uses(TestCase::class);

it('has ulid trait functionality', function () {
    $user = User::factory()->create();

    expect($user->ulid)->toBeString();
    expect($user->getRouteKeyName())->toBe('ulid');
});

it('generates unique ulids', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    expect($user1->ulid)->not->toBe($user2->ulid);
});
```

## Next Steps

- Review [Models Implementation](070-models-implementation.md) to see traits in use
- Check [Database Setup](040-database-setup.md) for migration requirements
- See [Testing Infrastructure](100-testing-infrastructure.md) for test setup
