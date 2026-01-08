<?php

declare(strict_types=1);

use App\Console\Commands\GenerateUserUlidsCommand;
use App\Console\Commands\OptimizeSearchCommand;
use App\Livewire\Teams\MoveTeam;
use App\Support\Result;

/*
 * |--------------------------------------------------------------------------
 * | UI Layer Dependency Rules
 * |--------------------------------------------------------------------------
 * |
 * | Ensures UI layer components (Livewire, Filament) only depend on
 * | appropriate namespaces, maintaining presentation layer separation.
 */

// Apply Laravel preset for common architectural conventions
arch()->preset()->laravel()
    ->ignoring([
        'App\Providers\Filament',
        OptimizeSearchCommand::class,
        GenerateUserUlidsCommand::class,
        'App\Projections',
        'App\Models\Builders',
        MoveTeam::class, // Ignores MoveTeamRequest violation in preset
    ]);

// ============================================================================
// UI Layer Dependencies
// ============================================================================

arch('Livewire components should only use Enums, Models, Requests, Services, and framework')
    ->expect('App\Livewire')
    ->toOnlyUse([
        'App\Enums',
        'App\Models',
        'App\Http\Requests',
        'App\Services',
        'App\Actions',
        Result::class,
        'App\Contracts',
        'App\Handlers',
        'Illuminate',
        'Laravel',
        'Livewire',
        'Filament',
    ]);

arch('Filament resources should only use Enums, Models, Requests, Services, and Filament')
    ->expect('App\Filament')
    ->toOnlyUse([
        'App\Enums',
        'App\Models',
        'App\Http\Requests',
        'App\Services',
        'App\Actions',
        'App\Handlers',
        'App\Contracts',
        'App\Support',
        'Filament',
        'Filament\Actions',
        'Filament\Forms',
        'Filament\Tables',
        'Filament\Schemas',
        'Filament\Support',
        'Filament\Notifications',
        'Illuminate',
        'Laravel',
        'Override',
        'Deprecated',
    ]);
