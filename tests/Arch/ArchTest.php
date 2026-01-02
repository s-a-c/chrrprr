<?php

declare(strict_types=1);

/*
 * |--------------------------------------------------------------------------
 * | Architectural Rules Tests - Index
 * |--------------------------------------------------------------------------
 * |
 * | This test suite has been split into focused test files for better
 * | performance and maintainability:
 * |
 * | - NamespaceDependenciesTest.php: All toOnlyUse() rules (18 tests)
 * | - NegativeDependenciesTest.php: All not->toUse() rules (5 tests)
 * | - ClassStructureTest.php: toBeFinal, toExtend, toImplement rules (6 tests)
 * | - NamingConventionsTest.php: toHaveSuffix rules (7 tests)
 * | - TestStructureTest.php: Test structure rules (2 tests)
 * |
 * | RUNNING THESE TESTS:
 * |   composer test:arch:pest  (runs all architecture tests)
 * |
 * | COMPLEMENTARY TOOL:
 * |   composer mago:guard  (uses Mago Guard for additional architectural checks)
 * |
 * | PERFORMANCE OPTIMIZATIONS:
 * | - Tests are split into smaller files for parallel execution
 * | - Each file uses excludePaths() to skip unnecessary analysis
 * | - Focused test files reduce memory usage and execution time
 */

// This file serves as documentation only.
// All actual tests have been moved to focused test files above.
