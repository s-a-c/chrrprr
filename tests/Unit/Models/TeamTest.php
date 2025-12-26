<?php

declare(strict_types=1);

use App\Enums\TeamStatus;
use App\Enums\TeamType;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Concerns\HasTranslatableSlug;
use App\Models\Concerns\HasUlid;
use App\Models\Team;
use App\States\Team\TeamState;
use Illuminate\Database\Eloquent\SoftDeletes;
use Parental\HasChildren;
use Spatie\ModelStates\HasStates;
use Tests\TestCase;

uses(TestCase::class);

test('team model has expected traits', function (): void {
    $traits = class_uses(Team::class);

    expect($traits)
        ->toContain(HasUlid::class)
        ->toContain(HasTranslatableAttributes::class)
        ->toContain(HasTranslatableSlug::class)
        ->toContain(SoftDeletes::class)
        ->toContain(HasChildren::class)
        ->toContain(HasStates::class); // Spatie State
});

test('team model casts attributes correctly', function (): void {
    $model = new Team();
    $casts = $model->getCasts();

    expect($casts['type'])
        ->toBe(TeamType::class)
        ->and($casts['state'])
        ->toBe(TeamState::class)
        ->and($casts['status'])
        ->toBe(TeamStatus::class)
        ->and($casts['name'])
        ->toBe('array')
        ->and($casts['slug'])
        ->toBe('array');
});

test('team model is translatable', function (): void {
    $model = new Team();
    expect($model->translatable)->toContain('name')->toContain('slug');
});

test('team model has sluggable configuration', function (): void {
    $model = new Team();
    expect($model->sluggable())->toBe([
        'slug' => [
            'source' => 'name',
        ],
    ]);
});
