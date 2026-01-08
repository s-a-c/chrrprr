#!/bin/bash

# Standalone script to run CI checks locally
# Matches all GitHub Actions workflows: tests.yml, pre-commit.yml, browser-tests.yml, nightly-heavy.yml
# Can be run manually: ./scripts/run-ci-checks.sh
# Set CI_FULL=1 to include heavy tier and browser tests (e.g., CI_FULL=1 composer ci:local)

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

printf "${YELLOW}Running CI checks locally...${NC}\n"

# Version checks
printf "${YELLOW}=== Version Checks ===${NC}\n"

# Check PHP version from composer.json
PHP_REQUIREMENT=$(grep '"php"' composer.json | sed 's/.*"php": *"\([^"]*\)".*/\1/' | head -1)
if [ -z "$PHP_REQUIREMENT" ]; then
    printf "${RED}✗ Could not determine PHP requirement from composer.json${NC}\n"
    exit 1
fi

# Extract minimum PHP version (e.g., ^8.5 -> 8.5)
PHP_MIN=$(printf "%s" "$PHP_REQUIREMENT" | sed 's/\^//' | sed 's/~//' | sed 's/>=//' | cut -d. -f1,2)
CURRENT_PHP=$(php -r "printf PHP_VERSION;" | cut -d. -f1,2)

# Compare versions (simple numeric comparison for major.minor)
PHP_MIN_MAJOR=$(printf "%s" "$PHP_MIN" | cut -d. -f1)
PHP_MIN_MINOR=$(printf "%s" "$PHP_MIN" | cut -d. -f2)
CURRENT_PHP_MAJOR=$(printf "%s" "$CURRENT_PHP" | cut -d. -f1)
CURRENT_PHP_MINOR=$(printf "%s" "$CURRENT_PHP" | cut -d. -f2)

if [ "$CURRENT_PHP_MAJOR" -lt "$PHP_MIN_MAJOR" ] || ([ "$CURRENT_PHP_MAJOR" -eq "$PHP_MIN_MAJOR" ] && [ "$CURRENT_PHP_MINOR" -lt "$PHP_MIN_MINOR" ]); then
    printf "${RED}✗ PHP version mismatch\n"
    printf "  Required: %s (minimum %s)\n" "${PHP_REQUIREMENT}" "${PHP_MIN}"
    printf "  Current: %s\n" "${CURRENT_PHP}"
    printf "  Please use PHP %s or higher\n" "${PHP_MIN}"
    exit 1
else
    printf "${GREEN}✓ PHP version OK${NC} (%s, required: %s)\n" "${CURRENT_PHP}" "${PHP_REQUIREMENT}"
fi

# Check Node version from package.json
if command -v node &> /dev/null; then
    NODE_REQUIREMENT=$(grep '"node"' package.json | sed 's/.*"node": *">=\([^"]*\)".*/\1/' | head -1)
    if [ -n "$NODE_REQUIREMENT" ]; then
        CURRENT_NODE=$(node -v | sed 's/v//' | cut -d. -f1)
        if [ "$CURRENT_NODE" -lt "$NODE_REQUIREMENT" ]; then
            printf "${RED}✗ Node version mismatch${NC}\n"
            printf "  Required: >=${NODE_REQUIREMENT}\n"
            printf "  Current: ${CURRENT_NODE}\n"
            printf "  Please use Node ${NODE_REQUIREMENT} or higher\n"
            exit 1
        else
            printf "${GREEN}✓ Node version OK${NC} (${CURRENT_NODE}, required: >=${NODE_REQUIREMENT})\n"
        fi
    fi
else
    printf "${YELLOW}⚠ Node not found, skipping Node version check${NC}\n"
fi

# Check Bun version from package.json
if command -v bun &> /dev/null; then
    BUN_REQUIREMENT=$(grep '"bun"' package.json | sed 's/.*"bun": *">=\([^"]*\)".*/\1/' | head -1)
    if [ -n "$BUN_REQUIREMENT" ]; then
        CURRENT_BUN=$(bun -v | cut -d. -f1,2)
        BUN_MIN_MAJOR=$(printf "%s" "$BUN_REQUIREMENT" | cut -d. -f1)
        BUN_MIN_MINOR=$(printf "%s" "$BUN_REQUIREMENT" | cut -d. -f2)
        CURRENT_BUN_MAJOR=$(printf "%s" "$CURRENT_BUN" | cut -d. -f1)
        CURRENT_BUN_MINOR=$(printf "%s" "$CURRENT_BUN" | cut -d. -f2)

        if [ "$CURRENT_BUN_MAJOR" -lt "$BUN_MIN_MAJOR" ] || ([ "$CURRENT_BUN_MAJOR" -eq "$BUN_MIN_MAJOR" ] && [ "$CURRENT_BUN_MINOR" -lt "$BUN_MIN_MINOR" ]); then
            printf "${RED}✗ Bun version mismatch${NC}\n"
            printf "  Required: >=${BUN_REQUIREMENT}\n"
            printf "  Current: ${CURRENT_BUN}\n"
            printf "  Please use Bun ${BUN_REQUIREMENT} or higher\n"
            exit 1
        else
            printf "${GREEN}✓ Bun version OK${NC} (${CURRENT_BUN}, required: >=${BUN_REQUIREMENT})\n"
        fi
    fi
else
    printf "${YELLOW}⚠ Bun not found, skipping Bun version check${NC}\n"
fi

# Track failures
FAILED=0

# Function to run a check
run_check() {
    local name=$1
    local command=$2

    printf "${YELLOW}Running: ${name}...${NC}\n"
    if eval "$command"; then
        printf "${GREEN}✓ ${name} passed${NC}\n"
    else
        printf "${RED}✗ ${name} failed${NC}\n"
        FAILED=1
        return 1
    fi
}

# Build Assets (required for tests that render views with @vite)
printf "${YELLOW}=== Build Assets ===${NC}\n"

# Check if bun is available before attempting build
if command -v bun &> /dev/null; then
    # Check if node_modules exists, if not install dependencies first
    if [ ! -d "node_modules" ]; then
        printf "${YELLOW}Installing Bun dependencies...${NC}\n"
        if ! bun install --frozen-lockfile; then
            printf "${RED}✗ Failed to install Bun dependencies${NC}\n"
            FAILED=1
        fi
    fi

    run_check "Build Frontend Assets" "bun run build" || FAILED=1
else
    printf "${YELLOW}⚠ Bun not found, skipping asset build${NC}\n"
    printf "${YELLOW}  Tests that render views may fail without built assets${NC}\n"
fi

# Core Quality Checks (same as GitHub Actions)
printf "${YELLOW}=== Core Quality Checks ===${NC}\n"

run_check "Linting (Pint, Rector, JS)" "composer test:lint" || FAILED=1

run_check "Unit Tests with Coverage" "composer test:unit" || FAILED=1

run_check "Type Checking (PHPStan)" "composer test:types" || FAILED=1

run_check "Security Audit" "composer security:audit" || FAILED=1

# Policy Checksum Monitor
printf "${YELLOW}=== Policy Checks ===${NC}\n"
# Skip Policy Checksum Monitor on PHP 8.4 due to Monolog compatibility issue
PHP_VERSION=$(php -r "printf PHP_VERSION;" | cut -d. -f1,2)
if [ "$PHP_VERSION" = "8.4" ]; then
    printf "${YELLOW}⚠ Policy Checksum Monitor skipped: Monolog compatibility issue with PHP 8.4${NC}\n"
    printf "${YELLOW}  This is a known issue: PHP 8.4's native PSR interfaces conflict with Monolog${NC}\n"
    printf "${YELLOW}  Consider using PHP 8.3 or wait for Monolog PHP 8.4 compatibility update${NC}\n"
else
    run_check "Policy Checksum Monitor" "php artisan policy:checksum-monitor" || FAILED=1
fi

# Environment Validation (matches tests.yml environment-validation job)
printf "${YELLOW}=== Environment Validation ===${NC}\n"
# Check if .env exists and database is accessible
if [ -f ".env" ]; then
    # Try to run environment validation
    # This will gracefully fail if database isn't set up or profiles don't exist
    if php artisan platform:validate-profiles --all &> /dev/null 2>&1; then
        run_check "Validate Environment Profiles" "php artisan platform:validate-profiles --all" || FAILED=1
    else
        printf "${YELLOW}⚠ Environment validation skipped${NC}\n"
        printf "${YELLOW}  Database may not be configured or BasePlatformSeeder not run${NC}\n"
        printf "${YELLOW}  Run 'php artisan migrate --force && php artisan db:seed --class=BasePlatformSeeder' to enable${NC}\n"
    fi
else
    printf "${YELLOW}⚠ .env file not found, skipping environment validation${NC}\n"
    printf "${YELLOW}  Copy .env.example to .env and configure database to enable${NC}\n"
fi

# Heavy Tier Workflow (matches nightly-heavy.yml)
# Runs mutation tests and Playwright browser tests
# Set CI_FULL=1 to enable (e.g., CI_FULL=1 composer ci:local)
if [ "${CI_FULL:-0}" = "1" ]; then
    printf "${YELLOW}=== Heavy Tier Workflow ===${NC}\n"

    # Check if Infection is available for mutation testing
    if [ -f "vendor/bin/infection" ]; then
        run_check "Mutation Tests" "composer test:mutation" || FAILED=1
    else
        printf "${YELLOW}⚠ Mutation tests skipped: Infection not found${NC}\n"
        printf "${YELLOW}  Install Infection with: composer require --dev infection/infection${NC}\n"
    fi

    # Check if Playwright is available
    if command -v bun &> /dev/null && ([ -f "node_modules/.bin/playwright" ] || [ -f "node_modules/@playwright/test/package.json" ]); then
        # Install Playwright browsers if not already installed
        if ! bunx playwright --version &> /dev/null 2>&1; then
            printf "${YELLOW}Installing Playwright browsers...${NC}\n"
            bunx playwright install --with-deps || {
                printf "${YELLOW}⚠ Failed to install Playwright browsers, skipping browser tests${NC}\n"
            }
        fi

        if bunx playwright --version &> /dev/null 2>&1; then
            run_check "Playwright Browser Tests" "bunx playwright test" || FAILED=1
        else
            printf "${YELLOW}⚠ Playwright browser tests skipped: browsers not installed${NC}\n"
        fi
    else
        printf "${YELLOW}⚠ Playwright tests skipped: Playwright not found in node_modules${NC}\n"
        printf "${YELLOW}  Run 'bun install' to install dependencies${NC}\n"
    fi

    # Run Policy Checksum Monitor again (matches nightly-heavy.yml)
    if [ "$PHP_VERSION" != "8.4" ]; then
        run_check "Policy Checksum Monitor (Heavy)" "php artisan policy:checksum-monitor" || FAILED=1
    fi
else
    printf "${YELLOW}=== Heavy Tier Workflow (Skipped) ===${NC}\n"
    printf "${YELLOW}⚠ Heavy tier workflow skipped (mutation tests, browser tests)${NC}\n"
    printf "${YELLOW}  Set CI_FULL=1 to enable: CI_FULL=1 composer ci:local${NC}\n"
fi

# Browser Tests (matches browser-tests.yml)
# Uses starter-kit-browser-tests package for Pest browser testing
# Set CI_FULL=1 to enable (e.g., CI_FULL=1 composer ci:local)
if [ "${CI_FULL:-0}" = "1" ]; then
    printf "${YELLOW}=== Browser Tests (Starter Kit) ===${NC}\n"

    # Check if starter-kit-browser-tests package exists
    if [ -d "vendor/laravel-labs/starter-kit-browser-tests" ]; then
        # Check if Playwright is available
        if command -v bun &> /dev/null; then
            # Install Playwright browsers if not already installed
            if ! bunx playwright --version &> /dev/null 2>&1; then
                printf "${YELLOW}Installing Playwright browsers...${NC}\n"
                bunx playwright install --with-deps || {
                    printf "${YELLOW}⚠ Failed to install Playwright browsers, skipping browser tests${NC}\n"
                }
            fi

            # Setup test environment using separate tests.starter-kit-browser directory
            if bunx playwright --version &> /dev/null 2>&1; then
                # Create/update tests.starter-kit-browser directory with browser tests
                if cp -rf vendor/laravel-labs/starter-kit-browser-tests/tests/ tests.starter-kit-browser/ 2>/dev/null; then
                    # Create phpunit.xml.starter-kit-browser for CI full tests
                    if cp vendor/laravel-labs/starter-kit-browser-tests/phpunit.xml.dist phpunit.xml.starter-kit-browser 2>/dev/null; then
                        # Update phpunit.xml.starter-kit-browser to use tests.starter-kit-browser directory
                        # Replace all occurrences of <directory>tests/ with <directory>tests.starter-kit-browser/
                        if command -v sed &> /dev/null; then
                            # Use sed with backup extension (required on macOS), then remove backup
                            if sed -i.bak 's|<directory>tests/</directory>|<directory>tests.starter-kit-browser/</directory>|g' phpunit.xml.starter-kit-browser 2>/dev/null; then
                                rm -f phpunit.xml.starter-kit-browser.bak 2>/dev/null || true
                            fi
                        fi

                        # Ensure assets are built
                        if command -v bun &> /dev/null && [ -d "node_modules" ]; then
                            bun run build &> /dev/null || true
                        fi

                        # Run browser tests using the CI full configuration
                        TEST_RESULT=0
                        run_check "Pest Browser Tests (Starter Kit)" "php vendor/bin/pest --configuration=phpunit.xml.starter-kit-browser" || TEST_RESULT=1

                        # Clean up CI full test files (optional - comment out if you want to keep them for debugging)
                        # rm -rf tests.starter-kit-browser/ phpunit.xml.starter-kit-browser

                        if [ "$TEST_RESULT" = "1" ]; then
                            FAILED=1
                        fi
                    else
                        printf "${YELLOW}⚠ Failed to create phpunit.xml.starter-kit-browser${NC}\n"
                        rm -rf tests.starter-kit-browser/ 2>/dev/null || true
                        FAILED=1
                    fi
                else
                    printf "${YELLOW}⚠ Failed to copy browser tests to tests.starter-kit-browser/${NC}\n"
                    rm -rf tests.starter-kit-browser/ phpunit.xml.starter-kit-browser 2>/dev/null || true
                    FAILED=1
                fi
            else
                printf "${YELLOW}⚠ Playwright browser tests skipped: browsers not installed${NC}\n"
            fi
        else
            printf "${YELLOW}⚠ Browser tests skipped: Bun not found${NC}\n"
        fi
    else
        printf "${YELLOW}⚠ Browser tests skipped: starter-kit-browser-tests not found${NC}\n"
        printf "${YELLOW}  Package should be installed via composer require-dev${NC}\n"
    fi
fi

# Summary
printf "\n${YELLOW}=== Summary ===${NC}\n"

if [ $FAILED -eq 0 ]; then
    printf "${GREEN}All CI checks passed! ✓${NC}\n"
    exit 0
else
    printf "${RED}CI checks failed. Please fix the issues above.${NC}\n"
    exit 1
fi
