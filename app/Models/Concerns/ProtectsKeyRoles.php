<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\CannotDeleteKeyUserException;

trait ProtectsKeyRoles
{
    public static function bootProtectsKeyRoles(): void
    {
        static::deleting(static function ($user): void {
            // Check protection using the model method
            if (method_exists($user, 'isProtectable') && $user->isProtectable()) {
                throw new CannotDeleteKeyUserException();
            }
        });
    }
}
