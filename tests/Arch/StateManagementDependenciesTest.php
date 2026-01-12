<?php

declare(strict_types=1);

use App\Livewire\Teams\MoveTeam;

/*
 * |--------------------------------------------------------------------------
 * | State Management Dependency Rules
 * |--------------------------------------------------------------------------
 * |
 * | Ensures state management components only depend on appropriate
 * | namespaces, maintaining state machine purity.
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
// State Management Dependencies
// ============================================================================

arch('States should only use Spatie, framework, and App namespace')
    ->expect('App\States')
    ->toOnlyUse([
        'App',
        'Spatie',
        'Spatie\ModelStates',
        'Illuminate',
        'Laravel',
        'Override',
    ]);
