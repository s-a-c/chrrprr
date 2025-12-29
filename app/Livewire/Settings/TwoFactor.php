<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Livewire\Component;

final class TwoFactor extends Component
{
    public bool $twoFactorEnabled = false;

    public bool $requiresConfirmation = false;

    public bool $showModal = false;

    public bool $showVerificationStep = false;

    public string $code = '';

    public string $qrCodeSvg = '';

    public string $manualSetupKey = '';

    /** @var array<string, string> */
    public array $modalConfig = [
        'title' => '',
        'description' => '',
        'buttonText' => '',
    ];

    public function mount(): void
    {
        // Check if 2FA is enabled - if not, abort with 403
        abort_unless(Features::canManageTwoFactorAuthentication(), 403);

        $user = auth()->user();

        if ($user) {
            // If 2FA was started but never confirmed, clear the partial data
            if ($user->two_factor_secret && ! $user->two_factor_confirmed_at) {
                $user->forceFill([
                    'two_factor_secret' => null,
                    'two_factor_recovery_codes' => null,
                ])->save();
            }

            $this->twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword');
        }
    }

    public function enable(EnableTwoFactorAuthentication $enable): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $enable($user);

        $this->twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();
    }

    public function disable(DisableTwoFactorAuthentication $disable): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $disable($user);

        $this->twoFactorEnabled = false;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->showVerificationStep = false;
        $this->code = '';
    }

    public function resetVerification(): void
    {
        $this->showVerificationStep = false;
        $this->code = '';
    }

    public function confirmTwoFactor(): void
    {
        // Implementation for confirming two-factor setup
    }

    public function showVerificationIfNecessary(): void
    {
        // Implementation for showing verification step if needed
    }
}
