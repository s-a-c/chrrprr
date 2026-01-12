<?php

declare(strict_types=1);

use function Laravel\Folio\name;

name('users.show');

/** @var App\Models\User $User */
?>

<div>
    <h1>{{ $User->name }}</h1>
    <p>{{ $User->email }}</p>
    <p>State: {{ $User->state->value }}</p>
    <p>Status: {{ $User->status->value }}</p>
</div>
