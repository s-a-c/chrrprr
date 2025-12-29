# Code Reference - Folio & Livewire 4 SFC Integration

---

<details><summary>Table of Contents</summary>

## Table of Contents

- [Code Reference - Folio \& Livewire 4 SFC Integration](#code-reference---folio--livewire-4-sfc-integration)
  - [Table of Contents](#table-of-contents)
  - [1. The Global Bridge](#1-the-global-bridge)
    - [1.1. app/Providers/FolioServiceProvider.php](#11-appprovidersfolioserviceproviderphp)
  - [2. The Unified Layout](#2-the-unified-layout)
    - [2.1. resources/views/layouts/app.blade.php](#21-resourcesviewslayoutsappbladephp)
  - [3. The Clean SFC](#3-the-clean-sfc)
    - [3.1. resources/views/pages/chrrps.blade.php](#31-resourcesviewspageschrrpsbladephp)
  - [4. Alias Registration](#4-alias-registration)
    - [4.1. app/Providers/AppServiceProvider.php](#41-appprovidersappserviceproviderphp)
  - [5. Main Dashboard Update](#5-main-dashboard-update)
    - [5.1. resources/views/dashboard.blade.php](#51-resourcesviewsdashboardbladephp)

</details>


This document contains the full source code for the files modified to achieve the seamless integration of Laravel Folio and Livewire 4 Single-File Components.

---

## 1. The Global Bridge

### 1.1. [app/Providers/FolioServiceProvider.php](../../app/Providers/FolioServiceProvider.php)

This service provider contains the crucial `Folio::renderUsing` callback that detects Livewire SFCs and boots them correctly.

```php
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

```

---

## 2. The Unified Layout

### 2.1. [resources/views/layouts/app.blade.php](../../resources/views/layouts/app.blade.php)

The standard Livewire layout updated with the Flux UI sidebar and navigation system.

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Platform')" class="grid">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                {{ __('Repository') }}
                </flux:navlist.item>

                <flux:navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                {{ __('Documentation') }}
                </flux:navlist.item>
            </flux:navlist>

            <!-- Desktop User Menu -->
            @auth
                <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                    <flux:profile
                        :name="auth()->user()->name"
                        :initials="auth()->user()->initials()"
                        icon:trailing="chevrons-up-down"
                    />

                    <flux:menu class="w-[220px]">
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                        <span
                                            class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                        >
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    </span>

                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                        <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            @endauth
        </flux:sidebar>

        <!-- Mobile User Menu -->
        @auth
            <flux:header class="lg:hidden">
                <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

                <flux:spacer />

                <flux:dropdown position="top" align="end">
                    <flux:profile
                        :initials="auth()->user()->initials()"
                        icon-trailing="chevron-down"
                    />

                    <flux:menu>
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                        <span
                                            class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                        >
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    </span>

                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                        <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </flux:header>
        @endauth

        <flux:main>
            {{ $slot }}
        </flux:main>

        @fluxScripts
    </body>
</html>

```

---

## 3. The Clean SFC

### 3.1. [resources/views/pages/chrrps.blade.php](../../resources/views/pages/chrrps.blade.php)

The "Chrrps" page implemented as a pure Livewire 4 SFC, automatically booting via Folio and using the unified layout.

```blade
<?php

use function Laravel\Folio\name;
use Livewire\Component;

name('chrrps.index');

new class extends Component {
    public string $message = '';

    public function chrrps(): array
    {
        return [
            [
                'author' => 'Jane Doe',
                'message' => 'Just deployed my first Laravel app! 🚀',
                'time' => '5 minutes ago',
            ],
            [
                'author' => 'John Smith',
                'message' => 'Laravel makes web development fun again!',
                'time' => '1 hour ago',
            ],
            [
                'author' => 'Alice Johnson',
                'message' => 'Working on something cool with Chrrprr...',
                'time' => '3 hours ago'
            ],
        ];
    }

    public function store()
    {
        $this->validate(['message' => 'required|string|max:255']);
        session()->flash('status', 'Chrrp sent!');
        $this->message = '';
    }
}
?>

<div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
    <form wire:submit="store" class="space-y-4">
        <flux:textarea
            wire:model="message"
            placeholder="What's on your mind?"
        />

        <flux:button type="submit" variant="primary">Chrrp</flux:button>
    </form>

    <div class="mt-6 bg-white shadow-sm rounded-lg divide-y dark:bg-zinc-900 dark:divide-zinc-800">
        @foreach($this->chrrps() as $chrrp)
            <div class="p-6" wire:key="{{ $loop->index }}">
                <div class="flex justify-between items-center">
                    <span class="text-zinc-800 font-medium dark:text-zinc-200">{{ $chrrp['author'] }}</span>
                    <small class="text-zinc-500">{{ $chrrp['time'] }}</small>
                </div>
                <p class="mt-4 text-lg text-zinc-900 dark:text-zinc-100">{{ $chrrp['message'] }}</p>
            </div>
        @endforeach
    </div>
</div>

```

---

## 4. Alias Registration

### 4.1. [app/Providers/AppServiceProvider.php](../../app/Providers/AppServiceProvider.php)

Registering the `app-layout` alias to point to the new unified `layouts.app`.

```php
<?php

declare(strict_types=1);

namespace App\Providers;

// ... imports ...

final class AppServiceProvider extends ServiceProvider
{
    // ...

    public function boot(): void
    {
        $this->configureCarbon();
        $this->configureCommands();
        $this->configureModels();
        $this->configurePasswordRules();
        $this->configureUrl();
        $this->configureVite();

        Blade::component('layouts.app', 'app-layout');
    }

    // ...
}

```

---

## 5. Main Dashboard Update

### 5.1. [resources/views/dashboard.blade.php](../../resources/views/dashboard.blade.php)

Updating the main dashboard to use the unified `x-app-layout`.

```blade
<x-app-layout :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
        </div>
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
        </div>
    </div>
</x-app-layout>

```

---
