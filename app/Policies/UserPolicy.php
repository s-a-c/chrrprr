<?php

declare(strict_types=1);

namespace App\Policies;

final class UserPolicy
{
    /**
     * Determine whether the user can create models.
     *
     * @return false
     */
    public function create(): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return false
     */
    public function update(): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return false
     */
    public function delete(): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return false
     */
    public function forceDelete(): bool
    {
        return false;
    }
}
