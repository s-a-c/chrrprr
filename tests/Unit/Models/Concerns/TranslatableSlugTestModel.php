<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Concerns\HasTranslatableSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int|null $id
 * @property array<string, string>|string|null $name
 * @property array<string, string>|string|null $slug
 */
final class TranslatableSlugTestModel extends Model
{
    use HasFactory;
    use HasTranslatableAttributes, HasTranslatableSlug;

    /** @var bool */
    public $timestamps = false;

    /** @var array<int, string> */
    public $translatable = ['name', 'slug'];

    /** @var string|null */
    protected $table = 'translatable_slug_test_models';

    /** @var array<string> */
    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = ['name' => 'array', 'slug' => 'array'];

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
            ->allowDuplicateSlugs(); // Disable Spatie's uniqueness check since we handle it manually for JSON columns
    }
}
