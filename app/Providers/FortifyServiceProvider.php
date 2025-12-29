<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\ConfirmPasswordViewResponse;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\LoginViewResponse;
use Laravel\Fortify\Contracts\RegisterViewResponse;
use Laravel\Fortify\Contracts\RequestPasswordResetLinkViewResponse;
use Laravel\Fortify\Contracts\ResetPasswordViewResponse;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Contracts\VerifyEmailViewResponse;
use Override;

final class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        $this->app->singleton(CreatesNewUsers::class, CreateNewUser::class);
        $this->app->singleton(ResetsUserPasswords::class, ResetUserPassword::class);

        $this->app->singleton(LoginViewResponse::class, static fn (): Responsable => new class implements LoginViewResponse
        {
            public function toResponse($request): Response
            {
                return response()->view('livewire.auth.login');
            }
        });

        $this->app->singleton(RegisterViewResponse::class, static fn (): Responsable => new class implements RegisterViewResponse
        {
            public function toResponse($request): Response
            {
                return response()->view('livewire.auth.register');
            }
        });

        $this->app->singleton(RequestPasswordResetLinkViewResponse::class, static fn (): Responsable => new class implements RequestPasswordResetLinkViewResponse
        {
            public function toResponse($request): Response
            {
                return response()->view('livewire.auth.forgot-password');
            }
        });

        $this->app->singleton(ResetPasswordViewResponse::class, static fn (): Responsable => new class implements ResetPasswordViewResponse
        {
            public function toResponse($request): Response
            {
                return response()->view('livewire.auth.reset-password', ['request' => $request]);
            }
        });

        $this->app->singleton(VerifyEmailViewResponse::class, static fn (): Responsable => new class implements VerifyEmailViewResponse
        {
            public function toResponse($request): Response
            {
                return response()->view('livewire.auth.verify-email');
            }
        });

        $this->app->singleton(ConfirmPasswordViewResponse::class, static fn (): Responsable => new class implements ConfirmPasswordViewResponse
        {
            public function toResponse($request): Response
            {
                return response()->view('livewire.auth.confirm-password');
            }
        });

        $this->app->singleton(TwoFactorLoginResponse::class, static fn (): Responsable => new class implements TwoFactorLoginResponse
        {
            public function toResponse($request): Response
            {
                return to_route('two-factor.login');
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', static fn (Request $request) => Limit::perMinute(5)->by($request->email.$request->ip()));

        RateLimiter::for('two-factor', static fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));
    }
}
