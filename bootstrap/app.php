<?php

declare(strict_types=1);

use App\Support\Result;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Validation\ValidationException;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Folio\Folio;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void {
            Folio::path(resource_path('views/pages'))->middleware([
                '*' => [
                    EncryptCookies::class,
                    AddQueuedCookiesToResponse::class,
                    StartSession::class,
                    ShareErrorsFromSession::class,
                    ValidateCsrfToken::class,
                ],
            ]);
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'tenancy' => InitializeTenancyBySubdomain::class,
            'central' => PreventAccessFromCentralDomains::class,
        ]);

        $middleware->group('universal', []); // Middleware that runs on both central and tenant domains

        // Middleware group that includes both auth and auth.session for web routes
        $middleware->group('auth.web', [
            'auth',
            'auth.session',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Convert unhandled exceptions to Result::failure for API routes
        // This allows the UI to handle a "Crashed Command" exactly the same way
        // it handles a "Validation Failure"
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof ValidationException) {
                return null; // Let Laravel handle validation errors normally (422)
            }

            if ($request->is('api/*') || $request->wantsJson()) {
                // Convert any exception into a Failure Monad for the response
                $result = Result::failure(
                    $e->getMessage(),
                    ['trace' => 'Captured by Global Handler', 'file' => $e->getFile(), 'line' => $e->getLine()]
                );

                $statusCode = $e instanceof HttpExceptionInterface
                    ? $e->getStatusCode()
                    : 500;

                return $result->match(
                    onSuccess: fn (): null => null, // Should not happen
                    onFailure: fn (string $error, array $logs) => response()->json([
                        'status' => 'exception',
                        'error' => $error,
                        'audit' => $logs,
                    ], $statusCode)
                );
            }

            return null; // Let Laravel handle non-API exceptions normally
        });
    })
    ->create();
