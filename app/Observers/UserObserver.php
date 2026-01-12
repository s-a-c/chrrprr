<?php

declare(strict_types=1);

namespace App\Observers;

use App\Exceptions\CannotDeleteKeyUserException;
use App\Models\User;

final class UserObserver
{
    /**
     * Handle the User "deleting" event.
     *
     * Prevents deletion of users who are executives or deputies of teams.
     */
    public function deleting(User $user): void
    {
        throw_if($user->isProtectable(), CannotDeleteKeyUserException::class);
    }
}
