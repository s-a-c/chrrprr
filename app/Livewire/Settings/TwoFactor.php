<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\View\View;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class TwoFactor extends Component
{
    /**
     * Indicates if the user is currently enabling two-factor authentication.
     */
    public bool $showModal = false;

    /**
     * Indicates if the verification step is being shown.
     */
    public bool $showVerificationStep = false;

    /**
     * The two-factor authentication confirmation code.
     */
    public string $code = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        abort_unless(Features::canManageTwoFactorAuthentication(), 403);

        $user = auth()->user();

        if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm') &&
            $user->two_factor_secret &&
            is_null($user->two_factor_confirmed_at)) {
            // Confirmation was abandoned, disable 2FA
            resolve(DisableTwoFactorAuthentication::class)($user);
        }
    }

    /**
     * Enable two-factor authentication for the user.
     */
    public function enable(EnableTwoFactorAuthentication $enable): void
    {
        $enable(auth()->user());

        $this->showModal = true;
        $this->showVerificationStep = false;
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(DisableTwoFactorAuthentication $disable): void
    {
        $disable(auth()->user());
    }

    /**
     * Show the verification step if necessary (e.g., for confirmation).
     */
    public function showVerificationIfNecessary(): void
    {
        if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm')) {
            $this->showVerificationStep = true;

            return;
        }

        $this->showModal = false;
    }

    /**
     * Confirm two-factor authentication for the user.
     */
    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirm): void
    {
        $this->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $confirm(auth()->user(), $this->code);

        $this->showModal = false;
        $this->showVerificationStep = false;
        $this->reset('code');
    }

    /**
     * Reset the verification step.
     */
    public function resetVerification(): void
    {
        $this->showVerificationStep = false;
        $this->reset('code');
    }

    /**
     * Handle closing the modal.
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->showVerificationStep = false;
        $this->reset('code');
    }

    /**
     * Get the two-factor authentication QR code SVG.
     */
    #[Computed]
    public function qrCodeSvg(): ?string
    {
        try {
            return auth()->user()->twoFactorQrCodeSvg();
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * Get the two-factor authentication manual setup key.
     */
    #[Computed]
    public function manualSetupKey(): ?string
    {
        try {
            return auth()->user()->twoFactorQrCodeUrl();
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * Determine if two-factor authentication is enabled.
     */
    #[Computed]
    public function twoFactorEnabled(): bool
    {
        return ! empty(auth()->user()->two_factor_secret) &&
               ! is_null(auth()->user()->two_factor_confirmed_at);
    }

    /**
     * Determine if two-factor authentication requires confirmation.
     */
    #[Computed]
    public function requiresConfirmation(): bool
    {
        return Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
    }

    /**
     * Get the configuration for the setup modal.
     *
     * @return array{title: string, description: string, buttonText: string}
     */
    #[Computed]
    public function modalConfig(): array
    {
        if ($this->showVerificationStep) {
            return [
                'title' => __('Verify Authentication'),
                'description' => __('Enter the 6-digit code from your authenticator app to complete setup.'),
                'buttonText' => __('Confirm'),
            ];
        }

        return [
            'title' => __('Setup Two Factor Authentication'),
            'description' => __('Scan this QR code using your preferred TOTP authenticator app (like Google Authenticator or 1Password).'),
            'buttonText' => __('I\'ve scanned the code'),
        ];
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.settings.two-factor', [
            'twoFactorEnabled' => $this->twoFactorEnabled,
            'requiresConfirmation' => $this->requiresConfirmation,
            'qrCodeSvg' => $this->qrCodeSvg,
            'manualSetupKey' => $this->manualSetupKey,
        ]);
    }
}
