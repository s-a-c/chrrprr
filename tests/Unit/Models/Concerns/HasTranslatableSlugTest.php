<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Schema::dropIfExists('translatable_slug_test_models');
    Schema::create('translatable_slug_test_models', function (Blueprint $table): void {
        $table->id();
        $table->json('name')->nullable();
        $table->json('slug')->nullable();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('translatable_slug_test_models');
});

it('generates translatable slugs', function (): void {
    $model = new TranslatableSlugTestModel();
    $model->setTranslation('name', 'en', 'My Super Name');
    $model->setTranslation('name', 'fr', 'Mon Super Nom');
    $model->save();

    expect($model->getTranslation('slug', 'en'))
        ->toBe('my-super-name')
        ->and($model->getTranslation('slug', 'fr'))
        ->toBe('mon-super-nom');
});
