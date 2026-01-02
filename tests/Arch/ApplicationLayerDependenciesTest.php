<?php

declare(strict_types=1);

/*
 * |--------------------------------------------------------------------------
 * | Application Layer Dependency Rules
 * |--------------------------------------------------------------------------
 * |
 * | Ensures application layer components (Actions, Services, Support)
 * | only depend on appropriate namespaces, maintaining business logic
 * | separation.
 */

// Apply Laravel preset for common architectural conventions
arch()->preset()->laravel();

// ============================================================================
// Application Layer Dependencies
// ============================================================================

arch('Actions should only use Enums, Models, Requests, Services, and framework')
    ->expect('App\Actions')
    ->toOnlyUse([
        'App\Enums',
        'App\Models',
        'App\Http\Requests',
        'App\Services',
        'App\Support',
        'App\Actions', // Actions can use other Actions
        'Illuminate',
        'Laravel',
        'Spatie',
        'Override',
        'Deprecated',
        'Stevebauman',
    ])
    ->ignoring([
        'App\Actions\Users\UpdateUserProfile', // Uses Hash and Purify facades
    ]);

arch('Services should only use Actions, Enums, Models, other Services, and framework')
    ->expect('App\Services')
    ->toOnlyUse([
        'App\Actions',
        'App\Enums',
        'App\Models',
        'App\Services',
        'App\Support',
        'Illuminate',
        'Laravel',
        'Spatie',
        'Staudenmeir',
        'Deprecated',
        'Override',
    ]);

arch('Support Validation should only use Enums, Models, Services, and framework')
    ->expect('App\Support\Validation')
    ->toOnlyUse([
        'App\Enums',
        'App\Models',
        'App\Services',
        'Illuminate',
        'Laravel',
        'Override',
    ]);
