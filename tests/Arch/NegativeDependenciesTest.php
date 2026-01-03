<?php

declare(strict_types=1);

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teams\BulkTeamController;
use App\Services\TeamHierarchyTraversalService;
use App\Services\TeamMove\TeamMoveApprovalService;
use App\Services\TeamMove\TeamMoveRequestService;
use Illuminate\Support\Facades\DB;

/*
 * |--------------------------------------------------------------------------
 * | Negative Dependency Rules
 * |--------------------------------------------------------------------------
 * |
 * | Ensures certain namespaces are NOT used where they shouldn't be.
 * | These rules complement the positive dependency rules.
 * |
 * | Optimizations:
 * | - Excludes vendor and tests from analysis
 * | - Focuses on specific anti-patterns
 */

// Apply Laravel preset
arch()->preset()->laravel();

// ============================================================================
// Facade Usage Restrictions
// ============================================================================

arch('Controllers should not use facades directly')
    ->expect('App\Http\Controllers')
    ->not->toUse([
        'Illuminate\Support\Facades',
    ])
    ->ignoring([
        Controller::class,
        BulkTeamController::class, // Uses Auth facade
    ]);

arch('Services should not use facades (DB facade is allowed for transactions)')
    ->expect('App\Services')
    ->not->toUse([
        'Illuminate\Support\Facades',
    ])
    ->ignoring([
        TeamMoveRequestService::class,
        TeamMoveApprovalService::class,
        TeamHierarchyTraversalService::class,
    ]);

// ============================================================================
// Layer Separation Rules
// ============================================================================

arch('Models should not depend on HTTP layer')
    ->expect('App\Models')
    ->not->toUse([
        'App\Http',
    ]);

arch('Enums should not have domain dependencies')
    ->expect('App\Enums')
    ->not->toUse([
        'App\Models',
        'App\Services',
        'App\Actions',
        'App\Http',
    ]);

arch('Console Commands should not depend on HTTP layer')
    ->expect('App\Console\Commands')
    ->not->toUse([
        'App\Http',
    ]);

// ============================================================================
// Specific Architectural Constraints
// ============================================================================

arch('DB facade usage should be limited to Actions and Services (for transactions)')
    ->expect('App')
    ->not->toUse([
        DB::class,
    ])
    ->ignoring([
        'App\Actions',
        'App\Services',
        'App\Models\Builders',
    ]);
