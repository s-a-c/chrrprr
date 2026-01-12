<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Enterprise;
use App\Services\TeamNameNormalizationService;

it('normalizes array name from json string in attributes', function (): void {
    $team = new Enterprise();
    $jsonName = json_encode(['en' => 'English Name', 'es' => 'Spanish Name']);
    $team->attributes = ['name' => $jsonName];

    $service = new TeamNameNormalizationService();
    $result = $service->normalize($team);

    expect($result)->toBeArray();
    expect($result)->toHaveKey('en', 'English Name');
    expect($result)->toHaveKey('es', 'Spanish Name');
});

it('filters out non-string pairs from decoded json', function (): void {
    $team = new Enterprise();
    $jsonName = json_encode(['en' => 'English', 123 => 'Number Key', 'es' => 456]);
    $team->attributes = ['name' => $jsonName];

    $service = new TeamNameNormalizationService();
    $result = $service->normalize($team);

    expect($result)->toBeArray();
    expect($result)->toHaveKey('en', 'English');
    expect($result)->not->toHaveKey(123);
    expect($result)->not->toHaveKey('es');
});

it('falls back to team name attribute when raw name is invalid', function (): void {
    $team = Enterprise::factory()->create(['name' => ['en' => 'Fallback Name']]);
    // Clear the raw attributes to simulate invalid name
    $team->setRawAttributes([]);

    $service = new TeamNameNormalizationService();
    $result = $service->normalize($team);

    // When name is an array, it returns empty string as fallback
    expect($result)->toBe('');
});

it('handles empty string name', function (): void {
    $team = new Enterprise();
    $team->attributes = ['name' => ''];

    $service = new TeamNameNormalizationService();
    $result = $service->normalize($team);

    expect($result)->toBe('');
});

it('handles null name attribute', function (): void {
    $team = new Enterprise();
    $team->attributes = ['name' => null];

    $service = new TeamNameNormalizationService();
    $result = $service->normalize($team);

    expect($result)->toBe('');
});
