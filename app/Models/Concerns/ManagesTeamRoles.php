<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

trait ManagesTeamRoles
{
    /**
     * Validate executive/deputy constraints.
     *
     * Validates that:
     * - Executive and Deputy cannot be the same person
     * - Executive/Deputy must be from the same Enterprise as the team
     */
    public function validateExecutiveDeputyConstraints(User $user, string $role): void
    {
        // Validate same enterprise constraint
        if ($this->tenant_id && $user->tenant_id && $this->tenant_id !== $user->tenant_id) {
            throw ValidationException::withMessages([
                $role => ['The user must belong to the same enterprise as the team.'],
            ]);
        }

        $this->withTeamContext(function () use ($user, $role): void {
            $this->checkRoleConflict($user, $role);
        });
    }

    /**
     * Get the executive user for this team.
     */
    public function executive(): ?User
    {
        // Closure executes query logic, cannot use first-class callable syntax
        return $this->withTeamContext(static fn (): ?User => User::query()->role('executive')->first());
    }

    /**
     * Assign an executive to this team.
     */
    public function assignExecutive(User $user): void
    {
        $this->validateExecutiveDeputyConstraints($user, 'executive');

        $this->withTeamContext(static function () use ($user): void {
            $existing = User::query()
                ->role('executive')
                ->where('id', '!=', $user->id)
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'executive' => ['This team already has an executive assigned.'],
                ]);
            }

            $user->assignRole('executive');
        });
    }

    /**
     * Remove the executive from this team.
     */
    public function removeExecutive(): void
    {
        $this->withTeamContext(static function (): void {
            $executives = User::query()->role('executive')->get();
            foreach ($executives as $exec) {
                $exec->removeRole('executive');
            }
        });
    }

    /**
     * Check if this team has an executive assigned.
     */
    public function hasExecutive(): bool
    {
        // Closure executes query logic, cannot use first-class callable syntax
        return $this->withTeamContext(static fn (): bool => User::query()->role('executive')->exists());
    }

    /**
     * Assign a deputy to this team.
     */
    public function assignDeputy(User $user): void
    {
        $this->validateExecutiveDeputyConstraints($user, 'deputy');

        $this->withTeamContext(static function () use ($user): void {
            $user->assignRole('deputy');
        });
    }

    /**
     * Remove a deputy from this team.
     */
    public function removeDeputy(User $user): void
    {
        $this->withTeamContext(static function () use ($user): void {
            $user->removeRole('deputy');
        });
    }

    /**
     * Get all deputies for this team.
     */
    public function deputies(): Collection
    {
        // Closure executes query logic, cannot use first-class callable syntax
        return $this->withTeamContext(static fn (): Collection => User::query()->role('deputy')->get());
    }

    /**
     * Execute a callback within the team's permission context.
     */
    protected function withTeamContext(callable $callback): mixed
    {
        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($this->id);

        try {
            return $callback();
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }

    /**
     * Check for role conflicts between executive and deputy.
     */
    private function checkRoleConflict(User $user, string $role): void
    {
        $conflictingRole = $role === 'executive' ? 'deputy' : 'executive';

        try {
            if ($role === 'executive') {
                $conflictingIds = User::query()->role($conflictingRole)->pluck('id');
                if ($conflictingIds->contains($user->id)) {
                    throw ValidationException::withMessages([
                        'executive' => ['A user cannot be both executive and deputy of the same team.'],
                    ]);
                }

                return;
            }

            $conflicting = User::query()->role($conflictingRole)->first();
            if ($conflicting && $conflicting->id === $user->id) {
                throw ValidationException::withMessages([
                    'deputy' => ['A user cannot be both executive and deputy of the same team.'],
                ]);
            }
        } catch (RoleDoesNotExist $e) {
            // Role doesn't exist yet, so no conflict to check
            // This is expected behavior when roles haven't been created yet
            unset($e); // No action needed - the absence of a role means no conflict exists
        }
    }
}
