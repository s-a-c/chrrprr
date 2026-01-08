<?php

declare(strict_types=1);

use App\Livewire\Settings\Password;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('password can be updated', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = Livewire::test(Password::class)
        ->set('current_password', 'password')
        ->set('password', $password = 'P@ssword123!')
        ->set('password_confirmation', $password)
        ->call('updatePassword');

    $response->assertHasNoErrors();

    expect(Hash::check($password, $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = Livewire::test(Password::class)
        ->set('current_password', 'wrong-password')
        ->set('password', $password = Str::password(16))
        ->set('password_confirmation', $password)
        ->call('updatePassword');

    $response->assertHasErrors(['current_password']);
});
