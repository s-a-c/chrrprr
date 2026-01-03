<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Laravel\Folio\Folio;
use Laravel\Folio\Pipeline\MatchedView;
use Override;

/**
 * Folio Service Provider.
 *
 * Handles Folio page rendering, Livewire SFC integration, and Result monad support.
 * Automatically detects and renders Livewire SFCs, and handles Result objects in view data.
 */
final class FolioServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    #[Override]
    public function register(): void {}

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Folio::path(resource_path('views/pages'))->middleware([
            '*' => [
                'web',
            ],
        ]);

        Folio::renderUsing(static function (Request $request, MatchedView $matchedView) {
            $pagesPath = resource_path('views/pages');

            if (! str_starts_with($matchedView->path, $pagesPath)) {
                return null;
            }

            $relativePath = mb_ltrim(str_replace($pagesPath, '', $matchedView->path), DIRECTORY_SEPARATOR);
            $componentName = 'pages::'.str_replace([DIRECTORY_SEPARATOR, '.blade.php'], ['.', ''], $relativePath);

            // Check if this is a Livewire SFC
            if (resolve('livewire')->exists($componentName)) {
                return (resolve('livewire')->new($componentName))();
            }

            // For non-Livewire pages, check if view data contains Result objects
            // The Blade directives (@success, @failure, @audit) will handle them
            return null;
        });
    }
}
