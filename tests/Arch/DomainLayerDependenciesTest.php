<?php

declare(strict_types=1);

/*
 * |--------------------------------------------------------------------------
 * | Domain Layer Dependency Rules
 * |--------------------------------------------------------------------------
 * |
 * | Ensures domain layer components (Models, Builders, Concerns) only
 * | depend on appropriate namespaces, maintaining domain purity.
 */

// Apply Laravel preset for common architectural conventions
arch()->preset()->laravel();

// ============================================================================
// Domain Layer Dependencies
// ============================================================================

arch('Models should only use Eloquent, framework, and third-party model packages')
    ->expect('App\Models')
    ->toOnlyUse([
        'App\Enums', // Models can reference Enums
        'App\Models', // Models can reference other models
        'App\Support', // Models can use Support classes (validators, etc.)
        'App\States', // Models can use State classes
        'App\Observers', // Models can reference their observers
        'App\Presenters', // Models can reference their presenters
        'Illuminate',
        'Laravel',
        'Spatie',
        'Parental',
        'Symfony',
        'Cviebrock',
        'Stevebauman',
        'Staudenmeir',
        'Stancl',
        'Database',
        'Tests', // Models\Concerns\HasUlid uses test model for type hint
    ])
    ->ignoring('App\Models\Concerns\HasUlid'); // Uses test model for type hint

arch('Model Builders should only use Models, Enums, and framework classes')
    ->expect('App\Models\Builders')
    ->toOnlyUse([
        'App\Models',
        'App\Enums',
        'Illuminate',
        'Laravel',
    ]);

arch('Model Concerns should follow model dependencies')
    ->expect('App\Models\Concerns')
    ->toOnlyUse([
        'App\Enums',
        'App\Models',
        'App\Services', // Concerns may use services (e.g., TeamHierarchyTraversalService)
        'App\Support',
        'Illuminate',
        'Laravel',
    ]);
