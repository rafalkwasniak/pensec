<?php

use App\Enums\ApiErrorCode;
use App\Http\Middleware\AuthenticateDevice;
use App\Http\Middleware\RememberLeadSource;
use App\Http\Middleware\SetPanelLocale;
use App\Http\Middleware\StreamReportUpload;
use App\Support\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
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
            'report.intake' => StreamReportUpload::class,
            'panel.locale' => SetPanelLocale::class,
        ]);

        // Where a visitor came from, kept for the contact form (see the class).
        $middleware->web(append: [RememberLeadSource::class]);

        // Unauthenticated portal requests go to the client login; everything
        // else (the admin panel) to the panel login.
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('portal*') ? route('client.login') : route('panel.login'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

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

        // Raised by the framework before any route runs, when Content-Length
        // passes PHP's post_max_size; without this it leaves without the
        // envelope and code the contract promises for 413.
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                ApiErrorCode::PayloadTooLarge,
                __('api.errors.payload_too_large'),
                413,
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
