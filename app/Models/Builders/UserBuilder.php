<?php

declare(strict_types=1);

namespace App\Models\Builders;

use App\Enums\UserState;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * Custom builder for User model with complex query methods.
 *
 * @template TModelClass of \App\Models\User
 *
 * @extends Builder<TModelClass>
 */
class UserBuilder extends Builder
{
    /**
     * Scope the query to active users.
     */
    public function active(): self
    {
        return $this->where('state', UserState::ACTIVE);
    }

    /**
     * Scope the query to inactive users.
     */
    public function inactive(): self
    {
        return $this->where('state', UserState::INACTIVE);
    }

    /**
     * Scope the query to pending users.
     */
    public function pending(): self
    {
        return $this->where('state', UserState::PENDING);
    }

    /**
     * Scope the query to users with a specific status.
     */
    public function withStatus(UserStatus $status): self
    {
        return $this->where('status', $status);
    }

    /**
     * Scope the query to online users.
     */
    public function online(): self
    {
        return $this->where('status', UserStatus::ONLINE);
    }

    /**
     * Scope the query to offline users.
     */
    public function offline(): self
    {
        return $this->where('status', UserStatus::OFFLINE);
    }

    /**
     * Scope the query to users with a specific role.
     *
     * @psalm-return static<TModelClass>
     */
    public function withRole(string $role): static
    {
        return $this->whereHas('roles', static function (Builder $query) use ($role): void {
            $query->where('name', $role);
        });
    }

    /**
     * Scope the query to onboarded users (users who have completed onboarding).
     * For now, this means users who are not in pending state.
     */
    public function onboarded(): self
    {
        return $this->where('state', '!=', UserState::PENDING);
    }
}
