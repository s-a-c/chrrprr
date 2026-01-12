<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Services\TeamMove\ApprovalDecisionEngine;
use App\Services\TeamMove\ApproverResolverFactory;
use App\Services\TeamMove\CrossOrganisationRule;
use App\Services\TeamMove\DepthChangeRule;
use App\Services\TeamMove\DescendantCountRule;
use App\Services\TeamMove\TeamMoveApprovalService;
use App\Services\TeamMove\TeamMoveRequestService;
use App\Services\TeamOrganisationFinderService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Override;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        // Check if a slug is already configured (via .env or cached config)
        /** @var string|null $slug */
        $slug = config('app.slug');
        if ($slug === null) {
            /** @var string $name */
            $name = config('app.name', 'Laravel');
            /** @var string $env */
            $env = config('app.env', 'production');

            // Logic: Append env unless production
            $source = $env === 'production' ? $name : "{$name} {$env}";

            // Set the config at runtime
            config(['app.slug' => Str::slug($source)]);
        }

        // Register TeamOrganisationFinderService as singleton
        $this->app->singleton(TeamOrganisationFinderService::class);

        // Register ApprovalDecisionEngine with rules
        $this->app->singleton(static function (Application $app): ApprovalDecisionEngine {
            $organisationFinder = $app->make(TeamOrganisationFinderService::class);
            $rules = [
                new DescendantCountRule(),
                new DepthChangeRule(),
                new CrossOrganisationRule($organisationFinder),
            ];

            return new ApprovalDecisionEngine($rules);
        });

        // Register ApproverResolverFactory
        $this->app->singleton(ApproverResolverFactory::class, static fn (Application $app): ApproverResolverFactory => new ApproverResolverFactory(
            $app->make(TeamOrganisationFinderService::class)
        ));

        // Register TeamMoveRequestService
        $this->app->singleton(TeamMoveRequestService::class);

        // Register TeamMoveApprovalService
        $this->app->singleton(TeamMoveApprovalService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureCarbon();
        $this->configureCommands();
        $this->configureModels();
        $this->configurePasswordRules();
        $this->configureRateLimiters();
        $this->configureUrl();
        $this->configureVite();
        $this->configureGate();
    }

    /**
     * Configure the application's carbon.
     */
    private function configureCarbon(): void
    {
        Date::use(CarbonImmutable::class);
    }

    /**
     * Configure the application's commands.
     */
    private function configureCommands(): void
    {
        /** @var bool $isProduction */
        $isProduction = $this->app->environment('production');

        DB::prohibitDestructiveCommands(
            $isProduction
            && ! $this->app->runningInConsole()
            && ! $this->app->runningUnitTests()
            && ! $this->app->isDownForMaintenance(),
        );
    }

    /**
     * Configure the application's models.
     */
    private function configureModels(): void
    {
        /** @var bool $isProduction */
        $isProduction = $this->app->environment('production');

        Model::shouldBeStrict(! $isProduction);
        Model::unguard(! $isProduction);
    }

    /**
     * Configure the application's password rules.
     */
    private function configurePasswordRules(): void
    {
        /** @var bool $isLocalOrTesting */
        $isLocalOrTesting = $this->app->environment(['local', 'testing']);
        if (! $isLocalOrTesting) {
            Password::defaults($this->getProductionPasswordRule(...));

            return;
        }

        Password::defaults($this->getLocalPasswordRule(...));
    }

    /**
     * Get the production password rule configuration.
     */
    private function getProductionPasswordRule(): Password
    {
        return Password::min(12)
            ->letters()
            ->numbers()
            ->symbols()
            ->mixedCase()
            ->uncompromised();
    }

    /**
     * Get the local development password rule configuration.
     */
    private function getLocalPasswordRule(): Password
    {
        return Password::min(8)
            ->letters()
            ->numbers()
            ->symbols()
            ->mixedCase();
    }

    /**
     * Configure the application's rate limiters.
     */
    private function configureRateLimiters(): void
    {
        RateLimiter::for('login', static fn (Request $request): Limit => Limit::perMinute(5)->by($request->input('email').$request->ip()));

        RateLimiter::for('two-factor', static fn (Request $request): Limit => Limit::perMinute(5)->by($request->session()->get('login.id')));
    }

    /**
     * Configure the application's url.
     */
    private function configureUrl(): void
    {
        /** @var bool $isLocal */
        $isLocal = $this->app->environment('local');
        if (! $isLocal) {
            URL::forceScheme('https');
        }
    }

    /**
     * Configure the application's vite.
     */
    private function configureVite(): void
    {
        Vite::useBuildDirectory('build')
            ->withEntryPoints([
                'resources/js/app.js',
            ]);
    }

    /**
     * Configure the application's gate.
     */
    private function configureGate(): void
    {
        // Allow users with key roles (e.g., Super Admin) to bypass all permission checks
        Gate::before(/**
         * @param  ?User  $user
         * @return null|true
         */
            static function ($user, string $ability): ?bool {
                if (! $user instanceof User) {
                    return null;
                }

                // Check if user has any key role assigned
                $hasKeyRole = $user->roles()
                    ->where('is_key', true)
                    ->exists();

                return $hasKeyRole ? true : null;
            });
    }
}
