<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Folio\Folio;

final class FolioServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

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

        Folio::renderUsing(function (\Illuminate\Http\Request $request, \Laravel\Folio\Pipeline\MatchedView $matchedView) {
            $pagesPath = resource_path('views/pages');

            if (! str_starts_with($matchedView->path, $pagesPath)) {
                return null;
            }

            $relativePath = mb_ltrim(str_replace($pagesPath, '', $matchedView->path), DIRECTORY_SEPARATOR);
            $componentName = 'pages::'.str_replace([DIRECTORY_SEPARATOR, '.blade.php'], ['.', ''], $relativePath);

            if (app('livewire')->exists($componentName)) {
                return (app('livewire')->new($componentName))();
            }

            return null;
        });
    }
}
