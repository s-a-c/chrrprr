<?php

declare(strict_types=1);

use App\Enums\UserState;
use App\Enums\UserStatus;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Concerns\HasUlid;
use App\Models\User;
use Tests\TestCase;

uses(TestCase::class);

test('user model has expected traits', function (): void {
    $traits = class_uses(User::class);

    expect($traits)->toContain(HasUlid::class)->toContain(HasTranslatableAttributes::class);
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
    if (property_exists($model, 'translatable')) {
        expect($model->translatable)->toContain('bio');
    } else {
        $this->fail('User model missing translatable property');
    }
});
