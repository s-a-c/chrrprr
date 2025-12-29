<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Folio\Folio;
use Override;

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
        // Folio is now only used for non-Livewire pages
        // All Livewire SFCs have been moved to resources/views/livewire/ and are routed via routes/web.php
        // See docs/folio-livewire-sfc-integration/issue-analysis-remediation.md for details
        Folio::path(resource_path('views/pages'))->middleware([
            '*' => [
                'web',
            ],
        ]);
    }
}
