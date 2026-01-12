<?php

declare(strict_types=1);

use App\Models\Enterprise;

test('bio markdown rendering completes in less than 100ms for 95 percent of requests', function (): void {
    // Create a team with a moderately complex bio (typical use case)
    $bioContent = <<<'MARKDOWN'
    # Team Overview

    This is a comprehensive team biography that includes:

    ## Features

    - **Leadership**: Strong executive team
    - **Culture**: Collaborative and innovative
    - **Goals**: Excellence in delivery

    ## Code Example

    ```php
    public function render(): string
    {
        return 'Hello World';
    }
    ```

    ## More Content

    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
    MARKDOWN;

    $team = Enterprise::factory()->create([
        'bio' => $bioContent,
    ]);

    // Measure rendering time for multiple iterations
    $iterations = 100;
    $times = [];

    for ($i = 0; $i < $iterations; $i++) {
        // Clear instance cache/reload if needed? No, purely measuring getter execution.
        // But if getter caches internally (e.g. static cache), results differ.
        // Assuming getter does work.

        $start = microtime(true);
        $result = $team->bio_html; // Trigger rendering
        $end = microtime(true);

        $times[] = ($end - $start) * 1000; // Convert to milliseconds
    }

    // Calculate 95th percentile
    sort($times);
    $percentile95 = $times[(int) (count($times) * 0.95)];

    // Assert that 95% of requests complete in less than 100ms (SC-011)
    expect($percentile95)->toBeLessThan(100.0);
})->group('performance')->skip(fn (): bool => ! env('CI') && ! env('RUN_PERF_TESTS'), 'Performance test - set RUN_PERF_TESTS=1 or run in CI');

test('bio rendering handles large content efficiently', function (): void {
    // Create bio at the soft limit (10,000 characters)
    $largeBio = str_repeat('# Large Team Biography\n\n', 500).str_repeat('Content here. ', 100);

    $team = Enterprise::factory()->create([
        'bio' => $largeBio,
    ]);

    $start = microtime(true);
    $html = $team->bio_html;
    $end = microtime(true);

    $renderTime = ($end - $start) * 1000; // Convert to milliseconds

    // Even large content should render reasonably fast
    expect($renderTime)->toBeLessThan(500.0); // More lenient for large content
    expect($html)->not->toBeNull();
})->group('performance')->skip(fn (): bool => ! env('CI') && ! env('RUN_PERF_TESTS'), 'Performance test - set RUN_PERF_TESTS=1 or run in CI');

test('bio rendering handles empty bio efficiently', function (): void {
    $team = Enterprise::factory()->create([
        'bio' => null,
    ]);

    $start = microtime(true);
    $html = $team->bio_html;
    $end = microtime(true);

    $renderTime = ($end - $start) * 1000; // Convert to milliseconds

    // Empty bio should be very fast
    expect($renderTime)->toBeLessThan(10.0);
    expect($html)->toBeNull();
})->group('performance')->skip(fn (): bool => ! env('CI') && ! env('RUN_PERF_TESTS'), 'Performance test - set RUN_PERF_TESTS=1 or run in CI');
