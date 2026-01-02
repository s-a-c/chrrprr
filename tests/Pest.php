<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * |--------------------------------------------------------------------------
 * | Test Case
 * |--------------------------------------------------------------------------
 * |
 * | The closure you provide to your test functions is always bound to a specific PHPUnit test
 * | case class. By default, that class Pest "PHPUnit\Framework\TestCase". Of course, you may
 * | need to change it using the "pest()" function to bind a different classes or traits.
 * |
 */

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Browser');

/*
 * |--------------------------------------------------------------------------
 * | Architecture Test Configuration
 * |--------------------------------------------------------------------------
 * |
 * | Configure Pest Arch to ignore vendor and framework dependencies during
 * | analysis to improve performance. The phpunit.xml source configuration
 * | limits file scanning to the 'app' directory only.
 */
pest()->beforeEach(function (): void {
    $this->arch()
        ->ignore([
            'vendor',
            'Illuminate',
            'Laravel',
        ])
        ->ignoreGlobalFunctions();
})->in('Arch');

/*
 * |--------------------------------------------------------------------------
 * | Expectations
 * |--------------------------------------------------------------------------
 * |
 * | When you're writing tests, you often need to check that values meet certain conditions. The
 * | "expect()" function gives you access to a set of "expectations" methods that you can use
 * | to assert different things. Of course, you may extend the Expectation API at any time.
 * |
 */

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
 * |--------------------------------------------------------------------------
 * | Functions
 * |--------------------------------------------------------------------------
 * |
 * | While Pest is very powerful out-of-the-box, you may have some testing code specific to your
 * | project that you don't want to repeat in every file. Here you can also expose helpers as
 * | global functions to help you to reduce the number of lines of code in your test files.
 * |
 */

function something(): void
{
    // ..
}

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;
use Pest\Browser\Api\Webpage;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Assert no JavaScript errors except CSP parser errors.
 * CSP parser errors are false positives from browser extensions or CSP configuration.
 */
/**
 * Check if a message contains MCP server informational messages.
 */
function is_mcp_message(string $message): bool
{
    return str_contains($message, '🔍 Browser logger active') &&
        (str_contains($message, 'MCP server detected') || str_contains($message, 'MCP server'));
}

/**
 * Check if an error string is an MCP message.
 */
function is_mcp_error(string $error): bool
{
    return str_contains($error, '🔍 Browser logger active') &&
        (str_contains($error, 'MCP server detected') || str_contains($error, 'MCP server'));
}

/**
 * Check if a message contains CSP parser errors.
 */
function is_csp_error_message(string $message): bool
{
    return str_contains($message, 'CSP Parser Error') ||
        (bool) preg_match('/CSP.*Parser.*Error/i', $message);
}

/**
 * Check if an error string is a CSP parser error.
 */
function is_csp_error(string $error): bool
{
    return str_contains($error, 'CSP Parser Error') ||
        (bool) preg_match('/CSP.*Parser.*Error/i', $error) ||
        (bool) preg_match('/Uncaught Error.*CSP.*Parser.*Error/i', $error) ||
        (bool) preg_match('/CSP.*Parser.*Error.*Unexpected token/i', $error);
}

/**
 * Extract errors from an assertion message (list format).
 *
 * @return list<non-empty-string>
 */
function extract_errors_from_message(string $message): array
{
    preg_match_all('/- (.+)/m', $message, $matches);

    return is_array($matches[1] ?? null) ? $matches[1] : [];
}

/**
 * Extract main error from "but found N: MESSAGE" format.
 */
function extract_main_error(string $message, string $stopPattern = '(?:\n|$|Failed asserting)'): ?string
{
    if (preg_match("/but found \d+:\s*(.+?){$stopPattern}/s", $message, $mainMatch) === 1) {
        return mb_trim($mainMatch[1]);
    }

    return null;
}

/**
 * Filter JavaScript errors, removing MCP and CSP parser errors.
 */
function filter_javascript_errors(PendingAwaitablePage|AwaitableWebpage|Webpage $page, AssertionFailedError $e): PendingAwaitablePage|AwaitableWebpage|Webpage
{
    $message = $e->getMessage();

    // Handle MCP messages
    if (is_mcp_message($message)) {
        $errors = extract_errors_from_message($message);
        $mainError = extract_main_error($message);

        if ($errors === [] && $mainError !== null && is_mcp_error($mainError)) {
            return $page;
        }

        $realErrors = array_filter($errors, fn (string $error): bool => ! is_mcp_error($error));

        if ($realErrors !== []) {
            throw new AssertionFailedError(
                "Expected no JavaScript errors on the page, but found:\n".
                implode("\n", array_map(fn (string $error): string => "- {$error}", $realErrors))
            );
        }

        return $page;
    }

    // Handle CSP parser errors
    if (is_csp_error_message($message)) {
        $errors = extract_errors_from_message($message);
        $mainError = extract_main_error($message);
        $mainErrorIsCsp = $mainError !== null && is_csp_error($mainError);

        if ($errors === [] && $mainErrorIsCsp) {
            return $page;
        }

        if ($errors !== []) {
            $realErrors = array_filter($errors, fn (string $error): bool => ! is_csp_error($error));

            if ($realErrors !== []) {
                throw new AssertionFailedError(
                    "Expected no JavaScript errors on the page, but found:\n".
                    implode("\n", array_map(fn (string $error): string => "- {$error}", $realErrors))
                );
            }

            return $page;
        }

        return $page;
    }

    throw $e;
}

/**
 * Filter console logs, removing MCP server messages.
 */
function filter_console_logs(PendingAwaitablePage|AwaitableWebpage|Webpage $page, AssertionFailedError $e): PendingAwaitablePage|AwaitableWebpage|Webpage
{
    $message = $e->getMessage();

    throw_unless(is_mcp_message($message), $e);

    $logs = extract_errors_from_message($message);
    $mainLog = extract_main_error($message, '(?:\nFailed asserting|\nThe following|$)');

    if ($logs === [] && $mainLog !== null && is_mcp_error($mainLog)) {
        return $page;
    }

    $realLogs = array_filter($logs, fn (string $log): bool => ! is_mcp_error($log));

    if ($realLogs !== []) {
        throw new AssertionFailedError(
            "Expected no console logs on the page, but found:\n".
            implode("\n", array_map(fn (string $log): string => "- {$log}", $realLogs))
        );
    }

    return $page;
}

function assert_no_javascript_errors_except_csp_parser(PendingAwaitablePage|AwaitableWebpage|Webpage $page): PendingAwaitablePage|AwaitableWebpage|Webpage
{
    try {
        $page->assertNoJavascriptErrors();
    } catch (Throwable $e) {
        throw_unless($e instanceof AssertionFailedError, $e);

        return filter_javascript_errors($page, $e);
    }

    try {
        $page->assertNoConsoleLogs();
    } catch (AssertionFailedError $e) {
        return filter_console_logs($page, $e);
    }

    return $page;
}
