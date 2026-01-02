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

use Illuminate\Database\Eloquent\Model;

// Apply Laravel preset
arch()->preset()->laravel();

// ============================================================================
// Class Instantiation Rules
// ============================================================================

arch('All controllers should be instantiable classes')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toBeClasses()
    ->ignoring('App\Http\Controllers\Controller');

// ============================================================================
// Inheritance Rules
// ============================================================================

arch('All models should extend Eloquent Model')
    ->expect('App\Models')
    ->classes()
    ->toExtend(Model::class);

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
