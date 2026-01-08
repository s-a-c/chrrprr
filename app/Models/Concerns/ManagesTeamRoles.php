<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\User;
use App\Support\Result;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use RuntimeException;

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
            // Use Result monad to handle RoleDoesNotExist as "error as value"
            $result = Result::try(
                static function () use ($user): ?User {
                    /** @var int|string $userId */
                    $userId = $user->getKey();

                    /** @var User|null */
                    return User::query()
                        ->role('executive')
                        ->where('id', '!=', $userId)
                        ->first();
                },
                ['Checking for existing executive']
            );

            // If RoleDoesNotExist was thrown, that's fine - no existing executive to check
            // Otherwise, check if we got a result (existing executive)
            $result->match(
                onSuccess: static function ($existing): void {
                    if ($existing !== null) {
                        throw ValidationException::withMessages([
                            'executive' => ['This team already has an executive assigned.'],
                        ]);
                    }
                },
                onFailure: static function (string $error, array $logs): void {
                    // Check if the exception was RoleDoesNotExist by examining logs
                    $isRoleDoesNotExist = false;
                    foreach ($logs as $log) {
                        if (! str_contains($log, 'RoleDoesNotExist')) {
                            continue;
                        }

                        $isRoleDoesNotExist = true;

                        break;
                    }

                    // Role doesn't exist yet in team context, so no existing executive to check
                    // This is fine - we can proceed with assignment
                    // The role will be assigned when assignRole is called
                    // Only ignore RoleDoesNotExist, re-throw other exceptions
                    throw_unless($isRoleDoesNotExist, RuntimeException::class, $error);
                }
            );

            $user->assignRole('executive');
        });
    }

    /**
     * Remove the executive from this team.
     *
     * Uses collection each for functional approach.
     */
    public function removeExecutive(): void
    {
        $this->withTeamContext(static function (): void {
            User::query()
                ->role('executive')
                ->get()
                ->each(static fn (User $exec) => $exec->removeRole('executive'));
        });
    }

    /**
     * Check if this team has an executive assigned.
     */
    public function hasExecutive(): bool
    {
        // Use Result monad to handle RoleDoesNotExist as "error as value"
        return $this->withTeamContext(static function (): bool {
            $result = Result::try(
                static fn (): bool => User::query()->role('executive')->exists(),
                ['Checking for executive existence']
            );

            /** @var bool */
            return $result->match(
                // @mago-expect Identity function in monadic context, not a boolean flag parameter
                onSuccess: static fn (mixed $exists, array $logs): bool => (bool) $exists,
                onFailure: /**
                 * @return false
                 */
                static function (string $error, array $logs): bool {
                    // Check if the exception was RoleDoesNotExist by examining logs
                    $isRoleDoesNotExist = false;
                    foreach ($logs as $log) {
                        if (! str_contains($log, 'RoleDoesNotExist')) {
                            continue;
                        }

                        $isRoleDoesNotExist = true;

                        break;
                    }

                    // Role doesn't exist yet, so no executive
                    // Only return false for RoleDoesNotExist, re-throw other exceptions
                    throw_unless($isRoleDoesNotExist, RuntimeException::class, $error);

                    return false;
                }
            );
        });
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
        // @mago-expect Closure executes query logic, not forwarding arguments - first-class callable syntax not applicable
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
     *
     * Unified approach eliminates code duplication between executive and deputy checks.
     */
    private function checkRoleConflict(User $user, string $role): void
    {
        $conflictingRole = $role === 'executive' ? 'deputy' : 'executive';

        // Use Result monad to handle RoleDoesNotExist as "error as value"
        // @mago-expect Closure captures variables from outer scope ($conflictingRole, $user)
        // Cannot use static without use() clause - non-static closure is appropriate here
        $result = Result::try(
            static function () use ($conflictingRole, $user): bool {
                /** @var int|string $userId */
                $userId = $user->getKey();

                /** @var bool */
                return User::query()->role($conflictingRole)->where('id', $userId)->exists();
            },
            ['Checking for role conflict']
        );

        /** @var bool $hasConflictingRole */
        $hasConflictingRole = $result->match(
            // @mago-expect Identity function in monadic context, not a boolean flag parameter
            onSuccess: static fn (mixed $exists, array $logs): bool => (bool) $exists,
            onFailure: /**
             * @return false
             */
            static function (string $error, array $logs): bool {
                // Check if the exception was RoleDoesNotExist by examining logs
                $isRoleDoesNotExist = false;
                foreach ($logs as $log) {
                    if (! str_contains($log, 'RoleDoesNotExist')) {
                        continue;
                    }

                    $isRoleDoesNotExist = true;

                    break;
                }

                // Role doesn't exist yet, so no conflict to check
                // This is expected behavior when roles haven't been created yet
                // Only return false for RoleDoesNotExist, re-throw other exceptions
                throw_unless($isRoleDoesNotExist, RuntimeException::class, $error);

                return false;
            }
        );

        if (! $hasConflictingRole) {
            return;
        }

        throw ValidationException::withMessages([
            $role => ['A user cannot be both executive and deputy of the same team.'],
        ]);
    }
}
