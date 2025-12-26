<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\CreateTeam;
use App\Actions\Teams\UpdateTeam;
use App\Http\Requests\BulkTeamRequest;
use App\Models\Enterprise;
use App\Models\Team;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class BulkTeamController
{
    public function __construct(
        private readonly CreateTeam $createTeamAction,
        private readonly UpdateTeam $updateTeamAction,
    ) {}

    /**
     * Bulk create/update teams with partial success handling.
     */
    public function store(BulkTeamRequest $request): JsonResponse
    {
        $teams = $request->getTeams();
        $enterprise = $this->getCurrentEnterprise();

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
                if (isset($teamData['id'])) {
                    // Update existing team
                    $team = Team::query()->findOrFail($teamData['id']);
                    // Ensure lock_version is included if not provided
                    if (! isset($teamData['lock_version'])) {
                        $teamData['lock_version'] = $team->lock_version;
                    }
                    $updatedTeam = $this->updateTeamAction->handle($team, $teamData);

                    $results[] = [
                        'index' => $index,
                        'success' => true,
                        'team_id' => $updatedTeam->id,
                        'team_ulid' => $updatedTeam->ulid,
                        'action' => 'updated',
                    ];
                    $successCount++;
                } else {
                    // Create new team
                    $team = $this->createTeamAction->handle($teamData);

                    $results[] = [
                        'index' => $index,
                        'success' => true,
                        'team_id' => $team->id,
                        'team_ulid' => $team->ulid,
                        'action' => 'created',
                    ];
                    $successCount++;
                }
            } catch (ValidationException $e) {
                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'error' => $e->getMessage(),
                    'errors' => $e->errors(),
                ];
                $failureCount++;
            } catch (Exception $e) {
                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
                $failureCount++;
            }
        }

        $statusCode = $failureCount === 0 ? 200 : ($successCount > 0 ? 207 : 400); // 207 = Multi-Status (partial success)

        return response()->json([
            'success' => $successCount > 0,
            'total' => count($teams),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results,
        ], $statusCode);
    }

    /**
     * Get the current enterprise tenant.
     */
    private function getCurrentEnterprise(): Enterprise
    {
        $user = auth()->user();

        if ($user && $user->tenant_id) {
            $enterprise = Enterprise::query()->find($user->tenant_id);
            if ($enterprise) {
                return $enterprise;
            }
        }

        // Fallback: try to get from tenancy context
        if (tenancy()->initialized && tenancy()->tenant) {
            $tenantId = tenancy()->tenant->getKey();
            $enterprise = Enterprise::query()->find($tenantId);
            if ($enterprise) {
                return $enterprise;
            }
        }

        // Last resort: create a default enterprise or throw
        throw new RuntimeException('Unable to determine current enterprise tenant.');
    }
}
