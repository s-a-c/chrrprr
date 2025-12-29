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
function assert_no_javascript_errors_except_csp_parser(PendingAwaitablePage|AwaitableWebpage|Webpage $page): PendingAwaitablePage|AwaitableWebpage|Webpage
{
    try {
        $page->assertNoJavascriptErrors();
    } catch (Throwable $e) {
        // Only handle AssertionFailedError exceptions
        throw_unless($e instanceof AssertionFailedError, $e);

        $message = $e->getMessage();

        // Check if the error contains CSP parser errors
        $isCspError = str_contains($message, 'CSP Parser Error') ||
            (bool) preg_match('/CSP.*Parser.*Error/i', $message);

        if ($isCspError) {
            // Extract all errors from the message
            preg_match_all('/- (.+)/m', $message, $matches);
            /** @var list<non-empty-string> $errors */
            $errors = is_array($matches[1] ?? null) ? $matches[1] : [];

            // Also check the main message for CSP errors
            $mainErrorIsCsp = false;
            if (preg_match('/but found \d+:\s*(.+?)(?:\n|$)/s', $message, $mainMatch) === 1) {
                $mainError = $mainMatch[1];
                $mainErrorIsCsp = str_contains($mainError, 'CSP Parser Error') ||
                    (bool) preg_match('/CSP.*Parser.*Error/i', $mainError);
            }

            // If no errors were extracted from the list format, check if the main message contains only CSP errors
            if ($errors === [] && $mainErrorIsCsp) {
                return $page;
            }

            // Filter out CSP parser errors
            $realErrors = array_filter($errors, function (string $error): bool {
                $isCspErrorInList = str_contains($error, 'CSP Parser Error') ||
                    (bool) preg_match('/CSP.*Parser.*Error/i', $error);

                return ! $isCspErrorInList;
            });

            // If there are real errors, throw them
            if ($realErrors !== []) {
                throw new AssertionFailedError(
                    "Expected no JavaScript errors on the page, but found:\n".
                    implode("\n", array_map(fn (string $error): string => "- {$error}", $realErrors))
                );
            }

            // If only CSP parser errors, ignore them
            return $page;
        }

        // Re-throw if it's not a CSP parser error
        throw $e;
    }

    return $page;
}
