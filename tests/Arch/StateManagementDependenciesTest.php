<?php

declare(strict_types=1);

/*
 * |--------------------------------------------------------------------------
 * | State Management Dependency Rules
 * |--------------------------------------------------------------------------
 * |
 * | Ensures state management components only depend on appropriate
 * | namespaces, maintaining state machine purity.
 */

// Apply Laravel preset for common architectural conventions
arch()->preset()->laravel();

// ============================================================================
// State Management Dependencies
// ============================================================================

arch('States should only use Spatie, framework, and App namespace')
    ->expect('App\States')
    ->toOnlyUse([
        'App',
        'Spatie',
        'Illuminate',
        'Laravel',
        'Override',
    ]);
