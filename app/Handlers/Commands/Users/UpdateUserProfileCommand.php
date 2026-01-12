<?php

declare(strict_types=1);

namespace App\Handlers\Commands\Users;

use App\Models\User;

final
/**
 * Update User Profile Command.
 *
 * DTO for the UpdateUserProfile command.
 */
readonly class UpdateUserProfileCommand
{
    /**
     * @param  User  $user  The user to update
     * @param  array<string, mixed>  $data  The update data
     */
    public function __construct(
        public User $user,
        public array $data
    ) {}
}
