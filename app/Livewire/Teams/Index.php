<?php

declare(strict_types=1);

namespace App\Livewire\Teams;

use App\Models\Team;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class Index extends Component
{
    /**
     * @return Collection<int, Team>
     */
    public function getTeamsProperty(): Collection
    {
        return Team::query()->inContext()->get();
    }

    public function render(): Factory|View
    {
        return view('livewire.teams.index');
    }
}
