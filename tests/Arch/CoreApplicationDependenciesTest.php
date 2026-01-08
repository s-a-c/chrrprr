<?php

declare(strict_types=1);

use App\Livewire\Teams\MoveTeam;
use Carbon\CarbonImmutable;

/*
 * |--------------------------------------------------------------------------
 * | Core Application Dependency Rules
 * |--------------------------------------------------------------------------
 * |
 * | Ensures core application components (Enums, Commands, Exceptions,
 * | Providers) only depend on appropriate namespaces.
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
// Core Application Dependencies
// ============================================================================

arch('Enums should only use framework classes')
    ->expect('App\Enums')
    ->toOnlyUse([
        'Illuminate',
        'Laravel',
    ]);

arch('Console Commands should only use Models, Services, and framework')
    ->expect('App\Console\Commands')
    ->toOnlyUse([
        'App\Models',
        'App\Services',
        'App\Actions',
        'App\Enums',
        'App\Enums',
        'Illuminate',
        'Laravel',
        'Symfony',
        'Symfony\Component\Uid',
    ]);

arch('Exceptions should only extend framework exception classes')
    ->expect('App\Exceptions')
    ->toOnlyUse([
        'Illuminate',
        'Laravel',
        'Exception',
    ]);

arch('Providers should be able to use all application and framework classes')
    ->expect('App\Providers')
    ->toOnlyUse([
        'App',
        'Illuminate',
        'Laravel',
        'Spatie',
        'Stancl',
        'Parental',
        'Symfony',
        'Cviebrock',
        'Stevebauman',
        'Filament',
        'Livewire',
        'Override',
        CarbonImmutable::class,
        'Stancl\JobPipeline',
        'Stancl\Tenancy',
        'Filament\Support',
    ]);
