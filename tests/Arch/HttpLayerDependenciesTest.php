<?php

declare(strict_types=1);

/*
 * |--------------------------------------------------------------------------
 * | HTTP Layer Dependency Rules
 * |--------------------------------------------------------------------------
 * |
 * | Ensures HTTP layer components (Controllers, Requests) only depend on
 * | appropriate namespaces following proper layer separation.
 */

// Apply Laravel preset for common architectural conventions
arch()->preset()->laravel();

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
        'Illuminate',
        'Laravel',
    ])
    ->ignoring('App\Http\Controllers\Controller');

arch('Form Requests should only use Enums, Models, and framework classes')
    ->expect('App\Http\Requests')
    ->toOnlyUse([
        'App\Enums',
        'App\Models',
        'Illuminate',
        'Laravel',
    ]);
