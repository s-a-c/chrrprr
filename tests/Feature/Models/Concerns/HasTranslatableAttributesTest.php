<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Schema::dropIfExists('translatable_test_models');
    Schema::create('translatable_test_models', function (Blueprint $table): void {
        $table->id();
        $table->json('name')->nullable();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('translatable_test_models');
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
