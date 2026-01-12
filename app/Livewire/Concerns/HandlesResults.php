<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Contracts\CommandHandler;
use App\Contracts\QueryHandler;
use App\Support\Result;
use Illuminate\Support\Facades\Log;

/**
 * HandlesResults Trait for Livewire Components.
 *
 * Provides common functionality for Livewire components to handle Result monads.
 * Automatically displays errors via Flux UI notifications and handles loading states.
 */
trait HandlesResults
{
    /**
     * Handle a Result object, displaying notifications and managing state.
     *
     * @param  Result  $result  The Result to handle
     * @param  string|null  $successMessage  Custom success message
     * @param  string|null  $errorMessage  Custom error message prefix
     * @return mixed The value if successful, null if failed
     */
    protected function handleResult(Result $result, ?string $successMessage = null, ?string $errorMessage = null): mixed
    {
        return $result->logInternal()->match(
            onSuccess: function (mixed $value, array $logs) use ($successMessage): mixed {
                if ($successMessage !== null) {
                    $this->dispatch('notify', [
                        'type' => 'success',
                        'message' => $successMessage,
                    ]);
                }

                // Log audit trail in debug mode
                if (config('app.debug') && $logs !== []) {
                    Log::debug('Result audit trail', ['logs' => $logs]);
                }

                return $value;
            },
            onFailure: function (string $error, array $logs) use ($errorMessage): null {
                $message = $errorMessage !== null ? "{$errorMessage}: {$error}" : $error;

                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => $message,
                ]);

                // Log failure with audit trail
                Log::error('Livewire action failed', [
                    'error' => $error,
                    'logs' => $logs,
                    'component' => static::class,
                ]);

                return null;
            }
        );
    }

    /**
     * Execute a command handler and handle the Result.
     *
     * @param  CommandHandler  $handler  The command handler
     * @param  object  $command  The command object
     * @param  string|null  $successMessage  Custom success message
     * @return mixed The value if successful, null if failed
     */
    protected function executeCommand(CommandHandler $handler, object $command, ?string $successMessage = null): mixed
    {
        $result = $handler->handle($command);

        return $this->handleResult($result, $successMessage);
    }

    /**
     * Execute a query handler and handle the Result.
     *
     * @param  QueryHandler  $handler  The query handler
     * @param  object  $query  The query object
     * @return mixed The value if successful, null if failed
     */
    protected function executeQuery(QueryHandler $handler, object $query): mixed
    {
        $result = $handler->ask($query);

        return $this->handleResult($result);
    }

    /**
     * Get the value from a Result, or return null if failure.
     *
     * Useful when you need the actual value but don't want to throw.
     *
     * @param  Result  $result  The Result to unwrap
     * @return mixed The value if successful, null if failed
     */
    protected function unwrapResult(Result $result): mixed
    {
        return $result->getOrElse();
    }

    /**
     * Check if a Result is successful.
     *
     * @param  Result  $result  The Result to check
     */
    protected function isResultSuccess(Result $result): bool
    {
        return $result->isSuccess;
    }

    /**
     * Get error message from a Result, or null if successful.
     *
     * @param  Result  $result  The Result to check
     */
    protected function getResultError(Result $result): ?string
    {
        return $result->getError();
    }
}
