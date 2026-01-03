<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Result;

/**
 * Query Handler Interface.
 *
 * Defines the contract for CQRS query handlers.
 * Queries read data and return a Result monad that acts as an Option monad:
 * Success = Some (data found), Failure = None (data not found).
 */
interface QueryHandler
{
    /**
     * Ask a query.
     *
     * @param  object  $query  The query object to execute
     * @return Result Acts as an Option monad (Success = Some, Failure = None)
     */
    public function ask(object $query): Result;
}
