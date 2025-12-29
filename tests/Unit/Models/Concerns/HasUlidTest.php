<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Schema::create('has_ulid_test_models', function (Blueprint $table): void {
        $table->id();
        $table->char('ulid', 26)->nullable();
    });
});

it('generates a ulid on creation', function (): void {
    $model = HasUlidTestModel::query()->create();

    expect($model->ulid)->not->toBeNull()->and(mb_strlen($model->ulid))->toBe(26);
});

it('does not overwrite existing ulid', function (): void {
    $ulid = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    $model = HasUlidTestModel::query()->create(['ulid' => $ulid]);

    expect($model->ulid)->toBe($ulid);
});
