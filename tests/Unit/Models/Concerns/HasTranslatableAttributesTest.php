<?php

declare(strict_types=1);

use App\Models\Concerns\HasTranslatableAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

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
    protected $casts = ['name' => 'array']; // Spatie trait handles casting usually but ensures array for non-db testing
}

beforeEach(function (): void {
    Schema::create('translatable_test_models', function (Blueprint $table): void {
        $table->id();
        $table->json('name')->nullable();
    });
});

it('handles translatable attributes', function (): void {
    $model = new TranslatableTestModel();
    $model->setTranslation('name', 'en', 'Name in English');
    $model->setTranslation('name', 'fr', 'Nom en Français');

    $model->save();

    $model->refresh();

    expect($model->getTranslation('name', 'en'))
        ->toBe('Name in English')
        ->and($model->getTranslation('name', 'fr'))
        ->toBe('Nom en Français');
});
