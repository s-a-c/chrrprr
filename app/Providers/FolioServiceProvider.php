<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
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
}
