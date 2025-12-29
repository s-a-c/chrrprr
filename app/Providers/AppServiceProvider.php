<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Team;
use App\Observers\TeamObserver;
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
    public function register(): void {}

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
