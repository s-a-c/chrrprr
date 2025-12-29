<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Team;
use App\Observers\TeamObserver;
use App\Services\TeamMove\ApprovalDecisionEngine;
use App\Services\TeamMove\ApproverResolverFactory;
use App\Services\TeamMove\CrossOrganisationRule;
use App\Services\TeamMove\DepthChangeRule;
use App\Services\TeamMove\DescendantCountRule;
use App\Services\TeamMove\TeamMoveApprovalService;
use App\Services\TeamMove\TeamMoveRequestService;
use App\Services\TeamOrganisationFinderService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Override;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        // Register TeamOrganisationFinderService as singleton
        $this->app->singleton(TeamOrganisationFinderService::class);

        // Register ApprovalDecisionEngine with rules
        $this->app->singleton(static function ($app): ApprovalDecisionEngine {
            $organisationFinder = $app->make(TeamOrganisationFinderService::class);
            $rules = [
                new DescendantCountRule(),
                new DepthChangeRule(),
                new CrossOrganisationRule($organisationFinder),
            ];

            return new ApprovalDecisionEngine($rules);
        });

        // Register ApproverResolverFactory
        $this->app->singleton(ApproverResolverFactory::class, static fn ($app): ApproverResolverFactory => new ApproverResolverFactory(
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
        Team::observe(TeamObserver::class);

        // Allow Super Admin role to bypass all permission and policy checks
        Gate::before(static function (?Authenticatable $user, string $ability): ?bool {
            if ($user && method_exists($user, 'hasRole')) {
                // Check for Super Admin role in global context (team_id = 0)
                $previousTeamId = getPermissionsTeamId();
                setPermissionsTeamId(0);
                $hasSuperAdmin = $user->hasRole('Super Admin');
                setPermissionsTeamId($previousTeamId);

                if ($hasSuperAdmin) {
                    return true;
                }
            }

            return null; // Let other gates/policies handle the check
        });
    }
}
