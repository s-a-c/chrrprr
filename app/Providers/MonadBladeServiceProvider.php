<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Result;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Monad Blade Service Provider.
 *
 * Provides Blade directives for working with Result monads in views.
 * Enables clean view rendering without if/else spaghetti.
 */
final class MonadBladeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // @success($result) ... @endsuccess
        Blade::if('success', static fn (mixed $result): bool => $result instanceof Result && $result->isSuccess);

        // @failure($result) ... @endfailure
        Blade::if('failure', static fn (mixed $result): bool => $result instanceof Result && $result->isFailure);

        // @audit($result) - Renders the Writer Monad logs for debugging
        Blade::directive('audit', static fn (string $expression): string => "<?php
                if (config('app.debug') && {$expression} instanceof \App\Support\Result) {
                    echo '<ul class=\"audit-trail\">';
                    foreach ({$expression}->logs as \$log) {
                        echo '<li>' . e(\$log) . '</li>';
                    }
                    echo '</ul>';
                }
            ?>");
    }
}
