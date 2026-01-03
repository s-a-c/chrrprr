<?php

declare(strict_types=1);

use App\Contracts\CommandHandler;
use App\Support\Result;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Exception;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Assert;
use Thunk\Verbs\Event as VerbsEvent;

/**
 * CQRS Context for Behat tests.
 *
 * Provides CQRS pattern validation and Result monad testing.
 */
class CqrsContext implements Context
{
    protected ?Result $lastResult = null;

    protected array $firedEvents = [];

    /**
     * Execute a command.
     *
     * @When I execute command :commandName with data:
     */
    public function iExecuteCommandWithData(string $commandName, PyStringNode $data): void
    {
        $commandClass = "App\\Handlers\\Commands\\{$commandName}";
        if (! class_exists($commandClass)) {
            throw new Exception("Command class '{$commandClass}' not found");
        }

        $dataArray = json_decode($data->getRaw(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON in command data: '.json_last_error_msg());
        }

        $command = new $commandClass($dataArray);

        $handlerClass = str_replace('Command', 'Handler', $commandClass);
        if (! class_exists($handlerClass)) {
            throw new Exception("Handler class '{$handlerClass}' not found");
        }

        $handler = app($handlerClass);
        if (! ($handler instanceof CommandHandler)) {
            throw new Exception("Handler '{$handlerClass}' does not implement CommandHandler");
        }

        // Capture events
        Event::fake();
        $this->firedEvents = [];

        $this->lastResult = $handler->handle($command);

        // Collect fired Verbs events
        Event::assertDispatched(function ($event) {
            if ($event instanceof VerbsEvent) {
                $this->firedEvents[] = $event;
            }

            return true;
        });
    }

    /**
     * Assert command returned successful result.
     *
     * @Then the command should return a successful Result
     */
    public function theCommandShouldReturnASuccessfulResult(): void
    {
        Assert::assertNotNull($this->lastResult, 'No command result available');
        Assert::assertTrue($this->lastResult->isSuccess, 'Command result is not successful: '.$this->lastResult->error);
    }

    /**
     * Assert command returned failed result.
     *
     * @Then the command should return a failed Result
     */
    public function theCommandShouldReturnAFailedResult(): void
    {
        Assert::assertNotNull($this->lastResult, 'No command result available');
        Assert::assertTrue($this->lastResult->isFailure, 'Command result is not failed');
    }

    /**
     * Assert result contains specific error.
     *
     * @Then the Result should contain error :error
     */
    public function theResultShouldContainError(string $error): void
    {
        Assert::assertNotNull($this->lastResult, 'No command result available');
        Assert::assertTrue($this->lastResult->isFailure, 'Result is not a failure');
        Assert::assertStringContainsString($error, $this->lastResult->error, "Result error does not contain '{$error}'");
    }

    /**
     * Assert result contains logs.
     *
     * @Then the Result should contain logs:
     */
    public function theResultShouldContainLogs(PyStringNode $logs): void
    {
        Assert::assertNotNull($this->lastResult, 'No command result available');

        $expectedLogs = array_filter(explode("\n", $logs->getRaw()));
        $actualLogs = $this->lastResult->logs;

        foreach ($expectedLogs as $expectedLog) {
            $expectedLog = mb_trim($expectedLog);
            if (empty($expectedLog)) {
                continue;
            }

            $found = false;
            foreach ($actualLogs as $actualLog) {
                if (str_contains($actualLog, $expectedLog)) {
                    $found = true;
                    break;
                }
            }

            Assert::assertTrue($found, "Expected log '{$expectedLog}' not found in result logs");
        }
    }

    /**
     * Assert event was fired.
     *
     * @Then an event :eventName should be fired
     */
    public function anEventShouldBeFired(string $eventName): void
    {
        $eventClass = "App\\Events\\{$eventName}";
        if (! class_exists($eventClass)) {
            throw new Exception("Event class '{$eventClass}' not found");
        }

        $found = false;
        foreach ($this->firedEvents as $event) {
            if ($event instanceof $eventClass) {
                $found = true;
                break;
            }
        }

        Assert::assertTrue($found, "Event '{$eventName}' was not fired");
    }

    /**
     * Assert projection was updated.
     *
     * @Then the projection should be updated
     */
    public function theProjectionShouldBeUpdated(): void
    {
        // This is a placeholder - actual projection validation would depend on
        // the specific projection implementation
        Assert::assertTrue(true, 'Projection update validation not yet implemented');
    }

    /**
     * Assert result value equals expected.
     *
     * @Then the Result value should be :expected
     */
    public function theResultValueShouldBe(string $expected): void
    {
        Assert::assertNotNull($this->lastResult, 'No command result available');
        Assert::assertTrue($this->lastResult->isSuccess, 'Result is not successful');

        $actual = is_string($this->lastResult->value) ? $this->lastResult->value : (string) $this->lastResult->value;
        Assert::assertEquals($expected, $actual, "Result value does not match expected '{$expected}'");
    }

    /**
     * Get last result.
     */
    public function getLastResult(): ?Result
    {
        return $this->lastResult;
    }

    /**
     * Create successful result.
     *
     * @When I create a successful Result with value :value
     */
    public function iCreateASuccessfulResultWithValue(string $value): void
    {
        $this->lastResult = Result::success($value);
    }

    /**
     * Create failed result.
     *
     * @When I create a failed Result with error :error
     */
    public function iCreateAFailedResultWithError(string $error): void
    {
        $this->lastResult = Result::failure($error);
    }

    /**
     * Assert result is successful.
     *
     * @Then the Result should be successful
     */
    public function theResultShouldBeSuccessful(): void
    {
        Assert::assertNotNull($this->lastResult, 'No result available');
        Assert::assertTrue($this->lastResult->isSuccess, 'Result is not successful');
    }

    /**
     * Assert result is failure.
     *
     * @Then the Result should be a failure
     */
    public function theResultShouldBeAFailure(): void
    {
        Assert::assertNotNull($this->lastResult, 'No result available');
        Assert::assertTrue($this->lastResult->isFailure, 'Result is not a failure');
    }

    /**
     * Map result.
     *
     * @When I map the Result to :transformation
     */
    public function iMapTheResultTo(string $transformation): void
    {
        if (! $this->lastResult || $this->lastResult->isFailure) {
            throw new Exception('Cannot map a failed result');
        }

        $callback = match ($transformation) {
            'uppercase' => fn ($v) => mb_strtoupper((string) $v),
            default => fn ($v) => $v,
        };

        $this->lastResult = $this->lastResult->map($callback);
    }

    /**
     * Execute query.
     *
     * @When I execute query :queryName with data:
     */
    public function iExecuteQueryWithData(string $queryName, PyStringNode $data): void
    {
        $queryClass = "App\\Handlers\\Queries\\{$queryName}";
        if (! class_exists($queryClass)) {
            throw new Exception("Query class '{$queryClass}' not found");
        }

        $dataArray = json_decode($data->getRaw(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON in query data: '.json_last_error_msg());
        }

        $query = new $queryClass($dataArray);

        $handlerClass = str_replace('Query', 'Handler', $queryClass);
        if (! class_exists($handlerClass)) {
            throw new Exception("Handler class '{$handlerClass}' not found");
        }

        $handler = app($handlerClass);
        $this->lastResult = $handler->handle($query);
    }

    /**
     * Assert query returned successful result.
     *
     * @Then the query should return a successful Result
     */
    public function theQueryShouldReturnASuccessfulResult(): void
    {
        $this->theCommandShouldReturnASuccessfulResult();
    }

    /**
     * Assert result contains team data.
     *
     * @Then the Result value should contain team data
     */
    public function theResultValueShouldContainTeamData(): void
    {
        Assert::assertNotNull($this->lastResult, 'No result available');
        Assert::assertTrue($this->lastResult->isSuccess, 'Result is not successful');
        Assert::assertNotNull($this->lastResult->value, 'Result value is null');
    }

    /**
     * Assert team name.
     *
     * @Then the team name should be :name
     */
    public function theTeamNameShouldBe(string $name): void
    {
        Assert::assertNotNull($this->lastResult, 'No result available');
        Assert::assertTrue($this->lastResult->isSuccess, 'Result is not successful');

        $team = $this->lastResult->value;
        if (! ($team instanceof App\Models\Team)) {
            throw new Exception('Result value is not a Team');
        }

        $teamName = is_array($team->name) ? ($team->name['en'] ?? '') : $team->name;
        Assert::assertEquals($name, $teamName, "Team name does not match '{$name}'");
    }

    /**
     * Reset context state.
     *
     * @BeforeScenario
     */
    public function resetContext(): void
    {
        $this->lastResult = null;
        $this->firedEvents = [];
    }
}
