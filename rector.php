<?php

declare(strict_types=1);

// Compliant with [.ai/AI-GUIDELINES.md](../../.ai/AI-GUIDELINES.md) v374a22e55a53ea38928957463e1f0ef28f820080a27e0466f35d46c20626fa72
//
// Rector Configuration - Aligned with Pint
// =========================================
// This configuration is designed to work harmoniously with:
// - Pint: Laravel's code style formatter (runs after Rector in lintfix - authoritative)
// - Mago: Static analysis only (no formatting - Pint handles all formatting)
//
// Execution order in lintfix:
// 1. Mago lint --fix (static analysis fixes only)
// 2. Rector (code refactoring - may change structure)
// 3. Pint (final Laravel style formatting - authoritative)
//
// Paths match Mago and Pint configurations for consistency.

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use RectorLaravel\Rector\StaticCall\CarbonToDateFacadeRector;
use RectorLaravel\Set\LaravelSetList;
use RectorLaravel\Set\LaravelSetProvider;

return RectorConfig::configure()
    ->withSetProviders(LaravelSetProvider::class)
    ->withSets([
        LaravelSetList::LARAVEL_ARRAYACCESS_TO_METHOD_CALL,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_CONTAINER_STRING_TO_FULLY_QUALIFIED_NAME,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
        LaravelSetList::LARAVEL_FACTORIES,
        LaravelSetList::LARAVEL_IF_HELPERS,
        LaravelSetList::LARAVEL_LEGACY_FACTORIES_TO_CLASSES,
    ])
    // Align with Pint's import ordering: remove unused imports
    // This complements Pint's "global_namespace_import" rule which organizes imports
    ->withImportNames(removeUnusedImports: true)
    ->withComposerBased(laravel: true)
    ->withCache(
        cacheDirectory: __DIR__.'/tmp/rector',
        cacheClass: FileCacheStorage::class,
    )
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/public',
        __DIR__.'/resources',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/bootstrap/cache',
        // Skip NullToStrictStringFuncCallArgRector for test files to avoid conflicts with PHPStan
        // PHPStan knows model casts make properties strings, but Rector adds redundant casts
        NullToStrictStringFuncCallArgRector::class => [
            __DIR__.'/tests',
        ],
        // Preserve intentional usage of mutable Carbon instances in these files
        CarbonToDateFacadeRector::class => [
            __DIR__.'/app/Services/BasePlatform/DependencyCatalogue.php',
            __DIR__.'/app/Console/Commands/DependencyReviewReport.php',
            __DIR__.'/app/Data/DependencyRecordData.php',
            __DIR__.'/tests/Unit/BasePlatform/DependencyCatalogueTest.php',
        ],
        // Skip throw_if refactoring for ProtectsKeyRoles - throw_if with class string doesn't work in this context
        RectorLaravel\Rector\If_\ThrowIfRector::class => [
            __DIR__.'/app/Models/Concerns/ProtectsKeyRoles.php',
        ],
        // Keep $classification param in EnterpriseSeeder::createEnterprise() for context/debugging
        Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector::class => [
            __DIR__.'/database/seeders/EnterpriseSeeder.php',
        ],
        Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector::class,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    )
    ->withPhpSets();
