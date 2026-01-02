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

use Tests\TestCase;

// Apply Laravel preset
arch()->preset()->laravel();

// ============================================================================
// Test Class Structure
// ============================================================================

arch('Unit tests should extend TestCase')
    ->expect('Tests\Unit')
    ->toUse(TestCase::class);

arch('Feature tests should extend TestCase')
    ->expect('Tests\Feature')
    ->toUse(TestCase::class);
