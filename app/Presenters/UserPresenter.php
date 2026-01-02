<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Presenter for User model UI-related methods.
 *
 * Extracts presentation logic from the User model.
 */
final readonly class UserPresenter
{
    /**
     * Get the user's initials using collection pipeline.
     */
    public function initials(User $user): string
    {
        return Str::of($user->name)
            ->explode(' ')
            ->take(2)
            ->map(static fn (string $word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
