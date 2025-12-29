<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Department;
use App\Models\Division;
use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\Project;
use App\Services\TeamTypeResolutionService;

it('resolves type from TeamType enum', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);

    $service = new TeamTypeResolutionService();
    $result = $service->resolve($enterprise);

    expect($result)->toBe('enterprise');
});

it('resolves type from string attribute', function (): void {
    $team = new Organisation(['type' => 'organisation']);

    $service = new TeamTypeResolutionService();
    $result = $service->resolve($team);

    expect($result)->toBe('organisation');
});

it('resolves type from model class', function (): void {
    $division = Division::factory()->create(['name' => ['en' => 'Division']]);
    $division->setRawAttributes(['type' => null]);

    $service = new TeamTypeResolutionService();
    $result = $service->resolve($division);

    expect($result)->toBe('division');
});

it('resolves all team types correctly', function (): void {
    $service = new TeamTypeResolutionService();

    expect($service->resolve(Enterprise::factory()->make()))->toBe('enterprise');
    expect($service->resolve(Organisation::factory()->make()))->toBe('organisation');
    expect($service->resolve(Division::factory()->make()))->toBe('division');
    expect($service->resolve(Department::factory()->make()))->toBe('department');
    expect($service->resolve(Project::factory()->make()))->toBe('project');
});

it('returns null for unknown team class', function (): void {
    $team = new class extends \App\Models\Team {};

    $service = new TeamTypeResolutionService();
    $result = $service->resolve($team);

    expect($result)->toBeNull();
});
