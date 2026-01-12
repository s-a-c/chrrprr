<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\HasTranslatableAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int|null $id
 * @property array<string, string>|string|null $name
 */
final class TranslatableTestModel extends Model
{
    use HasFactory;
    use HasTranslatableAttributes;

    /** @var bool */
    public $timestamps = false;

    /** @var array<int, string> */
    public $translatable = ['name'];

    /** @var string|null */
    protected $table = 'translatable_test_models';

    /** @var array<string> */
    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = ['name' => 'array']; // Spatie trait TranslatableTestModel casting usually but ensures array for non-db testing
}
