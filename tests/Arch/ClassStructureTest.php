<?php

declare(strict_types=1);

/*
 * |--------------------------------------------------------------------------
 * | Class Structure Rules
 * |--------------------------------------------------------------------------
 * |
 * | Ensures classes follow proper structural patterns (final, extends, implements).
 * |
 * | Optimizations:
 * | - Uses classes() method for efficient class-level analysis
 * | - Excludes non-class files from analysis
 */
use App\Http\Controllers\Controller;
use App\Livewire\Teams\MoveTeam;
use Illuminate\Database\Eloquent\Model;

// Apply Laravel preset for common architectural conventions
arch()->preset()->laravel()
    ->ignoring([
        'App\Providers\Filament',
        'App\Projections',
        MoveTeam::class,
        'App\Models\Builders',
    ]);

// ============================================================================
// Class Instantiation Rules
// ============================================================================

arch('All controllers should be instantiable classes')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toBeClasses()
    ->ignoring(Controller::class);

// ============================================================================
// Inheritance Rules
// ============================================================================

arch('All models should extend Eloquent Model')
    ->expect('App\Models')
    ->classes()
    ->toExtend(Model::class)
    ->ignoring([
        'App\Models\Builders',
    ]);

// ============================================================================
// Final Class Rules
// ============================================================================

arch('Services should be final')
    ->expect('App\Services')
    ->classes()
    ->toBeFinal();

arch('Actions should be final')
    ->expect('App\Actions')
    ->classes()
    ->toBeFinal();

arch('Policies should be final classes')
    ->expect('App\Policies')
    ->classes()
    ->toBeFinal();

// ============================================================================
// Interface Implementation Rules
// ============================================================================

arch('Enums should implement BackedEnum')
    ->expect('App\Enums')
    ->classes()
    ->toImplement('BackedEnum');
