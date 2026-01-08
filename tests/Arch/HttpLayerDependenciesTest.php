<?php

declare(strict_types=1);

use App\Http\Controllers\Controller;
use App\Livewire\Teams\MoveTeam;

/*
 * |--------------------------------------------------------------------------
 * | HTTP Layer Dependency Rules
 * |--------------------------------------------------------------------------
 * |
 * | Ensures HTTP layer components (Controllers, Requests) only depend on
 * | appropriate namespaces following proper layer separation.
 */

// Apply Laravel preset for common architectural conventions
arch()->preset()->laravel()
    ->ignoring([
        'App\Providers\Filament',
        'App\Projections',
        MoveTeam::class,
        'App\Models\Builders',
    ]);

// ============================================================================
// HTTP Layer Dependencies
// ============================================================================

arch('Controllers should only depend on Models, Requests, Resources, and framework')
    ->expect('App\Http\Controllers')
    ->toOnlyUse([
        'App\Models',
        'App\Http\Requests',
        'App\Http\Resources',
        'App\Actions', // Controllers can use Actions
        'App\Handlers',
        'Illuminate',
        'Laravel',
        'Symfony',
        'Symfony\Component\HttpKernel',
    ])
    ->ignoring(Controller::class);

arch('Form Requests should only use Enums, Models, and framework classes')
    ->expect('App\Http\Requests')
    ->toOnlyUse([
        'App\Enums',
        'App\Models',
        'Illuminate',
        'Laravel',
    ]);
