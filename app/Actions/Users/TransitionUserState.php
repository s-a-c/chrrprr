<?php

declare(strict_types=1);

namespace App\Actions\Users;

use Spatie\QueueableAction\QueueableAction;

final class TransitionUserState
{
    use QueueableAction;
}
