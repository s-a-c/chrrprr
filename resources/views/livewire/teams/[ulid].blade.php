<?php

declare(strict_types=1);

?>
<?php

use App\Http\Requests\UpdateTeamRequest;
use App\Models\Team;
use Livewire\Component;

new class extends Component {
    public string $teamUlid = '';

    public string $name = '';

    public string $typeLabel = '';

    public string $bio = '';

    public int $lockVersion = 0;

    public string $errorMessage = '';

    public ?string $parent_id = null;

    public function mount(string $ulid): void
    {
        $team = Team::query()->where('ulid', $ulid)->firstOrFail();

        $this->teamUlid = $ulid;
        $this->name = $team->getTranslation('name', app()->getLocale()) ?? '';
        $this->typeLabel = $team->type->label();
        $this->bio = $team->getTranslation('bio', app()->getLocale()) ?? '';
        $this->lockVersion = $team->lock_version;
        $this->parent_id = $team->parent_id !== null ? (string) $team->parent_id : null;
    }

    public function save(): void
    {
        $this->errorMessage = '';

        $rules = new UpdateTeamRequest()->rules();
        $this->validate($rules);

        try {
            $team = Team::query()->where('ulid', $this->teamUlid)->firstOrFail();
            $action = resolve(\App\Actions\Teams\UpdateTeam::class);

            $action->handle($team, [
                'lock_version' => max(0, $this->lockVersion),
                'parent_id' => $this->parent_id ? (int) $this->parent_id : null,
                'name' => $this->name,
                'bio' => $this->bio,
            ]);

            session()->flash('status', 'Team updated successfully.');
            $this->redirect(route('teams.index'));
        } catch (\App\Exceptions\OptimisticLockingException $e) {
            $this->errorMessage = $e->getMessage();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Let Livewire handle standard validation errors for fields
        } catch (\Exception $e) {
            $this->errorMessage = 'An error occurred: ' . $e->getMessage();
        }
    }

    /**
     * @psalm-return \Illuminate\Database\Eloquent\Collection<int, Team>
     */
    public function getParentsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        // Don't include the current team or its descendants in potential parents
        $currentTeam = Team::query()->where('ulid', $this->teamUlid)->first();

        if (!$currentTeam) {
            return Team::all();
        }

        return Team::all()->filter(static fn($team): bool => (int) $team->id !== (int) $currentTeam->id && !$team->isDescendantOf($currentTeam));
    }

    /**
     * Get the team model for bio display.
     */
    public function getTeamProperty(): ?Team
    {
        return Team::query()->where('ulid', $this->teamUlid)->first();
    }
}; ?>

<div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
    <flux:heading level="1">Edit Team: {{ $this->name }}</flux:heading>
    <flux:subheading>Update details for this team.</flux:subheading>

    @if ($errorMessage)
        <flux:callout variant="danger" class="mt-4">{{ $errorMessage }}</flux:callout>
    @endif

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:field>
            <flux:label>Team Name</flux:label>
            <flux:input wire:model="name" />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>Type</flux:label>
            <flux:input :value="$this->typeLabel" disabled />
            <flux:subheading>Type cannot be changed after creation.</flux:subheading>
        </flux:field>

        @if ($this->typeLabel !== 'enterprise')
            <flux:field>
                <flux:label>Parent Team</flux:label>
                <flux:select wire:model="parent_id">
                    @foreach ($this->parents as $parent)
                        <flux:select.option :value="(string) $parent->id">{{ $parent->name }}
                            ({{ $parent->type->value }})
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="parent_id" />
            </flux:field>
        @endif

        <flux:field>
            <flux:label>Bio (Markdown)</flux:label>
            <flux:textarea wire:model="bio" rows="5" />
            <flux:error name="bio" />
        </flux:field>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">Update Team</flux:button>
            <flux:button :href="route('teams.index')" variant="ghost">Cancel</flux:button>
        </div>
    </form>

    {{-- Bio Display Section --}}
    @if ($this->team)
        <div class="mt-12 border-t border-zinc-200 dark:border-zinc-700 pt-8">
            <flux:heading level="2">Biography</flux:heading>

            @if ($this->team->bio_html)
                <div
                    class="mt-4 prose prose-zinc dark:prose-invert max-w-none
                    prose-headings:text-zinc-900 dark:prose-headings:text-zinc-100
                    prose-a:text-blue-600 dark:prose-a:text-blue-400 hover:prose-a:text-blue-800 dark:hover:prose-a:text-blue-300
                    prose-code:text-zinc-900 dark:prose-code:text-zinc-100
                    prose-pre:bg-zinc-100 dark:prose-pre:bg-zinc-900 prose-pre:border prose-pre:border-zinc-300 dark:prose-pre:border-zinc-700
                ">
                    {!! $this->team->bio_html !!}
                </div>
            @else
                <flux:callout class="mt-4" variant="ghost">
                    This team has not written a biography yet.
                </flux:callout>
            @endif
        </div>
    @endif
</div>
