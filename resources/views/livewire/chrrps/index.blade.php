<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $message = '';

    // In LW4, computed properties are accessed by their name directly in Blade
    /**
     * @return string[][]
     *
     * @psalm-return list{array{author: 'Jane Doe', message: 'Just deployed my first Laravel app! 🚀', time: '5 minutes ago'}, array{author: 'John Smith', message: 'Laravel makes web development fun again!', time: '1 hour ago'}, array{author: 'Alice Johnson', message: 'Working on something cool with Chrrprr...', time: '3 hours ago'}}
     */
    #[Computed]
    public function chrrps(): array
    {
        return [['author' => 'Jane Doe', 'message' => 'Just deployed my first Laravel app! 🚀', 'time' => '5 minutes ago'], ['author' => 'John Smith', 'message' => 'Laravel makes web development fun again!', 'time' => '1 hour ago'], ['author' => 'Alice Johnson', 'message' => 'Working on something cool with Chrrprr...', 'time' => '3 hours ago']];
    }

    public function store(): void
    {
        $this->validate(['message' => 'required|string|max:255']);
        session()->flash('status', 'Chrrp sent!');
        $this->message = '';
    }
}; ?>

<div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
    @if (session('status'))
        <flux:callout variant="success" class="mb-4">{{ session('status') }}</flux:callout>
    @endif

    <form wire:submit="store" class="space-y-4">
        <flux:field>
            <flux:label>What's on your mind?</flux:label>
            <flux:textarea wire:model="message" placeholder="What's on your mind?" />
        </flux:field>

        <flux:button type="submit" variant="primary">Chrrp</flux:button>
    </form>

    <div class="mt-6 bg-white shadow-sm rounded-lg divide-y dark:bg-zinc-900 dark:divide-zinc-800">
        @foreach ($this->chrrps as $chrrp)
            <div class="p-6" wire:key="{{ $loop->index }}">
                <div class="flex justify-between items-center">
                    <span class="text-zinc-800 font-medium dark:text-zinc-200">{{ $chrrp['author'] }}</span>
                    <small class="text-zinc-500">{{ $chrrp['time'] }}</small>
                </div>
                <p class="mt-4 text-lg text-zinc-900 dark:text-zinc-100">{{ $chrrp['message'] }}</p>
            </div>
        @endforeach
    </div>
</div>
