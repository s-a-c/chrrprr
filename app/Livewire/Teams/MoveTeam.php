<?php

declare(strict_types=1);

namespace App\Livewire\Teams;

use App\Http\Requests\MoveTeamRequest;
use App\Livewire\Concerns\HandlesResults;
use App\Models\Team;
use App\Models\TeamMoveApproval;
use App\Services\TeamMove\TeamMoveRequestService;
use App\Support\Result;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Component;

final class MoveTeam extends Component
{
    use HandlesResults;

    public string $teamUlid = '';

    public ?string $parent_id = null;

    public string $reason = '';

    public TeamMoveApproval|Team|null $result = null;

    public function mount(string $ulid): void
    {
        $team = Team::query()->where('ulid', $ulid)->firstOrFail();
        $this->teamUlid = $ulid;
        $this->parent_id = $team->parent_id !== null ? (string) $team->parent_id : null;
    }

    /**
     * @throws ModelNotFoundException
     */
    public function submit(): void
    {
        $this->result = null;

        $rules = new MoveTeamRequest()->rules();
        $this->validate($rules);

        $team = Team::query()->where('ulid', $this->teamUlid)->firstOrFail();
        $service = resolve(TeamMoveRequestService::class);

        // Service now returns Result directly - no need for Result::try()
        $result = $service->requestMove(
            $team,
            $this->parent_id ? (int) $this->parent_id : null,
            auth()->user(),
            $this->reason !== null && $this->reason !== '' ? $this->reason : null,
        )->flatMap(function (TeamMoveApproval|Team $moveResult): Result {
            $this->result = $moveResult;

            $statusMessage = $moveResult instanceof TeamMoveApproval
                ? 'Team move request submitted for approval.'
                : 'Team moved successfully.';

            return Result::success($moveResult, [$statusMessage]);
        });

        // Handle the result using the trait method
        /** @var TeamMoveApproval|Team|null $moveResult */
        $moveResult = $this->handleResult(
            $result,
            $this->result instanceof TeamMoveApproval
                ? 'Team move request submitted for approval.'
                : 'Team moved successfully.'
        );

        if ($moveResult !== null) {
            $this->redirect(route('teams.index'));
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
