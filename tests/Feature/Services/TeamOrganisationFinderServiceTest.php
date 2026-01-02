<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Division;
use App\Models\Enterprise;
use App\Models\Organisation;
use App\Services\TeamOrganisationFinderService;

it('finds organisation for team within organisation', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);
    $division = Division::factory()->create([
        'parent_id' => $org->id,
        'name' => ['en' => 'Division'],
    ]);

    $service = new TeamOrganisationFinderService();

    expect($service->findOrganisation($division))->toBeInstanceOf(Organisation::class);
    expect($service->findOrganisation($division)?->id)->toBe($org->id);
});

it('returns organisation when team is organisation itself', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Organisation'],
    ]);

    $service = new TeamOrganisationFinderService();

    expect($service->findOrganisation($org))->toBeInstanceOf(Organisation::class);
    expect($service->findOrganisation($org)?->id)->toBe($org->id);
});

it('returns null when team has no organisation ancestor', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);

    $service = new TeamOrganisationFinderService();

    expect($service->findOrganisation($enterprise))->toBeNull();
});

it('detects cross-organisation moves', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);
    $org1 = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Org 1'],
    ]);
    $org2 = Organisation::factory()->create([
        'parent_id' => $enterprise->id,
        'name' => ['en' => 'Org 2'],
    ]);
    $division = Division::factory()->create([
        'parent_id' => $org1->id,
        'name' => ['en' => 'Division'],
    ]);

    $service = new TeamOrganisationFinderService();

    expect($service->isCrossOrganisationMove($division, $org2))->toBeTrue();
    expect($service->isCrossOrganisationMove($division, $org1))->toBeFalse();
});

it('returns false for cross-organisation check when organisations not found', function (): void {
    $enterprise = Enterprise::factory()->create(['name' => ['en' => 'Enterprise']]);

    $service = new TeamOrganisationFinderService();

    expect($service->isCrossOrganisationMove($enterprise, null))->toBeFalse();
});
