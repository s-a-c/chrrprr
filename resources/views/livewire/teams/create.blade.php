<?php

declare(strict_types=1);

use App\Enums\TeamType;
use App\Http\Requests\StoreTeamRequest;
use App\Models\Team;
use Livewire\Component;

new class extends Component {
    public string $name = '';

    public string $type = '';

    public ?string $parent_id = null;

    public string $bio = '';

    public string $errorMessage = '';

    public function mount(): void
    {
        // Default to Organisation if not specified
        $this->type = TeamType::ORGANISATION->value;
    }

    public function save(): void
    {
        $this->errorMessage = '';

        $rules = new StoreTeamRequest()->rules();
        $this->validate($rules);

        try {
            $action = resolve(\App\Actions\Teams\CreateTeam::class);

            $action->handle([
                'name' => $this->name,
                'type' => $this->type,
                'parent_id' => $this->parent_id ? (int) $this->parent_id : null,
                'bio' => $this->bio,
            ]);

            session()->flash('status', 'Team created successfully.');
            $this->redirect(route('teams.index'));
        } catch (\Exception $e) {
            $this->errorMessage = 'An error occurred while creating the team: ' . $e->getMessage();
        }
    }

    /**
     * @psalm-return \Illuminate\Database\Eloquent\Collection<int, Team>
     */
    public function getParentsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return Team::all(); // Simplified for MVP hierarchy selection
    }
}; ?>

<div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
    <flux:heading level="1">Create New Team</flux:heading>
    <flux:subheading>Define a new team within the hierarchy.</flux:subheading>

    @if (session('status'))
        <flux:callout variant="success" class="mt-4">{{ session('status') }}</flux:callout>
    @endif

    @if (!empty($errorMessage))
        <flux:callout variant="danger" class="mt-4">{{ $errorMessage }}</flux:callout>
    @endif

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:field>
            <flux:label>Team Name</flux:label>
            <flux:input wire:model="name" placeholder="Enter team name" />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>Type</flux:label>
            <flux:select wire:model="type">
                @foreach (TeamType::cases() as $type)
                    <flux:select.option :value="$type->value">{{ $type->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="type" />
        </flux:field>

        <flux:field>
            <flux:label>Parent Team</flux:label>
            <flux:select wire:model="parent_id">
                <flux:select.option value="">No Parent (Enterprise)</flux:select.option>
                @foreach ($this->parents as $parent)
                    <flux:select.option :value="$parent->id">{{ $parent->name }} ({{ $parent->type->value }})
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="parent_id" />
        </flux:field>

        <flux:field>
            <flux:label>Bio (Markdown)</flux:label>
            <flux:textarea wire:model="bio" placeholder="Describe the team..." rows="5" />
            <flux:error name="bio" />
        </flux:field>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">Create Team</flux:button>
            <flux:button :href="route('teams.index')" variant="ghost">Cancel</flux:button>
        </div>
    </form>
</div>
