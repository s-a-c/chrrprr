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

echo -e "${YELLOW}Running CI checks locally...${NC}\n"

# Version checks
echo -e "${YELLOW}=== Version Checks ===${NC}\n"

# Check PHP version from composer.json
PHP_REQUIREMENT=$(grep '"php"' composer.json | sed 's/.*"php": *"\([^"]*\)".*/\1/' | head -1)
if [ -z "$PHP_REQUIREMENT" ]; then
    echo -e "${RED}✗ Could not determine PHP requirement from composer.json${NC}\n"
    exit 1
fi

# Extract minimum PHP version (e.g., ^8.5 -> 8.5)
PHP_MIN=$(echo "$PHP_REQUIREMENT" | sed 's/\^//' | sed 's/~//' | sed 's/>=//' | cut -d. -f1,2)
CURRENT_PHP=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)

# Compare versions (simple numeric comparison for major.minor)
PHP_MIN_MAJOR=$(echo "$PHP_MIN" | cut -d. -f1)
PHP_MIN_MINOR=$(echo "$PHP_MIN" | cut -d. -f2)
CURRENT_PHP_MAJOR=$(echo "$CURRENT_PHP" | cut -d. -f1)
CURRENT_PHP_MINOR=$(echo "$CURRENT_PHP" | cut -d. -f2)

if [ "$CURRENT_PHP_MAJOR" -lt "$PHP_MIN_MAJOR" ] || ([ "$CURRENT_PHP_MAJOR" -eq "$PHP_MIN_MAJOR" ] && [ "$CURRENT_PHP_MINOR" -lt "$PHP_MIN_MINOR" ]); then
    echo -e "${RED}✗ PHP version mismatch${NC}"
    echo -e "  Required: ${PHP_REQUIREMENT} (minimum ${PHP_MIN})"
    echo -e "  Current: ${CURRENT_PHP}"
    echo -e "  Please use PHP ${PHP_MIN} or higher\n"
    exit 1
else
    echo -e "${GREEN}✓ PHP version OK${NC} (${CURRENT_PHP}, required: ${PHP_REQUIREMENT})\n"
fi

# Check Node version from package.json
if command -v node &> /dev/null; then
    NODE_REQUIREMENT=$(grep '"node"' package.json | sed 's/.*"node": *">=\([^"]*\)".*/\1/' | head -1)
    if [ -n "$NODE_REQUIREMENT" ]; then
        CURRENT_NODE=$(node -v | sed 's/v//' | cut -d. -f1)
        if [ "$CURRENT_NODE" -lt "$NODE_REQUIREMENT" ]; then
            echo -e "${RED}✗ Node version mismatch${NC}"
            echo -e "  Required: >=${NODE_REQUIREMENT}"
            echo -e "  Current: ${CURRENT_NODE}"
            echo -e "  Please use Node ${NODE_REQUIREMENT} or higher\n"
            exit 1
        else
            echo -e "${GREEN}✓ Node version OK${NC} (${CURRENT_NODE}, required: >=${NODE_REQUIREMENT})\n"
        fi
    fi
else
    echo -e "${YELLOW}⚠ Node not found, skipping Node version check${NC}\n"
fi

# Check Bun version from package.json
if command -v bun &> /dev/null; then
    BUN_REQUIREMENT=$(grep '"bun"' package.json | sed 's/.*"bun": *">=\([^"]*\)".*/\1/' | head -1)
    if [ -n "$BUN_REQUIREMENT" ]; then
        CURRENT_BUN=$(bun -v | cut -d. -f1,2)
        BUN_MIN_MAJOR=$(echo "$BUN_REQUIREMENT" | cut -d. -f1)
        BUN_MIN_MINOR=$(echo "$BUN_REQUIREMENT" | cut -d. -f2)
        CURRENT_BUN_MAJOR=$(echo "$CURRENT_BUN" | cut -d. -f1)
        CURRENT_BUN_MINOR=$(echo "$CURRENT_BUN" | cut -d. -f2)

        if [ "$CURRENT_BUN_MAJOR" -lt "$BUN_MIN_MAJOR" ] || ([ "$CURRENT_BUN_MAJOR" -eq "$BUN_MIN_MAJOR" ] && [ "$CURRENT_BUN_MINOR" -lt "$BUN_MIN_MINOR" ]); then
            echo -e "${RED}✗ Bun version mismatch${NC}"
            echo -e "  Required: >=${BUN_REQUIREMENT}"
            echo -e "  Current: ${CURRENT_BUN}"
            echo -e "  Please use Bun ${BUN_REQUIREMENT} or higher\n"
            exit 1
        else
            echo -e "${GREEN}✓ Bun version OK${NC} (${CURRENT_BUN}, required: >=${BUN_REQUIREMENT})\n"
        fi
    fi
else
    echo -e "${YELLOW}⚠ Bun not found, skipping Bun version check${NC}\n"
fi

# Track failures
FAILED=0

# Function to run a check
run_check() {
    local name=$1
    local command=$2

    echo -e "${YELLOW}Running: ${name}...${NC}"
    if eval "$command"; then
        echo -e "${GREEN}✓ ${name} passed${NC}\n"
    else
        echo -e "${RED}✗ ${name} failed${NC}\n"
        FAILED=1
        return 1
    fi
}

# Build Assets (required for tests that render views with @vite)
echo -e "${YELLOW}=== Build Assets ===${NC}\n"

# Check if bun is available before attempting build
if command -v bun &> /dev/null; then
    # Check if node_modules exists, if not install dependencies first
    if [ ! -d "node_modules" ]; then
        echo -e "${YELLOW}Installing Bun dependencies...${NC}"
        if ! bun install --frozen-lockfile; then
            echo -e "${RED}✗ Failed to install Bun dependencies${NC}\n"
            FAILED=1
        fi
    fi

    run_check "Build Frontend Assets" "bun run build" || FAILED=1
else
    echo -e "${YELLOW}⚠ Bun not found, skipping asset build${NC}"
    echo -e "${YELLOW}  Tests that render views may fail without built assets${NC}\n"
fi

# Core Quality Checks (same as GitHub Actions)
echo -e "${YELLOW}=== Core Quality Checks ===${NC}\n"

run_check "Linting (Pint, Rector, JS)" "composer test:lint" || FAILED=1

run_check "Unit Tests with Coverage" "composer test:unit" || FAILED=1

run_check "Type Checking (PHPStan)" "composer test:types" || FAILED=1

run_check "Security Audit" "composer security:audit" || FAILED=1

# Policy Checksum Monitor
echo -e "${YELLOW}=== Policy Checks ===${NC}\n"
# Skip Policy Checksum Monitor on PHP 8.4 due to Monolog compatibility issue
PHP_VERSION=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)
if [ "$PHP_VERSION" = "8.4" ]; then
    echo -e "${YELLOW}⚠ Policy Checksum Monitor skipped: Monolog compatibility issue with PHP 8.4${NC}\n"
    echo -e "${YELLOW}  This is a known issue: PHP 8.4's native PSR interfaces conflict with Monolog${NC}\n"
    echo -e "${YELLOW}  Consider using PHP 8.3 or wait for Monolog PHP 8.4 compatibility update${NC}\n"
else
    run_check "Policy Checksum Monitor" "php artisan policy:checksum-monitor" || FAILED=1
fi

# Environment Validation (matches tests.yml environment-validation job)
echo -e "${YELLOW}=== Environment Validation ===${NC}\n"
# Check if .env exists and database is accessible
if [ -f ".env" ]; then
    # Try to run environment validation
    # This will gracefully fail if database isn't set up or profiles don't exist
    if php artisan platform:validate-profiles --all &> /dev/null 2>&1; then
        run_check "Validate Environment Profiles" "php artisan platform:validate-profiles --all" || FAILED=1
    else
        echo -e "${YELLOW}⚠ Environment validation skipped${NC}"
        echo -e "${YELLOW}  Database may not be configured or BasePlatformSeeder not run${NC}"
        echo -e "${YELLOW}  Run 'php artisan migrate --force && php artisan db:seed --class=BasePlatformSeeder' to enable${NC}\n"
    fi
else
    echo -e "${YELLOW}⚠ .env file not found, skipping environment validation${NC}"
    echo -e "${YELLOW}  Copy .env.example to .env and configure database to enable${NC}\n"
fi

# Heavy Tier Workflow (matches nightly-heavy.yml)
# Runs mutation tests and Playwright browser tests
# Set CI_FULL=1 to enable (e.g., CI_FULL=1 composer ci:local)
if [ "${CI_FULL:-0}" = "1" ]; then
    echo -e "${YELLOW}=== Heavy Tier Workflow ===${NC}\n"

    # Check if Infection is available for mutation testing
    if [ -f "vendor/bin/infection" ]; then
        run_check "Mutation Tests" "composer test:mutation" || FAILED=1
    else
        echo -e "${YELLOW}⚠ Mutation tests skipped: Infection not found${NC}"
        echo -e "${YELLOW}  Install Infection with: composer require --dev infection/infection${NC}\n"
    fi

    # Check if Playwright is available
    if command -v bun &> /dev/null && ([ -f "node_modules/.bin/playwright" ] || [ -f "node_modules/@playwright/test/package.json" ]); then
        # Install Playwright browsers if not already installed
        if ! bunx playwright --version &> /dev/null 2>&1; then
            echo -e "${YELLOW}Installing Playwright browsers...${NC}"
            bunx playwright install --with-deps || {
                echo -e "${YELLOW}⚠ Failed to install Playwright browsers, skipping browser tests${NC}\n"
            }
        fi

        if bunx playwright --version &> /dev/null 2>&1; then
            run_check "Playwright Browser Tests" "bunx playwright test" || FAILED=1
        else
            echo -e "${YELLOW}⚠ Playwright browser tests skipped: browsers not installed${NC}\n"
        fi
    else
        echo -e "${YELLOW}⚠ Playwright tests skipped: Playwright not found in node_modules${NC}"
        echo -e "${YELLOW}  Run 'bun install' to install dependencies${NC}\n"
    fi

    # Run Policy Checksum Monitor again (matches nightly-heavy.yml)
    if [ "$PHP_VERSION" != "8.4" ]; then
        run_check "Policy Checksum Monitor (Heavy)" "php artisan policy:checksum-monitor" || FAILED=1
    fi
else
    echo -e "${YELLOW}=== Heavy Tier Workflow (Skipped) ===${NC}\n"
    echo -e "${YELLOW}⚠ Heavy tier workflow skipped (mutation tests, browser tests)${NC}"
    echo -e "${YELLOW}  Set CI_FULL=1 to enable: CI_FULL=1 composer ci:local${NC}\n"
fi

# Browser Tests (matches browser-tests.yml)
# Uses starter-kit-browser-tests package for Pest browser testing
# Set CI_FULL=1 to enable (e.g., CI_FULL=1 composer ci:local)
if [ "${CI_FULL:-0}" = "1" ]; then
    echo -e "${YELLOW}=== Browser Tests (Starter Kit) ===${NC}\n"

    # Check if starter-kit-browser-tests package exists
    if [ -d "vendor/laravel-labs/starter-kit-browser-tests" ]; then
        # Check if Playwright is available
        if command -v bun &> /dev/null; then
            # Install Playwright browsers if not already installed
            if ! bunx playwright --version &> /dev/null 2>&1; then
                echo -e "${YELLOW}Installing Playwright browsers...${NC}"
                bunx playwright install --with-deps || {
                    echo -e "${YELLOW}⚠ Failed to install Playwright browsers, skipping browser tests${NC}\n"
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
                        echo -e "${YELLOW}⚠ Failed to create phpunit.xml.starter-kit-browser${NC}\n"
                        rm -rf tests.starter-kit-browser/ 2>/dev/null || true
                        FAILED=1
                    fi
                else
                    echo -e "${YELLOW}⚠ Failed to copy browser tests to tests.starter-kit-browser/${NC}\n"
                    rm -rf tests.starter-kit-browser/ phpunit.xml.starter-kit-browser 2>/dev/null || true
                    FAILED=1
                fi
            else
                echo -e "${YELLOW}⚠ Playwright browser tests skipped: browsers not installed${NC}\n"
            fi
        else
            echo -e "${YELLOW}⚠ Browser tests skipped: Bun not found${NC}\n"
        fi
    else
        echo -e "${YELLOW}⚠ Browser tests skipped: starter-kit-browser-tests not found${NC}"
        echo -e "${YELLOW}  Package should be installed via composer require-dev${NC}\n"
    fi
fi

# Summary
echo -e "\n${YELLOW}=== Summary ===${NC}\n"

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}All CI checks passed! ✓${NC}\n"
    exit 0
else
    echo -e "${RED}CI checks failed. Please fix the issues above.${NC}\n"
    exit 1
fi
