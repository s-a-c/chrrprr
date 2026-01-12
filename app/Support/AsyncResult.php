<?php

declare(strict_types=1);

namespace App\Support;

use Throwable;

/**
 * AsyncResult: Extends Result for concurrent operations.
 *
 * Handles high-concurrency CQRS tasks, such as calling multiple
 * thirdfinal -party APIs in parallel, while merging their independent
 * Writer logs into a single chronological audit trail.
 */
final class AsyncResult extends Result
{
    /**
     * Executes an array of closures in parallel.
     *
     * Each closure MUST return a Result object.
     * Follows the "All-or-Nothing" principle: if any task fails,
     * the entire operation fails with the first error encountered.
     *
     * @param  array<string, callable(): Result>  $tasks  Array of tasks to execute (key => closure)
     */
    public static function all(array $tasks): Result
    {
        if ($tasks === []) {
            return Result::success([], ['No tasks to execute']);
        }

        $mergedValue = [];
        $mergedLogs = ['Parallel execution started: '.count($tasks).' tasks.'];

        // For now, execute sequentially but collect results
        // This can be enhanced with actual parallel execution when Laravel's
        // Concurrency facade or other async mechanisms are available
        foreach ($tasks as $key => $task) {
            if (! is_callable($task)) {
                return Result::failure(
                    "Async task '{$key}' is not callable.",
                    $mergedLogs
                );
            }

            // Execute task and catch exceptions
            // Tasks should return Result objects directly
            try {
                $result = $task();
            } catch (Throwable $e) {
                return Result::failure(
                    error: "Async task '{$key}' threw exception: {$e->getMessage()}",
                    logs: [...$mergedLogs, 'Exception caught: '.$e::class]
                );
            }

            // Verify the task returned a Result
            if (! $result instanceof Result) {
                return Result::failure(
                    "Async task '{$key}' did not return a Result object.",
                    $mergedLogs
                );
            }

            // Error Monad: Short-circuit if any task failed
            if ($result->isFailure) {
                return Result::failure(
                    error: "Async Failure in '{$key}': {$result->error}",
                    logs: [...$mergedLogs, ...$result->logs]
                );
            }

            // Writer Monad: Accumulate logs and values
            $mergedValue[$key] = $result->value;
            $mergedLogs = [...$mergedLogs, ...$result->logs];
        }

        return Result::success($mergedValue, $mergedLogs);
    }
}
