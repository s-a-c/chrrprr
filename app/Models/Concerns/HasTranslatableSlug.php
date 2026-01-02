<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Str;
use ReflectionClass;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

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
            $slugFieldProperty->setAccessible(true);
            $slugField = $slugFieldProperty->getValue($slugOptions);

            $generateSlugFromProperty = $reflection->getProperty('generateSlugFrom');
            $generateSlugFromProperty->setAccessible(true);
            $sourceFields = $generateSlugFromProperty->getValue($slugOptions);

            // Check if allowDuplicateSlugs property exists (may vary by Spatie version)
            $allowDuplicateSlugs = false;
            if ($reflection->hasProperty('allowDuplicateSlugs')) {
                $allowDuplicateSlugsProperty = $reflection->getProperty('allowDuplicateSlugs');
                $allowDuplicateSlugsProperty->setAccessible(true);
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
                    $translations = $model->getTranslations($sourceField);
                    foreach ($translations as $locale => $value) {
                        if (empty($value)) {
                            continue;
                        }

                        $slug = Str::slug($value);

                        // Handle duplicate slugs if needed
                        if (! $allowDuplicateSlugs) {
                            $slug = $model->makeTranslatableSlugUnique($slug, $slugField, $locale);
                        }

                        $model->setTranslation($slugField, $locale, $slug);
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
        $slugFieldProperty->setAccessible(true);
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
        $query = static::where($slugField.'->'.$locale, $slug);

        if ($this->exists) {
            $query->where($this->getKeyName(), '!=', $this->getKey());
        }

        return $query->exists();
    }
}
