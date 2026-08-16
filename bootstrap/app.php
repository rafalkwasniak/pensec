<?php

use App\Enums\ApiErrorCode;
use App\Http\Middleware\AuthenticateDevice;
use App\Http\Middleware\EnforceReportSizeLimit;
use App\Http\Middleware\SetPanelLocale;
use App\Support\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'device.auth' => AuthenticateDevice::class,
            'report.size' => EnforceReportSizeLimit::class,
            'panel.locale' => SetPanelLocale::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('panel.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Failures on the API leave in the same envelope as successes, with the
        // machine-readable code the contract publishes.
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                ApiErrorCode::ValidationFailed,
                __('api.errors.validation_failed'),
                422,
                $exception->errors(),
            );
        });

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                ApiErrorCode::RateLimitExceeded,
                __('api.errors.rate_limit_exceeded'),
                429,
            )->withHeaders($exception->getHeaders());
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')
                || $exception instanceof HttpExceptionInterface
                || $exception instanceof ValidationException) {
                return null;
            }

            report($exception);

            return ApiResponse::error(
                ApiErrorCode::ServerError,
                __('api.errors.server_error'),
                500,
            );
        });
    })->create();
