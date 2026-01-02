<?php

declare(strict_types=1);

/*
 * |--------------------------------------------------------------------------
 * | Core Application Dependency Rules
 * |--------------------------------------------------------------------------
 * |
 * | Ensures core application components (Enums, Commands, Exceptions,
 * | Providers) only depend on appropriate namespaces.
 */

// Apply Laravel preset for common architectural conventions
arch()->preset()->laravel();

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
        'Illuminate',
        'Laravel',
        'Symfony',
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
    ]);
