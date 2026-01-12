<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Component;

new class extends Component {
    public ?User $user = null;

    public function mount(string $ulid): void
    {
        $this->user = User::query()->where('ulid', $ulid)->firstOrFail();
    }
}; ?>

<div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
    @if ($this->user)
        <div class="bg-white rounded-lg shadow-sm dark:bg-zinc-900 p-6">
            <div class="flex items-center gap-4 mb-6">
                <div
                    class="w-16 h-16 rounded-full bg-zinc-200 dark:bg-zinc-800 flex items-center justify-center text-2xl font-semibold text-zinc-600 dark:text-zinc-400">
                    {{ $this->user->initials() }}
                </div>
                <div>
                    <flux:heading level="1">{{ $this->user->name }}</flux:heading>
                    <flux:subheading>{{ $this->user->email }}</flux:subheading>
                </div>
            </div>

            {{-- Bio Display Section --}}
            <div class="mt-8 border-t border-zinc-200 dark:border-zinc-700 pt-8">
                <flux:heading level="2">Biography</flux:heading>

                @if ($this->user->bio_html)
                    <div
                        class="mt-4 prose prose-zinc dark:prose-invert max-w-none
                        prose-headings:text-zinc-900 dark:prose-headings:text-zinc-100
                        prose-a:text-blue-600 dark:prose-a:text-blue-400 hover:prose-a:text-blue-800 dark:hover:prose-a:text-blue-300
                        prose-code:text-zinc-900 dark:prose-code:text-zinc-100
                        prose-pre:bg-zinc-100 dark:prose-pre:bg-zinc-900 prose-pre:border prose-pre:border-zinc-300 dark:prose-pre:border-zinc-700
                    ">
                        {!! $this->user->bio_html !!}
                    </div>
                @else
                    <flux:callout class="mt-4" variant="ghost">
                        This user has not written a biography yet.
                    </flux:callout>
                @endif
            </div>
        </div>
    @endif
</div>
