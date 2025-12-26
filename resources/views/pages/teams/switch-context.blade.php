<?php

declare(strict_types=1);

use App\Models\Organisation;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Component;

new class extends Component {
    /**
     * The accessible organisations for the current user.
     *
     * @var Collection<int, Organisation>
     */
    public Collection $organisations;

    /**
     * The current organisational context ID.
     */
    public ?int $currentContextId = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->organisations = $user->accessibleOrganisations()->get();
        $this->currentContextId = $user->current_context_id;

        // Ensure we have a valid context if none set
        if (!$this->currentContextId && $this->organisations->isNotEmpty()) {
            $user->validateContext();
            $this->currentContextId = $user->current_context_id;
        }
    }

    /**
     * Switch the user's organisational context.
     */
    public function switchContext(int $organisationId): void
    {
        /** @var User $user */
        $user = auth()->user();
        $organisation = Organisation::find($organisationId);

        if ($organisation && $user->switchContext($organisation)) {
            $this->currentContextId = $organisation->id;
            $this->dispatch('context-switched', id: $organisationId);

            // Redirect to dashboard or refresh to apply scoping
            $this->redirectRoute('dashboard', navigate: true);
        }
    }

    /**
     * Get the current context name.
     */
    public function getCurrentContextName(): string
    {
        if (!$this->currentContextId) {
            return __('Select Context');
        }

        /** @var Organisation|null $current */
        $current = $this->organisations->firstWhere('id', $this->currentContextId);

        return $current ? $current->name : __('Unknown Context');
    }
};

?>

<div class="px-2 py-4">
    <flux:dropdown position="bottom" align="start" class="w-full">
        <flux:button variant="ghost" icon-trailing="chevrons-up-down" class="w-full justify-between">
            <div class="flex items-center gap-2 truncate">
                <flux:icon icon="building-office-2" variant="micro" />
                <span class="truncate">{{ $this->getCurrentContextName() }}</span>
            </div>
        </flux:button>

        <flux:menu class="w-[240px]">
            <flux:menu.radio.group wire:model="currentContextId">
                <flux:menu.heading>{{ __('Available Contexts') }}</flux:menu.heading>

                @foreach ($organisations as $org)
                    <flux:menu.item
                        wire:key="context-{{ $org->id }}"
                        wire:click="switchContext({{ $org->id }})"
                        :variant="$currentContextId === $org->id ? 'bullet' : 'none'"
                    >
                        {{ $org->name }}
                    </flux:menu.item>
                @endforeach
            </flux:menu.radio.group>
        </flux:menu>
    </flux:dropdown>
</div>
