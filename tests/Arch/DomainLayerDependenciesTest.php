<?php

declare(strict_types=1);

use App\Livewire\Teams\MoveTeam;
use App\Models\Concerns\HasUlid;

/*
 * |--------------------------------------------------------------------------
 * | Domain Layer Dependency Rules
 * |--------------------------------------------------------------------------
 * |
 * | Ensures domain layer components (Models, Builders, Concerns) only
 * | depend on appropriate namespaces, maintaining domain purity.
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
        'App\Contracts',
        'Illuminate',
        'Laravel',
        'Spatie',
        'Spatie\ModelStates',
        'Spatie\Sluggable',
        'Spatie\Permission',
        'Spatie\Translatable',
        'App\Services',
        'App\Exceptions',
        'Parental',
        'Symfony',
        'Cviebrock',
        'Stevebauman',
        'Staudenmeir',
        'Stancl',
        'Stancl\Tenancy',
        'Database',
        'Tests',
    ])
    ->ignoring([
        HasUlid::class,
        'getPermissionsTeamId',
        'setPermissionsTeamId',
    ]);

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
        'App\Exceptions',
        'Illuminate',
        'Laravel',
        'Spatie',
        'Spatie\Sluggable',
        'Spatie\Translatable',
        'Symfony',
        'Symfony\Component\Uid',
    ])
    ->ignoring([
        'getPermissionsTeamId',
        'setPermissionsTeamId',
    ]);
