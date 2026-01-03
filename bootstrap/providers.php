<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\TenantPanelProvider;
use App\Providers\FolioServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\LivewireServiceProvider;
use App\Providers\MonadBladeServiceProvider;
use App\Providers\ResultServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    TenantPanelProvider::class,
    FolioServiceProvider::class,
    FortifyServiceProvider::class,
    LivewireServiceProvider::class,
    ResultServiceProvider::class,
    MonadBladeServiceProvider::class,
];
