<?php

declare(strict_types=1);

namespace App\Livewire\Teams;

use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

final class TeamList extends Component
{
    use WithPagination;

    /**
     * Get the teams to display.
     */
    public function getTeamsProperty(): LengthAwarePaginator
    {
        return Team::query()
            ->inContext()
            ->latest()
            ->paginate(15);
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View|View
    {
        return view('livewire.teams.team-list', [
            'teams' => $this->teams,
        ]);
    }
}
