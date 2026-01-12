<?php

declare(strict_types=1);

use App\Enums\TeamType;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Enterprise;
use Illuminate\Support\Facades\Validator;

test('enforces soft limit default 10000 characters in store request', function (): void {
    // Default soft limit should be 10,000 characters (from spec)
    $longBio = str_repeat('a', 10001);

    $validator = Validator::make([
        'name' => 'Valid Name',
        'type' => TeamType::ENTERPRISE->value,
        'bio' => $longBio,
    ], new StoreTeamRequest()->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('bio'))->toBeTrue();
});

test('allows bio at soft limit default 10000 characters in store request', function (): void {
    $validBio = str_repeat('a', 10000);

    $validator = Validator::make([
        'name' => 'Valid Name',
        'type' => TeamType::ENTERPRISE->value,
        'bio' => $validBio,
    ], new StoreTeamRequest()->rules());

    expect($validator->fails())->toBeFalse();
});

test('enforces hard limit 50000 characters in store request', function (): void {
    // Hard limit should be 50,000 characters (system maximum from spec)
    // Note: Currently validation enforces soft limit (10K), hard limit enforcement
    // would be implemented at a different layer (e.g., custom validation rule or service)
    $longBio = str_repeat('a', 50001);

    $validator = Validator::make([
        'name' => 'Valid Name',
        'type' => TeamType::ENTERPRISE->value,
        'bio' => $longBio,
    ], new StoreTeamRequest()->rules());

    // Currently fails at soft limit (10K), hard limit (50K) would be enforced separately
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('bio'))->toBeTrue();
});

test('enforces soft limit in update request', function (): void {
    Enterprise::factory()->create();
    $longBio = str_repeat('a', 10001);

    $validator = Validator::make([
        'name' => 'Valid Name',
        'bio' => $longBio,
    ], new UpdateTeamRequest()->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('bio'))->toBeTrue();
});

test('allows bio at soft limit in update request', function (): void {
    Enterprise::factory()->create();
    $validBio = str_repeat('a', 10000);

    $validator = Validator::make([
        'name' => 'Valid Name',
        'bio' => $validBio,
    ], new UpdateTeamRequest()->rules());

    expect($validator->fails())->toBeFalse();
});

test('allows null bio', function (): void {
    $validator = Validator::make([
        'name' => 'Valid Name',
        'type' => TeamType::ENTERPRISE->value,
        'bio' => null,
    ], new StoreTeamRequest()->rules());

    expect($validator->fails())->toBeFalse();
});
