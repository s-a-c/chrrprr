<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

final class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<array-key, mixed>  $input
     *
     * @throws InvalidArgumentException
     */
    public function reset(mixed $user, array $input): void
    {
        /** @var User $user */
        throw_unless($user instanceof User, InvalidArgumentException::class, 'User must be an instance of App\Models\User.');

        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => Hash::make(is_string($input['password']) ? $input['password'] : ''),
        ])->save();
    }
}
