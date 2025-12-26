<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class TwoFactor extends Component
{
    #[Locked]
    public bool $twoFactorEnabled;

    #[Locked]
    public bool $requiresConfirmation;

    public bool $showVerificationStep = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    public null|string $qrCodeSvg = null;

    public null|string $manualSetupKey = null;

    /**
     * Close the two-factor authentication modal.
     */
    public function closeModal(): void
    {
        $this->reset('code', 'manualSetupKey', 'qrCodeSvg', 'showModal', 'showVerificationStep');

        $this->resetErrorBag();

        if (!$this->requiresConfirmation) {
            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        }
    }
}
