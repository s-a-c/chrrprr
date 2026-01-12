#!/bin/bash

# Wrapper script for mutation testing that filters out verbose intermediate failures
# ValidationException failures during mutation testing are actually "killed mutations" (successes)
# This script preserves the final mutation score summary while reducing noise

set -e

# Run mutation testing and capture all output
OUTPUT=$(PEST_MUTATE=1 php -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" -d memory_limit=1G vendor/bin/pest --mutate --everything --min=90 --covered-only --default-time-limit=10 2>&1) || EXIT_CODE=$?

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

# Exit with the original exit code
exit "${EXIT_CODE:-0}"
