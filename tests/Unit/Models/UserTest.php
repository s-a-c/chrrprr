<?php

declare(strict_types=1);

use App\Enums\UserState;
use App\Enums\UserStatus;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Concerns\HasUlid;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Sluggable\HasSlug;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('user model has expected traits', function (): void {
    $traits = class_uses(User::class);

    expect($traits)
        ->toContain(HasUlid::class)
        ->toContain(HasTranslatableAttributes::class)
        ->toContain(HasSlug::class);
});

test('user model casts attributes correctly', function (): void {
    $model = new User();

    // Check specific casts in the casts() method return array
    // Since Laravel 11/12 uses protected function casts(): array
    // We can inspect method return or use getCasts()
    $casts = $model->getCasts();

    expect($casts['state'])->toBe(UserState::class)->and($casts['status'])->toBe(UserStatus::class);
});

test('user model is translatable', function (): void {
    $model = new User();
    // Assuming bio is translatable
    if (! property_exists($model, 'translatable')) {
        $this->fail('User model missing translatable property');
    }

    expect($model->translatable)->toContain('bio');
});

test('user model has sluggable configuration', function (): void {
    $model = new User();
    $slugOptions = $model->getSlugOptions();

    // Use reflection to access SlugOptions properties
    $reflection = new ReflectionClass($slugOptions);
    $slugFieldProperty = $reflection->getProperty('slugField');
    $slugFieldProperty->setAccessible(true);
    $slugField = $slugFieldProperty->getValue($slugOptions);

    $generateSlugFromProperty = $reflection->getProperty('generateSlugFrom');
    $generateSlugFromProperty->setAccessible(true);
    $sourceFields = $generateSlugFromProperty->getValue($slugOptions);

    // generateSlugFrom can be a string or array
    $expectedSource = is_array($sourceFields) ? $sourceFields[0] : $sourceFields;

    expect($expectedSource)->toBe('name')
        ->and($slugField)->toBe('slug');
});

test('user model generates slug from name', function (): void {
    $user = User::factory()->create([
        'name' => 'John Doe',
    ]);

    expect($user->slug)->toBe('john-doe');
});
