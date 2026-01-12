<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Str;
use ReflectionClass;
use Spatie\Sluggable\HasSlug;

/**
 * @mago-expect High complexity due to reflection-based slug generation for translatable fields.
 * This is necessary to work with Spatie's HasSlug trait while supporting translatable attributes.
 */
trait HasTranslatableSlug
{
    use HasSlug;

    public static function bootHasTranslatableSlug(): void
    {
        // Override Spatie's default slug generation for translatable fields
        static::saving(static function ($model): void {
            $slugOptions = $model->getSlugOptions();

            if (! $slugOptions) {
                return;
            }

            // Access SlugOptions properties using reflection
            $reflection = new ReflectionClass($slugOptions);
            $slugFieldProperty = $reflection->getProperty('slugField');
            $slugField = $slugFieldProperty->getValue($slugOptions);

            $generateSlugFromProperty = $reflection->getProperty('generateSlugFrom');
            $sourceFields = $generateSlugFromProperty->getValue($slugOptions);

            // Check if allowDuplicateSlugs property exists (may vary by Spatie version)
            $allowDuplicateSlugs = false;
            if ($reflection->hasProperty('allowDuplicateSlugs')) {
                $allowDuplicateSlugsProperty = $reflection->getProperty('allowDuplicateSlugs');
                $allowDuplicateSlugs = $allowDuplicateSlugsProperty->getValue($slugOptions);
            }

            // Check if slug field is translatable
            if (! (property_exists($model, 'translatable') && in_array($slugField, $model->translatable, true))) {
                // Not translatable, let Spatie handle it normally
                return;
            }

            // Handle translatable slug generation manually
            if (method_exists($model, 'getTranslations')) {
                // Support both single field and array of fields
                $sourceFields = is_array($sourceFields) ? $sourceFields : [$sourceFields];

                foreach ($sourceFields as $sourceField) {
                    // Check if source field is translatable
                    $isSourceFieldTranslatable = property_exists($model, 'translatable')
                        && in_array($sourceField, $model->translatable, true);

                    if ($isSourceFieldTranslatable) {
                        // Source field is translatable - generate slugs for each locale
                        $translations = $model->getTranslations($sourceField);
                        foreach ($translations as $locale => $value) {
                            if ($value === null) {
                                continue;
                            }

                            if ($value === '') {
                                continue;
                            }

                            $slug = Str::slug($value);

                            // Handle duplicate slugs if needed
                            if (! $allowDuplicateSlugs) {
                                $slug = $model->makeTranslatableSlugUnique($slug, $slugField, $locale);
                            }

                            $model->setTranslation($slugField, $locale, $slug);
                        }
                    } else {
                        // Source field is not translatable - generate slug for default locale (use 'en' for consistency)
                        $value = $model->getAttribute($sourceField);
                        if ($value !== null && $value !== '') {
                            $defaultLocale = 'en';
                            $slug = Str::slug($value);

                            // Handle duplicate slugs if needed
                            if (! $allowDuplicateSlugs) {
                                $slug = $model->makeTranslatableSlugUnique($slug, $slugField, $defaultLocale);
                            }

                            $model->setTranslation($slugField, $defaultLocale, $slug);
                        }
                    }
                }
            }
        });
    }

    /**
     * Override Spatie's slug generation to prevent it from running on translatable fields.
     * This method is called by Spatie's HasSlug trait.
     */
    public function shouldGenerateSlug(): bool
    {
        $slugOptions = $this->getSlugOptions();

        if (! $slugOptions) {
            return false;
        }

        // Use reflection to access SlugOptions properties
        $reflection = new ReflectionClass($slugOptions);
        $slugFieldProperty = $reflection->getProperty('slugField');
        $slugField = $slugFieldProperty->getValue($slugOptions);

        // If slug field is translatable, don't let Spatie generate it (we handle it manually)
        if (property_exists($this, 'translatable') && in_array($slugField, $this->translatable, true)) {
            return false;
        }

        // For non-translatable fields, use Spatie's default behavior
        return parent::shouldGenerateSlug();
    }

    /**
     * Make slug unique for translatable attributes.
     */
    protected function makeTranslatableSlugUnique(string $slug, string $slugField, string $locale): string
    {
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $slugField, $locale)) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if slug exists for the given locale.
     */
    protected function slugExists(string $slug, string $slugField, string $locale): bool
    {
        $connection = static::getConnection();
        $driver = $connection->getDriverName();

        // For PostgreSQL, if the column is VARCHAR but contains JSON, we need to cast it
        if ($driver === 'pgsql') {
            // Cast to JSONB for PostgreSQL to handle JSON operations on VARCHAR columns
            $query = static::whereRaw("CAST({$slugField} AS JSONB)->>'{$locale}' = ?", [$slug]);
        } else {
            // For other databases, use standard JSON path syntax
            $query = static::where($slugField.'->'.$locale, $slug);
        }

        if ($this->exists) {
            $query->where($this->getKeyName(), '!=', $this->getKey());
        }

        return $query->exists();
    }
}
