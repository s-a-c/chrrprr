<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Contracts\CommandHandler;
use App\Contracts\QueryHandler;
use App\Support\Result;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Result Action Base Class for Filament.
 *
 * Provides a base class for Filament actions that work with CQRS handlers
 * returning Result monads. Automatically converts Result states to Filament
 * notifications and handles success/failure display.
 */
abstract class ResultAction extends Action
{
    /**
     * Execute a command handler and handle the Result.
     *
     * @param  CommandHandler  $handler  The command handler to execute
     * @param  object  $command  The command object
     */
    protected function executeCommand(CommandHandler $handler, object $command): void
    {
        $result = $handler->handle($command)->logInternal();

        $this->handleResult($result);
    }

    /**
     * Execute a query handler and handle the Result.
     *
     * @param  QueryHandler  $handler  The query handler to execute
     * @param  object  $query  The query object
     */
    protected function executeQuery(QueryHandler $handler, object $query): void
    {
        $result = $handler->ask($query)->logInternal();

        $this->handleResult($result);
    }

    /**
     * Handle a Result object, converting it to Filament notifications.
     *
     * @param  Result  $result  The Result to handle
     */
    protected function handleResult(Result $result): void
    {
        $result->match(
            onSuccess: static function (mixed $value, array $logs): void {
                Notification::make()
                    ->title('Success')
                    ->success()
                    ->send();

                // Optionally log audit trail in debug mode
                if (config('app.debug') && $logs !== []) {
                    Log::debug('Result audit trail', ['logs' => $logs]);
                }
            },
            onFailure: static function (string $error, array $logs): void {
                Notification::make()
                    ->title('Error')
                    ->body($error)
                    ->danger()
                    ->send();

                // Log failure with audit trail
                Log::error('Action failed', [
                    'error' => $error,
                    'logs' => $logs,
                ]);
            }
        );
    }

    /**
     * Get the value from a Result, or throw if failure.
     *
     * Useful when you need the actual value for further processing.
     *
     * @param  Result  $result  The Result to unwrap
     *
     * @throws RuntimeException If the Result is a failure
     */
    protected function unwrapResult(Result $result): mixed
    {
        return $result->match(
            onSuccess: static fn (mixed $value): mixed => $value,
            onFailure: static fn (string $error) => throw new RuntimeException("Cannot unwrap failed Result: {$error}")
        );
    }
}
