<?php

declare(strict_types=1);

/*
 * |--------------------------------------------------------------------------
 * | Test Structure Rules
 * |--------------------------------------------------------------------------
 * |
 * | Ensures test classes follow proper structure and extend TestCase.
 * |
 * | Optimizations:
 * | - Only analyzes test directories
 * | - Excludes vendor and other non-test paths
 */
use App\Console\Commands\GenerateUserUlidsCommand;
use App\Console\Commands\OptimizeSearchCommand;
use App\Livewire\Teams\MoveTeam;
use Tests\TestCase;
use Tests\Unit\Models\Concerns\HasUlidTestModel;
use Tests\Unit\Models\Concerns\TranslatableSlugTestModel;
use Tests\Unit\Models\Concerns\TranslatableTestModel;

// Apply Laravel preset
arch()->preset()->laravel()
    ->ignoring([
        'App\Providers\Filament',
        OptimizeSearchCommand::class,
        GenerateUserUlidsCommand::class,
        'App\Projections',
        'App\Models\Builders',
        MoveTeam::class,
    ]);

// ============================================================================
// Test Class Structure
// ============================================================================

arch('Unit tests should extend TestCase')
    ->expect('Tests\Unit')
    ->toExtend(PHPUnit\Framework\TestCase::class)
    ->ignoring([
        HasUlidTestModel::class,
        TranslatableTestModel::class,
        TranslatableSlugTestModel::class,
    ]);

arch('Feature tests should extend TestCase')
    ->expect('Tests\Feature')
    ->toUse(TestCase::class);
