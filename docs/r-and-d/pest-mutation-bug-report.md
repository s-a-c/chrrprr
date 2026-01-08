# Mutation testing fails with "Psr\SimpleCache\CacheInterface cannot be resolved"

## Description

Pest mutation testing fails during the mutation analysis phase with a `ShouldNotHappen` exception when trying to resolve `Psr\SimpleCache\CacheInterface`.

## Environment

- **Pest version**: 4.3.1
- **PHP versions tested**: 8.4.16 and 8.5.1
- **Operating system**: macOS (Darwin)
- **Laravel version**: 12.x

## Steps to Reproduce

1. Run mutation testing with the `--everything --covered-only` flags:

   ```bash
   vendor/bin/pest --mutate --everything --min=90 --covered-only

   ```

## Expected Behavior

Mutation testing should complete and report the mutation score.

## Actual Behavior

Tests pass successfully (430 passed, 1569 assertions), but mutation analysis fails with:

```log
Pest\Exceptions\ShouldNotHappen

This should not happen - please create an new issue here: https://github.com/pestphp/pest/issues

Issue: A dependency with the name `Psr\SimpleCache\CacheInterface` cannot be resolved.
PHP version: 8.4.16
Operating system: Darwin

at vendor/pestphp/pest/src/Exceptions/ShouldNotHappen.php:37

```

## Additional Context

- This occurs consistently on both PHP 8.4 and PHP 8.5
- Regular test runs ([vendor/bin/pest](file:///Users/s-a-c/Herd/chrrprr/vendor/bin/pest)) work without issues
- The project uses Laravel 12 with standard PSR-4 autoloading
- `psr/simple-cache` is installed as a dependency of Laravel

## Composer dependencies (relevant)

```json
{
    "require": {
        "laravel/framework": "^12.45.1"
    },
    "require-dev": {
        "pestphp/pest": "^4.3.1",
        "pestphp/pest-plugin-arch": "^4.0",
        "pestphp/pest-plugin-browser": "^4.1.1",
        "pestphp/pest-plugin-faker": "^4.0",
        "pestphp/pest-plugin-laravel": "^4.0",
        "pestphp/pest-plugin-type-coverage": "^4.0.3"
    }
}

```

---
