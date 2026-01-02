<?php

declare(strict_types=1);

/*
 * |--------------------------------------------------------------------------
 * | UI Layer Dependency Rules
 * |--------------------------------------------------------------------------
 * |
 * | Ensures UI layer components (Livewire, Filament) only depend on
 * | appropriate namespaces, maintaining presentation layer separation.
 */

// Apply Laravel preset for common architectural conventions
arch()->preset()->laravel();

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
        'Filament',
        'Illuminate',
        'Laravel',
        'Override',
        'Deprecated',
    ]);
