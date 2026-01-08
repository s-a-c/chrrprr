#!/bin/bash

# Mutation testing script using PHP 8.4 (to avoid PHP 8.5 Pest bugs)
# This script regenerates the autoloader with platform check disabled,
# runs mutation tests, then restores the autoloader for PHP 8.5

set -e

PHP84="/Users/s-a-c/Library/Application Support/Herd/bin/php84"
PHP85="/Users/s-a-c/Library/Application Support/Herd/bin/php85"
COMPOSER="/Users/s-a-c/Library/Application Support/Herd/bin/composer"

printf "\033[1;93m[Mutation Test PHP84]\033[0m Regenerating autoloader for PHP 8.4...\n"
"${PHP84}" "${COMPOSER}" dump-autoload --ignore-platform-req=php --quiet

printf "\033[1;93m[Mutation Test PHP84]\033[0m Running mutation tests with PHP 8.4...\n"

# Run mutation testing
OUTPUT=$("${PHP84}" -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" -d memory_limit=1G vendor/bin/pest --mutate --everything --min=90 --covered-only --default-time-limit=10 2>&1) || EXIT_CODE=$?

# Process output to filter verbose intermediate failures while keeping important info
# ValidationException failures = killed mutations (successes), so we filter the verbose stack traces
FILTERED_OUTPUT=$(printf "%s" "${OUTPUT}" | awk '
    BEGIN {
        in_validation_exception=0
        validation_count=0
    }
    # Mark start of ValidationException - this is a KILLED MUTATION (success!)
    /ValidationException/ && /FAILED/ {
        in_validation_exception=1
        validation_count++
        # Show test name with note that this is a killed mutation
        sub(/FAILED/, "KILLED MUTATION (success)")
        print
        next
    }
    # Skip everything in ValidationException block until we hit the test summary
    in_validation_exception {
        # Stop skipping when we hit test summary or new section
        if (/^Tests:|^Duration:|^Parallel:|^  [A-Z]/ && !/ValidationException/) {
            in_validation_exception=0
        } else {
            next
        }
    }
    # Always print everything else
    {
        print
    }
    END {
        if (validation_count > 0) {
            print "\n" "Note: " validation_count " ValidationException(s) shown above are KILLED MUTATIONS (successes)."
            print "These mutations were correctly detected by your tests and count toward your mutation score."
        }
    }
')

# Show filtered output
printf "%s\n" "${FILTERED_OUTPUT}"

# Restore autoloader for PHP 8.5
printf "\033[1;93m[Mutation Test PHP84]\033[0m Restoring autoloader for PHP 8.5...\n"
"${PHP85}" "${COMPOSER}" dump-autoload --quiet

exit "${EXIT_CODE:-0}"
