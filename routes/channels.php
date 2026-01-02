<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{ulid}', fn (User $user, string $ulid): bool => $user->ulid === $ulid);
