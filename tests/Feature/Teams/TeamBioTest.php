<?php

declare(strict_types=1);

use App\Enums\TeamType;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Enterprise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

it('renders bio as markdown', function (): void {
    $team = Enterprise::factory()->create([
        'bio' => '# Hello World',
    ]);

    // We expect a 'bio_html' accessor or method
    expect($team->bio_html)->toContain('<h1>Hello World</h1>');
});

it('sanitizes bio html', function (): void {
    $team = Enterprise::factory()->create([
        'bio' => 'Hello <script>alert("XSS")</script>',
    ]);

    expect($team->bio_html)->not->toContain('<script>');
    expect($team->bio_html)->toContain('Hello');
});

it('enforces bio size limit in validation', function (): void {
    $longBio = str_repeat('a', 10001); // Exceeds max:10000

    $validator = Validator::make([
        'name' => 'Valid Name',
        'type' => TeamType::ENTERPRISE->value,
        'bio' => $longBio,
    ], new StoreTeamRequest()->rules()); // Testing Store request for limits

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->messages())->toHaveKey('bio');

    // Test valid length
    $validBio = str_repeat('a', 10000);
    $validatorValid = Validator::make([
        'name' => 'Valid Name',
        'type' => TeamType::ENTERPRISE->value,
        'bio' => $validBio,
    ], new StoreTeamRequest()->rules());

    expect($validatorValid->fails())->toBeFalse();
});

it('enforces bio size limit in update validation', function (): void {
    $longBio = str_repeat('a', 10001); // Exceeds max:10000

    $validator = Validator::make([
        'name' => 'Valid Name',
        'bio' => $longBio,
    ], new UpdateTeamRequest()->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->messages())->toHaveKey('bio');
});
