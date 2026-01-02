<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
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

        $this->app->singleton(LoginViewResponse::class, static fn (): object => new class implements LoginViewResponse
        {
            #[Override]
            public function toResponse($request): Response
            {
                return response()->view('livewire.auth.login');
            }
        });

        $this->app->singleton(RegisterViewResponse::class, static fn (): object => new class implements RegisterViewResponse
        {
            #[Override]
            public function toResponse($request): Response
            {
                return response()->view('livewire.auth.register');
            }
        });

        $this->app->singleton(RequestPasswordResetLinkViewResponse::class, static fn (): object => new class implements RequestPasswordResetLinkViewResponse
        {
            #[Override]
            public function toResponse($request): Response
            {
                return response()->view('livewire.auth.forgot-password');
            }
        });

        $this->app->singleton(ResetPasswordViewResponse::class, static fn (): object => new class implements ResetPasswordViewResponse
        {
            #[Override]
            public function toResponse($request): Response
            {
                return response()->view('livewire.auth.reset-password', ['request' => $request]);
            }
        });

        $this->app->singleton(VerifyEmailViewResponse::class, static fn (): object => new class implements VerifyEmailViewResponse
        {
            #[Override]
            public function toResponse($request): Response
            {
                return response()->view('livewire.auth.verify-email');
            }
        });

        $this->app->singleton(ConfirmPasswordViewResponse::class, static fn (): object => new class implements ConfirmPasswordViewResponse
        {
            #[Override]
            public function toResponse($request): Response
            {
                return response()->view('livewire.auth.confirm-password');
            }
        });

        $this->app->singleton(TwoFactorLoginResponse::class, static fn (): object => new class implements TwoFactorLoginResponse
        {
            #[Override]
            public function toResponse($request): RedirectResponse
            {
                return to_route('two-factor.login');
            }
        });
    }
}
