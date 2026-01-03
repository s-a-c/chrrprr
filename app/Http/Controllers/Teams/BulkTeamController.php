<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Handlers\Commands\Teams\CreateTeamCommand;
use App\Handlers\Commands\Teams\CreateTeamHandler;
use App\Handlers\Commands\Teams\UpdateTeamCommand;
use App\Handlers\Commands\Teams\UpdateTeamHandler;
use App\Http\Requests\BulkTeamRequest;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

final readonly class BulkTeamController
{
    public function __construct(
        private CreateTeamHandler $createTeamHandler,
        private UpdateTeamHandler $updateTeamHandler,
    ) {}

    /**
     * Handle bulk team operations (create and update).
     *
     * Uses collection pipeline with partition for functional approach.
     */
    public function store(BulkTeamRequest $request): JsonResponse
    {
        $teams = collect($request->getTeams());
        $enterprise = $this->getEnterprise();

        // Validate batch size
        /** @var int $maxBatchSize */
        $maxBatchSize = $enterprise->bulk_operation_batch_size ?? 500;
        if ($teams->count() > $maxBatchSize) {
            return response()->json([
                'success' => false,
                'error' => "Batch size exceeds maximum allowed ({$maxBatchSize}).",
            ], 422);
        }

        $results = $teams
            ->mapWithKeys(fn (array $teamData, int $index): array => [
                $index => $this->processTeamSafely($teamData, $index),
            ])
            ->values();

        $partitioned = $results->partition(
            static fn ($result): bool => ($result['success'] ?? false) === true
        );

        /** @var Collection<int, array<string, mixed>> $successes */
        $successes = $partitioned->get(0);
        /** @var Collection<int, array<string, mixed>> $failures */
        $failures = $partitioned->get(1);

        $successCount = $successes->count();
        $failureCount = $failures->count();
        $total = $teams->count();

        $statusCode = match (true) {
            $failureCount === 0 => 200, // All succeeded
            $successCount === 0 => 422, // All failed
            default => 207, // Partial success (Multi-Status)
        };

        return response()->json([
            'success' => $successCount > 0,
            'total' => $total,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results->all(),
        ], $statusCode);
    }

    /**
     * Process a single team safely using Result monad.
     *
     * @param  array<string, mixed>  $teamData
     * @return array<string, mixed>
     */
    private function processTeamSafely(array $teamData, int $index): array
    {
        return $this->processTeam($teamData, $index);
    }

    /**
     * Process a single team (create or update) using Result monad.
     *
     * @param  array<string, mixed>  $teamData
     * @return array<string, mixed>
     */
    private function processTeam(array $teamData, int $index): array
    {
        if (isset($teamData['id'])) {
            // Update existing team
            /** @var Team|null $team */
            $team = Team::query()->find($teamData['id']);
            if (! $team instanceof Team) {
                return [
                    'index' => $index,
                    'success' => false,
                    'error' => 'Team not found.',
                    'action' => 'update',
                ];
            }

            $command = new UpdateTeamCommand($team, $teamData);
            $result = $this->updateTeamHandler->handle($command);

            return $result->match(
                onSuccess: static fn (Team $updatedTeam): array => [
                    'index' => $index,
                    'success' => true,
                    'team_id' => $updatedTeam->id,
                    'team_ulid' => $updatedTeam->ulid,
                    'action' => 'update',
                ],
                onFailure: static fn (string $error): array => [
                    'index' => $index,
                    'success' => false,
                    'error' => $error,
                    'action' => 'update',
                ]
            );
        }

        // Create new team
        $command = new CreateTeamCommand($teamData);
        $result = $this->createTeamHandler->handle($command);

        return $result->match(
            onSuccess: static fn (Team $team): array => [
                'index' => $index,
                'success' => true,
                'team_id' => $team->id,
                'team_ulid' => $team->ulid,
                'action' => 'create',
            ],
            onFailure: static fn (string $error): array => [
                'index' => $index,
                'success' => false,
                'error' => $error,
                'action' => 'create',
            ]
        );
    }

    /**
     * Get the enterprise for the current user.
     *
     * @throws HttpException
     */
    private function getEnterprise(): Team
    {
        $user = Auth::user();

        abort_if(! $user || ! $user->tenant_id, 403, 'User must have a tenant.');

        /** @var Team|null $enterprise */
        $enterprise = Team::query()->find($user->tenant_id);

        abort_unless($enterprise instanceof Team, 404, 'Enterprise not found.');

        return $enterprise;
    }
}
