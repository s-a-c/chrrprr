<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Service for determining if a user is protected from deletion.
 *
 * Extracted from User model to improve testability and reduce model complexity.
 */
final readonly class UserProtectionService
{
    /**
     * Determine if the user is protected from deletion.
     *
     * A user is protected if they hold a key role (e.g., Executive, Deputy)
     * and are the only user with that role in that team context.
     *
     * Uses collection contains for functional approach.
     */
    public function isProtectable(User $user): bool
    {
        $userKeyRoles = $this->getUserKeyRoles($user);

        return $userKeyRoles->contains(function ($row) use ($user): bool {
            return $this->isUniqueRoleAssignment($user, $row);
        });
    }

    /**
     * Get all key roles held by this user, including their team context.
     *
     * @return Collection<int, object{role_id: int, team_id: int|null}>
     */
    private function getUserKeyRoles(User $user): Collection
    {
        $pivotTable = config('permission.table_names.model_has_roles');
        $rolesTable = config('permission.table_names.roles');
        $teamKey = config('permission.column_names.team_foreign_key');

        return $user
            ->getConnection()
            ->table($pivotTable)
            ->join($rolesTable, "{$pivotTable}.role_id", '=', "{$rolesTable}.id")
            ->where("{$pivotTable}.model_id", $user->getKey())
            ->where("{$pivotTable}.model_type", $user->getMorphClass())
            ->where("{$rolesTable}.is_key", true)
            ->select("{$rolesTable}.id as role_id", "{$pivotTable}.{$teamKey} as team_id")
            ->get();
    }

    /**
     * Check if the user is the only one with this role in this team context.
     */
    private function isUniqueRoleAssignment(User $user, object $row): bool
    {
        $pivotTable = config('permission.table_names.model_has_roles');
        $teamKey = config('permission.column_names.team_foreign_key');

        $count = $user
            ->getConnection()
            ->table($pivotTable)
            ->where('role_id', $row->role_id)
            ->where($teamKey, $row->team_id)
            ->count();

        return $count <= 1;
    }
}
