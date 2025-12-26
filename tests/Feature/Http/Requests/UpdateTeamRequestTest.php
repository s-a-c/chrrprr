<?php

declare(strict_types=1);

use App\Http\Requests\UpdateTeamRequest;
use Illuminate\Support\Facades\Validator;

it('validates required name', function (): void {
    $validator = Validator::make([], (new UpdateTeamRequest())->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->messages())->toHaveKey('name');
});

it('allows nullable bio', function (): void {
    $validator = Validator::make([
        'name' => 'Updated Team Name',
        'bio' => null,
    ], (new UpdateTeamRequest())->rules());

    expect($validator->fails())->toBeFalse();
});

it('validates bio is a string', function (): void {
    $validator = Validator::make(
        [
            'name' => 'Updated Team Name',
            'bio' => 12345, // Not a string
        ],
        (new UpdateTeamRequest())->rules(),
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->messages())->toHaveKey('bio');
});

it('passes with valid data', function (): void {
    $validator = Validator::make([
        'name' => 'Updated Team Name',
        'bio' => 'Updated bio content',
    ], (new UpdateTeamRequest())->rules());

    expect($validator->fails())->toBeFalse();
});
