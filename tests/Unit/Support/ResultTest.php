<?php

declare(strict_types=1);

use App\Support\AsyncResult;
use App\Support\Result;

test('it satisfies the Left Identity law', function (): void {
    $value = 10;
    $f = static fn ($x): Result => Result::success($x + 5);

    // flatMap(unit(x), f) == f(x)
    expect(Result::success($value)->flatMap($f)->value)
        ->toBe($f($value)->value);
});

test('it satisfies the Right Identity law', function (): void {
    $result = Result::success(10);

    // flatMap(m, unit) == m
    $unit = fn ($x): Result => Result::success($x);

    expect($result->flatMap($unit)->value)
        ->toBe($result->value)
        ->and($result->flatMap($unit)->isSuccess)
        ->toBe($result->isSuccess);
});

test('it satisfies the Associativity law', function (): void {
    $m = Result::success(5);
    $f = fn ($x): Result => Result::success($x * 2);
    $g = fn ($x): Result => Result::success($x + 3);

    // flatMap(flatMap(m, f), g) == flatMap(m, x => flatMap(f(x), g))
    $left = $m->flatMap($f)->flatMap($g);
    $right = $m->flatMap(fn ($x): Result => $f($x)->flatMap($g));

    expect($left->value)->toBe($right->value)
        ->and($left->isSuccess)->toBe($right->isSuccess);
});

test('it accumulates Writer logs through the chain', function (): void {
    $result = Result::success(1, ['Step 1'])
        ->flatMap(fn ($val): Result => Result::success($val + 1, ['Step 2']))
        ->flatMap(fn ($val): Result => Result::success($val + 1, ['Step 3']));

    expect($result->value)->toBe(3)
        ->and($result->logs)->toHaveCount(3)
        ->and($result->logs)->toContain('Step 1')
        ->and($result->logs)->toContain('Step 2')
        ->and($result->logs)->toContain('Step 3');
});

test('it proxies collection methods while preserving the monad', function (): void {
    $result = Result::success([1, 2, 3, 4, 5])
        ->filter(fn ($n): bool => $n > 3) // Collection method
        ->values();                // Collection method

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->isSuccess)->toBeTrue()
        ->and($result->value->toArray())->toBe([4, 5])
        ->and($result->logs)->toContain('Collection Operation: filter')
        ->and($result->logs)->toContain('Collection Operation: values');
});

test('it short-circuits on failure (Error Monad)', function (): void {
    $result = Result::failure('Stop here')
        ->flatMap(fn ($val): Result => Result::success($val + 10));

    expect($result->isSuccess)->toBeFalse()
        ->and($result->isFailure)->toBeTrue()
        ->and($result->error)->toBe('Stop here')
        ->and($result->value)->toBeNull();
});

test('it preserves logs when short-circuiting', function (): void {
    $result = Result::success(1, ['Step 1'])
        ->flatMap(fn (): Result => Result::failure('Error occurred', ['Step 2']))
        ->flatMap(fn ($val): Result => Result::success($val + 10, ['Step 3']));

    expect($result->isFailure)->toBeTrue()
        ->and($result->error)->toBe('Error occurred')
        ->and($result->logs)->toContain('Step 1')
        ->and($result->logs)->toContain('Step 2')
        ->and($result->logs)->not->toContain('Step 3');
});

test('it transforms values with map', function (): void {
    $result = Result::success(5)
        ->map(fn ($x): int|float => $x * 2)
        ->map(fn ($x): int|float => $x + 3);

    expect($result->value)->toBe(13)
        ->and($result->isSuccess)->toBeTrue();
});

test('map does not execute on failure', function (): void {
    $result = Result::failure('Error')
        ->map(fn ($x): int|float => $x * 2);

    expect($result->isFailure)->toBeTrue()
        ->and($result->error)->toBe('Error')
        ->and($result->value)->toBeNull();
});

test('it matches on success', function (): void {
    $result = Result::success('Hello', ['Logged']);

    $output = $result->match(
        onSuccess: fn ($value, $logs): string => "Success: {$value} with ".count($logs).' logs',
        onFailure: fn ($error, $logs): string => "Failure: {$error}"
    );

    expect($output)->toBe('Success: Hello with 1 logs');
});

test('it matches on failure', function (): void {
    $result = Result::failure('Not found', ['Tried lookup']);

    $output = $result->match(
        onSuccess: fn ($value, $logs): string => "Success: {$value}",
        onFailure: fn ($error, $logs): string => "Failure: {$error} with ".count($logs).' logs'
    );

    expect($output)->toBe('Failure: Not found with 1 logs');
});

test('it handles Result::try with successful operation', function (): void {
    $result = Result::try(
        fn (): int => 42,
        ['Context: testing try']
    );

    expect($result->isSuccess)->toBeTrue()
        ->and($result->value)->toBe(42)
        ->and($result->logs)->toContain('Context: testing try');
});

test('it handles Result::try with exception', function (): void {
    $result = Result::try(
        fn () => throw new RuntimeException('Test exception'),
        ['Context: testing exception']
    );

    expect($result->isFailure)->toBeTrue()
        ->and($result->error)->toBe('Test exception')
        ->and($result->logs)->toContain('Context: testing exception')
        ->and($result->logs)->toContain('Exception caught: RuntimeException');
});

test('it handles Result::try with Result return', function (): void {
    $innerResult = Result::success('Inner', ['Inner log']);

    $result = Result::try(
        fn (): Result => $innerResult,
        ['Context: testing Result return']
    );

    expect($result->isSuccess)->toBeTrue()
        ->and($result->value)->toBe('Inner')
        ->and($result->logs)->toContain('Inner log');
});

test('it provides getOrElse for default values', function (): void {
    $success = Result::success('value');
    $failure = Result::failure('error');

    expect($success->getOrElse('default'))->toBe('value')
        ->and($failure->getOrElse('default'))->toBe('default');
});

test('it provides getError method', function (): void {
    $success = Result::success('value');
    $failure = Result::failure('error message');

    expect($success->getError())->toBeNull()
        ->and($failure->getError())->toBe('error message');
});

test('it provides getLogs method', function (): void {
    $result = Result::success('value', ['Log 1', 'Log 2']);

    expect($result->getLogs())->toBe(['Log 1', 'Log 2']);
});

test('it computes isSuccess property correctly', function (): void {
    $success = Result::success('value');
    $failure = Result::failure('error');

    expect($success->isSuccess)->toBeTrue()
        ->and($failure->isSuccess)->toBeFalse();
});

test('it computes isFailure property correctly', function (): void {
    $success = Result::success('value');
    $failure = Result::failure('error');

    expect($success->isFailure)->toBeFalse()
        ->and($failure->isFailure)->toBeTrue();
});

test('collection proxy returns failure on failed result', function (): void {
    $result = Result::failure('Error');

    $output = $result->filter(fn ($x): true => true);

    expect($output->isFailure)->toBeTrue()
        ->and($output->error)->toBe('Error');
});

test('collection proxy throws on unknown method', function (): void {
    $result = Result::success([1, 2, 3]);

    expect(static fn (): mixed => $result->unknownMethod())
        ->toThrow(BadMethodCallException::class);
});

test('AsyncResult::all executes all tasks successfully', function (): void {
    $tasks = [
        'task1' => fn (): Result => Result::success('Result 1', ['Task 1 executed']),
        'task2' => fn (): Result => Result::success('Result 2', ['Task 2 executed']),
        'task3' => fn (): Result => Result::success('Result 3', ['Task 3 executed']),
    ];

    $result = AsyncResult::all($tasks);

    expect($result->isSuccess)->toBeTrue()
        ->and($result->value)->toBeArray()
        ->and($result->value)->toHaveKeys(['task1', 'task2', 'task3'])
        ->and($result->value['task1'])->toBe('Result 1')
        ->and($result->value['task2'])->toBe('Result 2')
        ->and($result->value['task3'])->toBe('Result 3')
        ->and($result->logs)->toContain('Task 1 executed')
        ->and($result->logs)->toContain('Task 2 executed')
        ->and($result->logs)->toContain('Task 3 executed');
});

test('AsyncResult::all short-circuits on first failure', function (): void {
    $tasks = [
        'task1' => fn (): Result => Result::success('Result 1', ['Task 1 executed']),
        'task2' => fn (): Result => Result::failure('Task 2 failed', ['Task 2 error']),
        'task3' => fn (): Result => Result::success('Result 3', ['Task 3 executed']),
    ];

    $result = AsyncResult::all($tasks);

    expect($result->isFailure)->toBeTrue()
        ->and($result->error)->toContain("Async Failure in 'task2'")
        ->and($result->error)->toContain('Task 2 failed')
        ->and($result->logs)->toContain('Task 1 executed')
        ->and($result->logs)->toContain('Task 2 error')
        ->and($result->logs)->not->toContain('Task 3 executed');
});

test('AsyncResult::all handles empty task array', function (): void {
    $result = AsyncResult::all([]);

    expect($result->isSuccess)->toBeTrue()
        ->and($result->value)->toBe([])
        ->and($result->logs)->toContain('No tasks to execute');
});

test('AsyncResult::all fails on non-callable task', function (): void {
    $tasks = [
        'task1' => fn (): Result => Result::success('Result 1'),
        'task2' => 'not callable',
    ];

    $result = AsyncResult::all($tasks);

    expect($result->isFailure)->toBeTrue()
        ->and($result->error)->toContain("Async task 'task2' is not callable");
});

test('AsyncResult::all fails on task that does not return Result', function (): void {
    $tasks = [
        'task1' => fn (): string => 'not a Result',
    ];

    $result = AsyncResult::all($tasks);

    expect($result->isFailure)->toBeTrue()
        ->and($result->error)->toContain('did not return a Result object');
});
