<?php

declare(strict_types=1);

use App\Exceptions\OptimisticLockingException;
use App\Livewire\Teams\MoveTeam;

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
arch()->preset()->laravel()
    ->ignoring([
        'App\Providers\Filament',
        'App\Projections',
        MoveTeam::class,
        'App\Models\Builders',
    ]);

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
        'App\Handlers',
        'Illuminate',
        'Laravel',
        'Spatie',
        'Spatie\QueueableAction',
        OptimisticLockingException::class,
        'Override',
        'Deprecated',
        'Stevebauman',
        'Stevebauman\Purify',
    ])
    ->ignoring([
        'getPermissionsTeamId',
        'setPermissionsTeamId',
    ]);

arch('Services should only use Actions, Enums, Models, other Services, and framework')
    ->expect('App\Services')
    ->toOnlyUse([
        'App\Actions',
        'App\Enums',
        'App\Models',
        'App\Services',
        'App\Handlers',
        'App\Support',
        'App\Exceptions',
        'Illuminate',
        'Laravel',
        'Spatie',
        'Spatie\Permission',
        'Spatie\Sluggable',
        'Spatie\Translatable',
        'Staudenmeir',
        'Deprecated',
        'Override',
        'Parental',
        'Symfony',
    ])
    ->ignoring([
        'getPermissionsTeamId',
        'setPermissionsTeamId',
    ]);

arch('Support Validation should only use Enums, Models, Services, and framework')
    ->expect('App\Support\Validation')
    ->toOnlyUse([
        'App\Enums',
        'App\Models',
        'App\Services',
        'App\Support',
        'Illuminate',
        'Laravel',
        'Override',
    ]);
