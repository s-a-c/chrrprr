<?php

declare(strict_types=1);

use App\Models\User;

test('user bio can be set in different locales', function (): void {
    $user = User::factory()->create();

    $user->setTranslation('bio', 'en', 'English bio');
    $user->setTranslation('bio', 'fr', 'Bio en français');
    $user->save();

    expect($user->getTranslation('bio', 'en'))
        ->toBe('English bio')
        ->and($user->getTranslation('bio', 'fr'))
        ->toBe('Bio en français');
});

test('user bio returns default locale when translation missing', function (): void {
    $user = User::factory()->create();

    $user->setTranslation('bio', 'en', 'English bio');
    $user->save();

    // When fallback is false, returns empty string if translation missing
    $translation = $user->getTranslation('bio', 'fr', false);
    expect($translation)->toBeEmpty()->and($user->getTranslation('bio', 'fr', true))->toBe('English bio'); // Falls back to default
});

test('user bio can be retrieved in current locale', function (): void {
    $user = User::factory()->create();

    $user->setTranslation('bio', 'en', 'English bio');
    $user->setTranslation('bio', 'fr', 'Bio en français');
    $user->save();

    app()->setLocale('en');
    expect($user->bio)->toBe('English bio');

    app()->setLocale('fr');
    expect($user->bio)->toBe('Bio en français');
});

test('user bio attribute is in translatable array', function (): void {
    $user = new User();

    expect($user->translatable)->toContain('bio');
});
