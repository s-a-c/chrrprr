<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Laravel\Fortify\Features;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class TwoFactor extends Component
{
    public bool $showModal = false;

    public bool $showVerificationStep = false;

    public string $code = '';

    #[Locked]
    public ?string $qrCodeSvg = null;

    #[Locked]
    public ?string $manualSetupKey = null;

    public bool $twoFactorEnabled = false;

    public function mount(): void
    {
        // Check if two-factor authentication is enabled in Fortify
        abort_unless(Features::canManageTwoFactorAuthentication(), 403, 'Two-factor authentication is not enabled.');

        // If two-factor is enabled but not confirmed, disable it
        $user = auth()->user();
        if ($user->two_factor_secret && ! $user->two_factor_confirmed_at) {
            $user->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
            ])->save();
        }

        $this->twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();
    }

    public function getRequiresConfirmationProperty(): bool
    {
        return Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword');
    }

    public function updatedTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
    }

    public function getModalConfigProperty(): array
    {
        return [
            'title' => __('Enable Two Factor Authentication'),
            'description' => __('Scan this QR code with your authenticator app to enable two-factor authentication.'),
            'buttonText' => __('I\'ve scanned the QR code'),
        ];
    }

    public function enable(): void
    {
        auth()->user();

        try {
            // Make POST request to enable 2FA
            $response = Http::withCookies(request()->cookies->all(), config('session.domain'))
                ->post(url('/user/two-factor-authentication'));

            if ($response->successful()) {
                $this->loadQrCode();
                $this->loadManualSetupKey();
                $this->showModal = true;
            } else {
                $this->addError('setupData', __('Failed to enable two-factor authentication.'));
            }
        } catch (RequestException) {
            $this->addError('setupData', __('Failed to enable two-factor authentication.'));
        }
    }

    public function disable(): void
    {
        auth()->user();

        try {
            // Make DELETE request to disable 2FA
            $response = Http::withCookies(request()->cookies->all(), config('session.domain'))
                ->delete(url('/user/two-factor-authentication'));

            if ($response->successful()) {
                $this->showModal = false;
                $this->showVerificationStep = false;
                $this->reset(['code', 'qrCodeSvg', 'manualSetupKey']);
                $this->twoFactorEnabled = auth()->user()->fresh()->hasEnabledTwoFactorAuthentication();
            } else {
                $this->addError('setupData', __('Failed to disable two-factor authentication.'));
            }
        } catch (RequestException) {
            $this->addError('setupData', __('Failed to disable two-factor authentication.'));
        }
    }

    public function showVerificationIfNecessary(): void
    {
        if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm')) {
            $this->showVerificationStep = true;
        } else {
            $this->confirmTwoFactor();
        }
    }

    public function confirmTwoFactor(): void
    {
        $this->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        auth()->user();

        try {
            // Make POST request to confirm 2FA
            $response = Http::withCookies(request()->cookies->all(), config('session.domain'))
                ->post(url('/user/confirmed-two-factor-authentication'), [
                    'code' => $this->code,
                ]);

            if ($response->successful()) {
                $this->showModal = false;
                $this->showVerificationStep = false;
                $this->reset(['code', 'qrCodeSvg', 'manualSetupKey']);
                $this->twoFactorEnabled = auth()->user()->fresh()->hasEnabledTwoFactorAuthentication();
            } else {
                $this->addError('code', __('The provided two factor authentication code was invalid.'));
            }
        } catch (RequestException) {
            $this->addError('code', __('The provided two factor authentication code was invalid.'));
        }
    }

    public function resetVerification(): void
    {
        $this->showVerificationStep = false;
        $this->reset('code');
    }

    public function render(): Factory|View
    {
        return view('livewire.settings.two-factor');
    }

    private function loadQrCode(): void
    {
        try {
            $response = Http::withCookies(request()->cookies->all(), config('session.domain'))
                ->get(url('/user/two-factor-qr-code'));

            if ($response->successful()) {
                $data = $response->json();
                $this->qrCodeSvg = $data['svg'] ?? null;
            }
        } catch (RequestException) {
            // Silently fail - QR code will show loading state
        }
    }

    private function loadManualSetupKey(): void
    {
        $user = auth()->user();
        $this->manualSetupKey = decrypt($user->two_factor_secret);
    }
}
