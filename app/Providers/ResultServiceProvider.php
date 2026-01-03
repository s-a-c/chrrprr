<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\Telescope;
use Override;

/**
 * Result Service Provider.
 *
 * Provides infrastructure integration for the Result monad,
 * including Telescope integration for observability.
 */
final class ResultServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    #[Override]
    public function register(): void
    {
        // Result class methods are defined directly on the class
        // This provider can be extended for additional integrations
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Telescope watcher if Telescope is available
        if (class_exists(Telescope::class)) {
            $this->registerTelescopeWatcher();
        }
    }

    /**
     * Register Telescope watcher for Result objects.
     */
    private function registerTelescopeWatcher(): void
    {
        // Telescope integration can be added here
        // For now, the logInternal() method handles standard logging
        // Telescope-specific integration can be added in a future enhancement
    }
}
