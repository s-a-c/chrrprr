<?php

declare(strict_types=1);

namespace App\Livewire\Teams;

use App\Models\Team;
use Livewire\Component;

class Index extends Component
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Team>
     */
    public function getTeamsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return Team::query()->inContext()->get();
    }

    public function render()
    {
        return view('livewire.teams.index');
    }
}
