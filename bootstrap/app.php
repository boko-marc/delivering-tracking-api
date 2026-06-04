<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Module\Shared\ApiResponse;
use Module\Shared\Enums\ErrorCode;
use Module\Shared\Exceptions\ApiException;
use Module\Shared\Middleware\SetLocale;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend([
            SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Global exception handling configured later or in service providers.

        // Application business-specific exception
        $exceptions->renderable(function (ApiException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error(
                    status: $e->getStatus(),
                    message: $e->getMessage(),
                    errors: $e->getData(),
                    code: $e->getErrorCode()
                );
            }
        });

        $exceptions->renderable(function (ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $model = strtolower(class_basename($e->getModel()));
                $ids = $e->getIds();

                return ApiResponse::error(
                    status: 404,
                    message: __(ErrorCode::NOT_FOUND->translationKey()),
                    errors: [
                        'resource_type' => $model,
                        'resource_id' => $ids[0] ?? null,
                    ],
                    code: ErrorCode::NOT_FOUND->code()
                );
            }
        });

        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error(
                    status: 404,
                    message: __(ErrorCode::ROUTE_NOT_FOUND->translationKey()),
                    errors: [
                        'url' => $request->fullUrl(),
                        'method' => $request->method(),
                    ],
                    code: ErrorCode::ROUTE_NOT_FOUND->code()
                );
            }
        });

        $exceptions->renderable(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error(
                    status: 405,
                    message: __(ErrorCode::METHOD_NOT_ALLOWED->translationKey()),
                    errors: [
                        'method' => $request->method(),
                        'allowed_methods' => $e->getHeaders()['Allow'] ?? [],
                    ],
                    code: ErrorCode::METHOD_NOT_ALLOWED->code()
                );
            }
        });

        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error(
                    status: 401,
                    message: __(ErrorCode::UNAUTHORIZED->translationKey()),
                    errors: [
                        'message' => $e->getMessage(),
                        'url' => $request->fullUrl(),
                        'method' => $request->method(),
                    ],
                    code: ErrorCode::UNAUTHORIZED->code()
                );
            }
        });

        $exceptions->renderable(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error(
                    status: 403,
                    message: __(ErrorCode::FORBIDDEN->translationKey()),
                    errors: [
                        'message' => $e->getMessage(),
                        'url' => $request->fullUrl(),
                        'method' => $request->method(),
                    ],
                    code: ErrorCode::FORBIDDEN->code()
                );
            }
        });

        $exceptions->renderable(function (ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error(
                    status: 422,
                    message: __(ErrorCode::VALIDATION_ERROR->translationKey()),
                    errors: $e->errors(),
                    code: ErrorCode::VALIDATION_ERROR->code()  // Détails des champs en erreur
                );
            }
        });

        // Global fallback for unhandled exceptions (500 Internal Server Error)
        // send to sentry in production and sandbox, with detailed logging including error_id for correlation
        $exceptions->reportable(function (Throwable $e) {
            $isNotLocal = app()->isProduction() || app()->environment('sandbox');
            $errorId = 'ERROR-'.uniqid().'-'.time();
            $errorData = [
                'error_id' => $errorId,
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'created_at' => now()->toDateTimeString(),
            ];
            // in production/sandbox, log the error with error_id and send to Sentry if available
            if ($isNotLocal && ! $e instanceof ApiException) {

                logger()->error(__(ErrorCode::INTERNAL_SERVER_ERROR->translationKey()), $errorData);

                // TODO: Sentry captureException with $errorData context (requires Sentry SDK setup in the app)
                $sentry = app()->bound('sentry') ? app('sentry') : null;
                if (is_object($sentry) && method_exists($sentry, 'captureException')) {
                    $sentry->captureException($e, ['extra' => $errorData]);
                }

                return ApiResponse::error(
                    status: 500,
                    message: __(ErrorCode::INTERNAL_SERVER_ERROR->translationKey()),
                    errors: ['error_id' => $errorId],
                    code: ErrorCode::INTERNAL_SERVER_ERROR->code()
                );
            }

            return ApiResponse::error(
                status: 500,
                message: $e->getMessage(),
                errors: $errorData,
                code: ErrorCode::INTERNAL_SERVER_ERROR->code()
            );
        });
    })->create();
