<div>
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($teams as $team)
                <flux:table.row wire:key="team-{{ $team->ulid }}">
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
                            <flux:button variant="ghost" size="sm" :href="route('teams.edit', ['ulid' => $team->ulid])">
                                Edit
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $teams->links() }}
    </div>
</div>
