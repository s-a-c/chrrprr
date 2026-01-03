<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Support\Result;

/**
 * Base Handler for CQRS operations.
 *
 * Provides common helper methods for Command and Query handlers.
 * Standardizes Option monad behavior (404 handling) and Error monad
 * behavior (business rule validation).
 */
abstract class BaseHandler
{
    /**
     * Standardizes the 'Option' monad behavior for missing resources.
     *
     * Converts a model lookup into a Result. If the model exists,
     * returns Success with the model. If null, returns Failure
     * (representing "None" in Option monad terms).
     *
     * @param  mixed  $model  The model instance or null
     * @param  string  $message  Custom error message if not found
     * @return Result Success with model, or Failure if not found
     */
    protected function ensureFound(mixed $model, string $message = 'Resource not found'): Result
    {
        if ($model === null) {
            return Result::failure($message, ['Lookup failed']);
        }

        $className = get_debug_type($model);

        return Result::success($model, ["Found {$className}"]);
    }

    /**
     * Standardizes the 'Error' monad behavior for business constraints.
     *
     * Validates a business rule condition. If the condition is true,
     * returns Success. If false, returns Failure with the error message.
     *
     * @param  bool  $condition  The condition to validate
     * @param  string  $error  Error message if condition fails
     * @return Result Success if condition is true, Failure otherwise
     *
     * @mago-expect Boolean parameter represents a validation condition, not a behavioral flag
     */
    protected function guard(bool $condition, string $error): Result
    {
        if ($condition) {
            return Result::success(true);
        }

        return Result::failure($error, ["Guard failed: {$error}"]);
    }

    /**
     * Validates multiple conditions using guard logic.
     *
     * Returns the first failure encountered, or Success if all pass.
     *
     * @param  array<string, bool>  $conditions  Array of condition name => condition result
     * @return Result Success if all conditions pass, Failure with first error otherwise
     */
    protected function guardAll(array $conditions): Result
    {
        foreach ($conditions as $error => $condition) {
            $result = $this->guard($condition, $error);
            if ($result->isFailure) {
                return $result;
            }
        }

        return Result::success(true);
    }
}
