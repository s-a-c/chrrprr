#!/bin/bash

# Infection Mutation Testing script using PHP 8.4
# 1. Swaps autoloader to PHP 8.4
# 2. Generates coverage via Pest (bypass for Infection legacy XML bug)
# 3. Runs Infection with skipped initial tests
# 4. Restores autoloader to PHP 8.5

set -e

PHP84="/Users/s-a-c/Library/Application Support/Herd/bin/php84"
PHP85="/Users/s-a-c/Library/Application Support/Herd/bin/php85"
COMPOSER="/Users/s-a-c/Library/Application Support/Herd/bin/composer"

# Colors for output
YELLOW='\033[1;93m'
GREEN='\033[0;32m'
RESET='\033[0m'

printf "${YELLOW}[Infection PHP84]${RESET} Regenerating autoloader for PHP 8.4...\n"
"${PHP84}" "${COMPOSER}" dump-autoload --ignore-platform-req=php --quiet

# Ensure clean state and avoid stale cache
mkdir -p tmp/infection
rm -rf tmp/infection/* 2>/dev/null || true
mkdir -p tmp/infection/coverage-xml

ROOT_DIR=$(pwd)

printf "${YELLOW}[Infection PHP84]${RESET} Generating initial coverage via Pest (8.4)...\n"
# We run Pest on 8.4 to generate coverage reports that Infection will use
# We use absolute paths to ensure Infection can locate files correctly
"${PHP84}" -d memory_limit=2G \
    -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" \
    vendor/bin/pest \
    --coverage-xml="${ROOT_DIR}/tmp/infection/coverage-xml" \
    --log-junit="${ROOT_DIR}/tmp/infection/junit.xml" \
    --testsuite=Unit,Feature

printf "${YELLOW}[Infection PHP84]${RESET} Sanitizing reports (normalizing Pest prefixes)...\n"
# Pest sometimes adds a "P\" prefix to class names or uses absolute paths that confuse Infection
# We use PHP to robustly sanitize JUnit and all coverage XML files
"${PHP84}" -r '
    $rootDir = $argv[1];
    $files = [ $rootDir . "/tmp/infection/junit.xml" ];

    // Find all .xml files in coverage-xml recursively
    if (is_dir($rootDir . "/tmp/infection/coverage-xml")) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootDir . "/tmp/infection/coverage-xml"));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === "xml") {
                $files[] = $file->getPathname();
            }
        }
    }

    foreach ($files as $xmlFile) {
        if (!file_exists($xmlFile)) continue;
        $content = file_get_contents($xmlFile);
        $original = $content;

        // Strip P\ and P. prefixes that Pest adds to dynamic test classes
        // These appear in JUnit (class, classname) and Coverage (Index tests, individual covered by)
        $content = str_replace("P\\\\Tests", "Tests", $content); // Escaped in index.xml/junit.xml sometimes
        $content = str_replace("P\\Tests", "Tests", $content);   // Literal in many XMLs
        $content = str_replace("P.Tests", "Tests", $content);   // Dot notation in classname

        // Also handle escaped versions if they exist
        $content = str_replace("\\\"P\\\\\\\\Tests", "\\\"Tests", $content);
        $content = str_replace("\\\"P.Tests", "\\\"Tests", $content);

        if ($content !== $original) {
            file_put_contents($xmlFile, $content);
        }
    }
' "${ROOT_DIR}"

printf "${YELLOW}[Infection PHP84]${RESET} Running Infection mutation analysis (8.4)...\n"
# Skip initial tests and point to pre-generated reports
"${PHP84}" -d memory_limit=2G \
    -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" \
    vendor/bin/infection \
    --coverage="${ROOT_DIR}/tmp/infection" \
    --skip-initial-tests \
    --threads=max \
    --show-mutations \
    --test-framework-options="--configuration=${ROOT_DIR}/phpunit.xml"

EXIT_CODE=$?

printf "${YELLOW}[Infection PHP84]${RESET} Restoring autoloader for PHP 8.5...\n"
"${PHP85}" "${COMPOSER}" dump-autoload --quiet

if [[ "${EXIT_CODE}" -eq 0 ]]; then
    printf "${GREEN}[Infection PHP84] Success!${RESET}\n"
else
    printf "\033[1;31m[Infection PHP84] Failed with code ${EXIT_CODE}\033[0m\n"
fi

exit "${EXIT_CODE}"
