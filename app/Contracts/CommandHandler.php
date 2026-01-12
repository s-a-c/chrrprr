<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Result;

/**
 * Command Handler Interface.
 *
 * Defines the contract for CQRS command handlers.
 * Commands modify state and return a Result monad that encapsulates
 * success/failure (Error monad) and audit logs (Writer monad).
 */
interface CommandHandler
{
    /**
     * Handle a command.
     *
     * @param  object  $command  The command object to handle
     * @return Result Encapsulates Success/Failure (Error Monad) and Audit Logs (Writer Monad)
     */
    public function handle(object $command): Result;
}
