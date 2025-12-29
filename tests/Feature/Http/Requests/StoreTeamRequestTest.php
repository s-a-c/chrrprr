<?php

declare(strict_types=1);

use App\Enums\TeamType;
use App\Http\Requests\StoreTeamRequest;
use App\Models\Enterprise;
use Illuminate\Support\Facades\Validator;

it('validates required fields', function (): void {
    $validator = Validator::make([], new StoreTeamRequest()->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->messages())->toHaveKeys(['name', 'type']);
});

it('validates enum StoreTeamRequestTest', function (): void {
    $validator = Validator::make([
        'name' => 'Test Team',
        'type' => 'invalid_type',
    ], new StoreTeamRequest()->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->messages())->toHaveKey('type');
});

it('validates parent_id existence', function (): void {
    $validator = Validator::make(
        [
            'name' => 'Test Team',
            'type' => TeamType::ORGANISATION->value,
            'parent_id' => 999999, // Non-existent ID
        ],
        new StoreTeamRequest()->rules(),
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->messages())->toHaveKey('parent_id');
});

it('passes validation with valid data', function (): void {
    $enterprise = Enterprise::factory()->create();

    $validator = Validator::make([
        'name' => 'Test Team',
        'type' => TeamType::ORGANISATION->value,
        'parent_id' => $enterprise->id,
        'bio' => 'Something about the team',
    ], new StoreTeamRequest()->rules());

    expect($validator->fails())->toBeFalse();
});

it('allows nullable bio', function (): void {
    $enterprise = Enterprise::factory()->create();

    $validator = Validator::make([
        'name' => 'Test Team',
        'type' => TeamType::ORGANISATION->value,
        'parent_id' => $enterprise->id,
        'bio' => null,
    ], new StoreTeamRequest()->rules());

    expect($validator->fails())->toBeFalse();
});
