<?php

declare(strict_types=1);

use App\Console\Commands\GenerateUserUlidsCommand;
use App\Console\Commands\SetDefaultUserStatesCommand;
use App\Http\Controllers\Controller;
use App\Livewire\Teams\MoveTeam;

/*
 * |--------------------------------------------------------------------------
 * | Naming Convention Rules
 * |--------------------------------------------------------------------------
 * |
 * | Ensures classes follow consistent naming patterns with proper suffixes.
 * |
 * | Optimizations:
 * | - Uses classes() method for efficient class-level analysis
 * | - Focuses only on naming patterns, not dependencies
 */

// Apply Laravel preset
arch()->preset()->laravel()
    ->ignoring([
        'App\Providers\Filament',
        'App\Projections',
        MoveTeam::class,
        'App\Models\Builders',
    ]);

// ============================================================================
// Suffix Naming Rules
// ============================================================================

arch('Controllers should end with Controller suffix')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toHaveSuffix('Controller')
    ->ignoring(Controller::class);

arch('Policies should end with Policy suffix')
    ->expect('App\Policies')
    ->classes()
    ->toHaveSuffix('Policy');

arch('Observers should end with Observer suffix')
    ->expect('App\Observers')
    ->classes()
    ->toHaveSuffix('Observer');

arch('Listeners should end with Listener suffix')
    ->expect('App\Listeners')
    ->classes()
    ->toHaveSuffix('Listener');

arch('Presenters should end with Presenter suffix')
    ->expect('App\Presenters')
    ->classes()
    ->toHaveSuffix('Presenter');

arch('Exceptions should end with Exception suffix')
    ->expect('App\Exceptions')
    ->classes()
    ->toHaveSuffix('Exception');

arch('Commands should end with Command suffix')
    ->expect('App\Console\Commands')
    ->classes()
    ->toHaveSuffix('Command')
    ->ignoring([
        GenerateUserUlidsCommand::class, // Legacy naming
        SetDefaultUserStatesCommand::class, // Legacy naming
    ]);
