#!/usr/bin/env zsh

# Generate coverage and JUnit reports for Infection
# Using PCOV for performance

set -e

BANNER_TEXT="Generating Coverage for Infection"
BANNER_COLOR="1;35"

printf "\e[${BANNER_COLOR}m%s\e[0m\n" "================================================================================"
printf "\e[${BANNER_COLOR}m%s\e[0m\n" "  ${BANNER_TEXT}"
printf "\e[${BANNER_COLOR}m%s\e[0m\n" "================================================================================"

# Ensure tmp directory exists
mkdir -p tmp/infection

# Run Pest with coverage and junit flags
# We ignore deprecations to ensure a clean exit 0
php -d memory_limit=2G \
    -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" \
    -d xdebug.mode=off \
    -d pcov.enabled=1 \
    vendor/bin/pest \
    --coverage-xml=tmp/infection/coverage-xml \
    --log-junit=tmp/infection/junit.xml

printf "\e[32m%s\e[0m\n" "Coverage generated successfully in tmp/infection/"
