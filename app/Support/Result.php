<?php

declare(strict_types=1);

namespace App\Support;

use BadMethodCallException;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Throwable;

/**
 * Result Monad: Combines Error, Option, and Writer patterns.
 *
 * This class provides a unified monadic interface for handling:
 * - Error Monad: Success/Failure states
 * - Option Monad: Null handling (Failure = None, Success = Some)
 * - Writer Monad: Audit trail accumulation
 *
 * @template TValue
 *
 * @mixin Collection<int, TValue>
 */
class Result
{
    /**
     * Error Monad: Computed success state via Property Hooks.
     */
    public bool $isSuccess {
        get => $this->error === null;
    }

    /**
     * Option Monad: Computed failure/none state.
     */
    public bool $isFailure {
        get => $this->error !== null;
    }

    /**
     * @param  mixed  $value  The success value (null if failure)
     * @param  string|null  $error  The error message (null if success)
     * @param  array<string>  $logs  Writer Monad: Internal audit trail
     */
    private function __construct(
        public mixed $value = null,
        public ?string $error = null,
        public array $logs = []
    ) {}

    /**
     * Proxy Monad: Intercepts Collection calls.
     *
     * Allows direct use of Laravel Collection methods on the Result,
     * while preserving the monad state and accumulating logs.
     *
     * @param  string  $method  The Collection method name
     * @param  array<mixed>  $parameters  Method parameters
     *
     * @throws BadMethodCallException
     */
    public function __call(string $method, array $parameters): self
    {
        if ($this->isFailure) {
            return $this;
        }

        $collection = collect($this->value);

        if (method_exists($collection, $method)) {
            $result = $collection->$method(...$parameters);

            return new self(
                value: $result,
                logs: [...$this->logs, "Collection Operation: {$method}"]
            );
        }

        throw new BadMethodCallException("Method {$method} not found on Result or Collection.");
    }

    /**
     * Create a successful Result.
     *
     * @param  mixed  $value  The success value
     * @param  array<string>  $logs  Initial audit log entries
     */
    public static function success(mixed $value = true, array $logs = []): self
    {
        return new self(value: $value, logs: $logs);
    }

    /**
     * Create a failed Result.
     *
     * @param  string  $error  The error message
     * @param  array<string>  $logs  Initial audit log entries
     */
    public static function failure(string $error, array $logs = []): self
    {
        return new self(error: $error, logs: $logs);
    }

    /**
     * Lifts an impure execution into the Result Monad.
     *
     * Catches exceptions and converts them to Result::failure,
     * preserving the Writer logs in the context.
     *
     * @param  callable  $operation  The operation to execute
     * @param  array<string>  $context  Context for the Writer monad
     */
    public static function try(callable $operation, array $context = []): self
    {
        try {
            $value = $operation();

            // If the return is already a Result, return it. Otherwise, wrap it.
            return $value instanceof self ? $value : self::success($value, $context);
        } catch (ValidationException $e) {
            // Extract validation error message from ValidationException
            $messages = $e->errors();
            $firstMessage = collect($messages)->flatten()->first();
            $errorMessage = is_string($firstMessage) ? $firstMessage : $e->getMessage();

            return self::failure(
                error: $errorMessage,
                logs: [...$context, 'ValidationException caught: '.$errorMessage]
            );
        } catch (Throwable $e) {
            return self::failure(
                error: $e->getMessage(),
                logs: [...$context, 'Exception caught: '.$e::class, 'File: '.$e->getFile()]
            );
        }
    }

    /**
     * Bind (flatMap): The core of the Error Monad.
     *
     * Transitions state and accumulates Writer logs.
     * If the current Result is a failure, returns itself without executing the callback.
     * If successful, executes the callback and merges logs.
     *
     * @param  Closure(mixed): self  $callback  Function that returns a Result
     */
    public function flatMap(Closure $callback): self
    {
        if ($this->isFailure) {
            return $this;
        }

        $next = $callback($this->value);

        if (! $next instanceof self) {
            return self::success($next, [...$this->logs, 'Transformed value outside monad']);
        }

        return new self(
            value: $next->value,
            error: $next->error,
            logs: [...$this->logs, ...$next->logs]
        );
    }

    /**
     * Transform the value if successful.
     *
     * Unlike flatMap, this does not expect the callback to return a Result.
     * The transformed value is wrapped in a new success Result.
     *
     * @param  Closure(mixed): mixed  $callback  Function that transforms the value
     */
    public function map(Closure $callback): self
    {
        if ($this->isFailure) {
            return $this;
        }

        return new self(
            value: $callback($this->value),
            error: null,
            logs: $this->logs
        );
    }

    /**
     * Final unwrap for the UI layer.
     *
     * Pattern matching: executes the appropriate callback based on success/failure state.
     *
     * @param  Closure(mixed, array<string>): mixed  $onSuccess  Callback for success (receives value and logs)
     * @param  Closure(string, array<string>): mixed  $onFailure  Callback for failure (receives error and logs)
     */
    public function match(Closure $onSuccess, Closure $onFailure): mixed
    {
        return $this->isSuccess
            ? $onSuccess($this->value, $this->logs)
            : $onFailure($this->error, $this->logs);
    }

    /**
     * Get the value, or return a default if failure.
     *
     * @param  mixed  $default  Default value to return on failure
     */
    public function getOrElse(mixed $default = null): mixed
    {
        return $this->isSuccess ? $this->value : $default;
    }

    /**
     * Get the error message, or null if success.
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Get the audit logs.
     *
     * @return array<string>
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * Log the Writer monad's audit trail to Laravel logs.
     *
     * Flushes the internal logs array to Laravel's logging system.
     * This decouples business logic from infrastructure logging.
     * Also integrates with Telescope if available.
     *
     * @return static Returns self for method chaining
     *
     * @psalm-return static<TValue>
     */
    public function logInternal(): static
    {
        if ($this->logs !== []) {
            Log::channel('audit')->info('Command Execution Path', [
                'success' => $this->isSuccess,
                'error' => $this->error,
                'steps' => $this->logs,
            ]);

            // Record to Telescope if available
            if (class_exists(Telescope::class) && Telescope::isRecording()) {
                Telescope::recordLog(
                    IncomingEntry::make([
                        'level' => $this->isSuccess ? 'info' : 'error',
                        'message' => $this->isSuccess ? 'Monad Railway Success' : 'Monad Railway Derailed',
                        'context' => [
                            'error' => $this->error,
                            'audit_trail' => $this->logs,
                            'value_type' => get_debug_type($this->value),
                        ],
                    ])
                );
            }
        }

        return $this;
    }
}
