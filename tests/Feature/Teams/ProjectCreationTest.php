<?php

declare(strict_types=1);

use App\Models\Enterprise;
use App\Models\Project;

test('can create project as floating team (no parent)', function (): void {
    $enterprise = Enterprise::factory()->create();

    // Projects are cross-functional and must be floating (no parent)
    $project = Project::factory()->create([
        'parent_id' => null,
        'tenant_id' => $enterprise->id,
    ]);

    expect($project)
        ->toBeInstanceOf(Project::class)
        ->parent_id->toBeNull()
        ->tenant_id->toBe($enterprise->id);
});

test('project has tenant association', function (): void {
    $enterprise = Enterprise::factory()->create();

    $project = Project::factory()->create([
        'parent_id' => null,
        'tenant_id' => $enterprise->id,
    ]);

    expect($project->tenant->id)->toBe($enterprise->id);
});

test('multiple projects can exist in same tenant', function (): void {
    $enterprise = Enterprise::factory()->create();

    $project1 = Project::factory()->create([
        'parent_id' => null,
        'tenant_id' => $enterprise->id,
        'name' => ['en' => 'Project 1'],
    ]);

    $project2 = Project::factory()->create([
        'parent_id' => null,
        'tenant_id' => $enterprise->id,
        'name' => ['en' => 'Project 2'],
    ]);

    expect($project1->id)->not->toBe($project2->id);
    expect($project1->tenant_id)->toBe($project2->tenant_id);
});
