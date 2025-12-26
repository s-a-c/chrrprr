<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Support\Str;

trait HasTranslatableSlug
{
    use Sluggable;

    public static function bootHasTranslatableSlug(): void
    {
        static::saving(static function ($model): void {
            foreach ($model->sluggable() as $attribute => $config) {
                if (!(property_exists($model, 'translatable') && in_array($attribute, $model->translatable))) {
                    continue;
                }

                $source = $config['source'];
                // Ensure the source is also translatable or at least fetchable
                // We assume source is translatable for this feature
                if (method_exists($model, 'getTranslations')) {
                    $translations = $model->getTranslations($source);
                    foreach ($translations as $locale => $value) {
                        $slug = Str::slug($value);
                        $model->setTranslation($attribute, $locale, $slug);
                    }
                }
            }
        });
    }

    public function sluggableEvent(): string
    {
        // Disable default behavior to handle translations manually
        return '';
    }
}
