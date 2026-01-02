<?php

declare(strict_types=1);

/*
 * |--------------------------------------------------------------------------
 * | Namespace Dependency Rules - Index
 * |--------------------------------------------------------------------------
 * |
 * | This test suite has been split by architectural layer for better
 * | performance and maintainability:
 * |
 * | - HttpLayerDependenciesTest.php: HTTP layer (Controllers, Requests) - 2 tests
 * | - DomainLayerDependenciesTest.php: Domain layer (Models, Builders, Concerns) - 3 tests
 * | - ApplicationLayerDependenciesTest.php: Application layer (Actions, Services, Support) - 3 tests
 * | - StateManagementDependenciesTest.php: State management (States) - 1 test
 * | - UiLayerDependenciesTest.php: UI layer (Livewire, Filament) - 2 tests
 * | - AuthorizationEventDependenciesTest.php: Auth & Events (Policies, Observers, etc.) - 4 tests
 * | - CoreApplicationDependenciesTest.php: Core (Enums, Commands, Exceptions, Providers) - 4 tests
 * |
 * | Total: 19 tests across 7 focused files
 * |
 * | RUNNING THESE TESTS:
 * |   composer test:arch:pest  (runs all architecture tests)
 * |
 * | RUNNING SPECIFIC LAYER:
 * |   pest tests/Arch/HttpLayerDependenciesTest.php
 * |   pest tests/Arch/DomainLayerDependenciesTest.php
 * |   pest tests/Arch/ApplicationLayerDependenciesTest.php
 * |
 * | PERFORMANCE BENEFITS:
 * | - Smaller files = faster analysis per file
 * | - Can run specific layers during development
 * | - Better parallel execution in CI/CD
 */

// This file serves as documentation only.
// All actual tests have been moved to layer-specific test files above.
