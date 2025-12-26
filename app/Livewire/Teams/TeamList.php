<?php

declare(strict_types=1);

namespace App\Livewire\Teams;

use App\Models\Team;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

final class TeamList extends Component
{
    use WithPagination;

    /**
     * Get the teams to display.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getTeamsProperty()
    {
        return Team::query()
            ->inContext()
            ->latest()
            ->paginate(15);
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.teams.team-list', [
            'teams' => $this->teams,
        ]);
    }
}
