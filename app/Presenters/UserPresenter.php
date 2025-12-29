<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Models\User;
use Illuminate\Support\Str;
use Spatie\LaravelMarkdown\MarkdownRenderer;
use Stevebauman\Purify\Facades\Purify;

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
            ->map(fn (string $word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the bio HTML attribute (rendered from markdown).
     */
    public function bioHtml(User $user): ?string
    {
        $bio = $user->getTranslation('bio', app()->getLocale());

        if ($bio === null || $bio === '') {
            return null;
        }

        $html = resolve(MarkdownRenderer::class)->toHtml($bio);

        return Purify::clean($html);
    }
}
