<!-- @format -->

<?php

use App\Models\Team;
use Livewire\Component;

use function Laravel\Folio\name;

name('teams.index');
new class extends Component {
    /**
     * @psalm-return \Illuminate\Database\Eloquent\Collection<int, Team>
     */
    public function getTeamsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return Team::query()->inContext()->get();
    }
} ?>

    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="flex justify-between items-center bg-white p-6 rounded-lg shadow-sm dark:bg-zinc-900">
            <div>
                <flux:heading level="1">Teams</flux:heading>
                <flux:subheading>Manage your team hierarchy and biographies.</flux:subheading>
            </div>
            <flux:button :href="route('teams.create')" variant="primary">Create Team</flux:button>
        </div>

        <div class="mt-6">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach($this->teams as $team)
                    <flux:table.row :wire:key="$team->ulid">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar :name="$team->name" />
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $team->name }}</div>
                                    <div class="text-sm text-zinc-500">{{ $team->slug }}</div>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" variant="outline">{{ $team->type->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" variant="success">{{ $team->state->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button variant="ghost" size="sm" :href="route('teams.edit', ['ulid' => $team->ulid])">Edit</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
