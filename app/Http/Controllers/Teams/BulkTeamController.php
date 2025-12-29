<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\CreateTeam;
use App\Actions\Teams\UpdateTeam;
use App\Http\Requests\BulkTeamRequest;
use App\Models\Team;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

final readonly class BulkTeamController
{
    public function __construct(
        private CreateTeam $createTeamAction,
        private UpdateTeam $updateTeamAction,
    ) {}

    /**
     * Handle bulk team operations (create and update).
     */
    public function store(BulkTeamRequest $request): JsonResponse
    {
        $teams = $request->getTeams();
        $enterprise = $this->getEnterprise();

        // Validate batch size
        $maxBatchSize = $enterprise->bulk_operation_batch_size ?? 500;
        if (count($teams) > $maxBatchSize) {
            return response()->json([
                'success' => false,
                'error' => "Batch size exceeds maximum allowed ({$maxBatchSize}).",
            ], 422);
        }

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($teams as $index => $teamData) {
            try {
                $result = $this->processTeam($teamData, $index);
                $results[] = $result;
                $successCount++;
            } catch (Exception $e) {
                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'error' => $e->getMessage(),
                    'action' => isset($teamData['id']) ? 'update' : 'create',
                ];
                $failureCount++;
            }
        }

        $statusCode = match (true) {
            $failureCount === 0 => 200, // All succeeded
            $successCount === 0 => 422, // All failed
            default => 207, // Partial success (Multi-Status)
        };

        // success is true if there are any successes (partial or full)
        $success = $successCount > 0;

        return response()->json([
            'success' => $success,
            'total' => count($teams),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results,
        ], $statusCode);
    }

    /**
     * Process a single team (create or update).
     *
     * @param  array<string, mixed>  $teamData
     * @return array<string, mixed>
     */
    private function processTeam(array $teamData, int $index): array
    {
        if (isset($teamData['id'])) {
            // Update existing team
            $team = Team::query()->findOrFail($teamData['id']);
            $team = $this->updateTeamAction->handle($team, $teamData);

            return [
                'index' => $index,
                'success' => true,
                'team_id' => $team->id,
                'team_ulid' => $team->ulid,
                'action' => 'update',
            ];
        }

        // Create new team
        $team = $this->createTeamAction->handle($teamData);

        return [
            'index' => $index,
            'success' => true,
            'team_id' => $team->id,
            'team_ulid' => $team->ulid,
            'action' => 'create',
        ];
    }

    /**
     * Get the enterprise for the current user.
     */
    private function getEnterprise(): Team
    {
        $user = Auth::user();

        throw_if(! $user || ! $user->tenant_id, RuntimeException::class, 'User must have a tenant.');

        $enterprise = Team::query()->find($user->tenant_id);

        throw_unless($enterprise, RuntimeException::class, 'Enterprise not found.');

        return $enterprise;
    }
}
