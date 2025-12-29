<?php

declare(strict_types=1);

namespace App\Livewire\Teams;

use App\Http\Requests\MoveTeamRequest;
use App\Models\Team;
use App\Models\TeamMoveApproval;
use App\Services\TeamMoveService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

final class MoveTeam extends Component
{
    public string $teamUlid = '';

    public ?string $parent_id = null;

    public string $reason = '';

    public string $errorMessage = '';

    public TeamMoveApproval|Team|null $result = null;

    public function mount(string $ulid): void
    {
        $team = Team::query()->where('ulid', $ulid)->firstOrFail();
        $this->teamUlid = $ulid;
        $this->parent_id = $team->parent_id ? (string) $team->parent_id : null;
    }

    public function submit(): void
    {
        $this->errorMessage = '';
        $this->result = null;

        $rules = new MoveTeamRequest()->rules();
        $this->validate($rules);

        try {
            $team = Team::query()->where('ulid', $this->teamUlid)->firstOrFail();
            $service = resolve(TeamMoveService::class);

            $this->result = $service->requestMove(
                $team,
                $this->parent_id ? (int) $this->parent_id : null,
                auth()->user(),
                $this->reason !== null && $this->reason !== '' ? $this->reason : null,
            );

            $statusMessage = $this->result instanceof TeamMoveApproval
                ? 'Team move request submitted for approval.'
                : 'Team moved successfully.';

            session()->flash('status', $statusMessage);

            $this->redirect(route('teams.index'));
        } catch (ValidationException $e) {
            throw $e; // Let Livewire handle standard validation errors
        } catch (Exception $e) {
            $this->errorMessage = 'An error occurred: '.$e->getMessage();
        }
    }

    /**
     * @psalm-return Collection<int, Team>
     */
    public function getParentsProperty(): Collection
    {
        $currentTeam = Team::query()->where('ulid', $this->teamUlid)->first();

        if (! $currentTeam) {
            return Team::all();
        }

        // Don't include the current team or its descendants in potential parents
        return Team::all()->filter(
            static fn (Team $team): bool => (
                (int) $team->id !== (int) $currentTeam->id
                && ! $team->isDescendantOf($currentTeam)
            ),
        );
    }

    public function render(): \Illuminate\View\View|View
    {
        return view('livewire.teams.move-team');
    }
}
