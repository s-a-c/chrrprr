<div>
    <flux:heading level="1">Move Team</flux:heading>
    <flux:subheading>Move this team to a different parent in the hierarchy.</flux:subheading>

    @if ($errorMessage)
        <flux:callout variant="danger" class="mt-4">{{ $errorMessage }}</flux:callout>
    @endif

    <form wire:submit="submit" class="mt-6 space-y-6">
        <flux:field>
            <flux:label>New Parent Team</flux:label>
            <flux:select wire:model="parent_id">
                <flux:select.option value="">No Parent (Top Level)</flux:select.option>
                @foreach($this->parents as $parent)
                    <flux:select.option :value="$parent->id">{{ $parent->name }} ({{ $parent->type->value }})</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="parent_id" />
        </flux:field>

        <flux:field>
            <flux:label>Reason (Optional)</flux:label>
            <flux:textarea wire:model="reason" placeholder="Explain why this team should be moved..." rows="3" />
            <flux:error name="reason" />
            <flux:subheading>Required if approval is needed for this move.</flux:subheading>
        </flux:field>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">Request Move</flux:button>
            <flux:button :href="route('teams.index')" variant="ghost">Cancel</flux:button>
        </div>
    </form>
</div>
