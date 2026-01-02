<?php

declare(strict_types=1);

/*
 * |--------------------------------------------------------------------------
 * | Authorization & Event Dependency Rules
 * |--------------------------------------------------------------------------
 * |
 * | Ensures authorization and event handling components (Policies, Observers,
 * | Listeners, Presenters) only depend on appropriate namespaces.
 */

// Apply Laravel preset for common architectural conventions
arch()->preset()->laravel();

// ============================================================================
// Authorization & Event Dependencies
// ============================================================================

arch('Policies should only use Models and framework classes')
    ->expect('App\Policies')
    ->toOnlyUse([
        'App\Models',
        'Illuminate',
        'Laravel',
    ]);

arch('Observers should only use Models, Services, and framework classes')
    ->expect('App\Observers')
    ->toOnlyUse([
        'App\Models',
        'App\Services',
        'Illuminate',
        'Laravel',
    ]);

arch('Listeners should only use Models and framework classes')
    ->expect('App\Listeners')
    ->toOnlyUse([
        'App\Models',
        'Illuminate',
        'Laravel',
    ]);

arch('Presenters should only use Models and framework classes')
    ->expect('App\Presenters')
    ->toOnlyUse([
        'App\Models',
        'Illuminate',
        'Laravel',
    ]);
