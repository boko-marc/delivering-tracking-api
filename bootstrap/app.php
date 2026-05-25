<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Module\Shared\ApiResponse;
use Module\Shared\Enums\ErrorCode;
use Module\Shared\Exceptions\ApiException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Module\Shared\Middleware\SetLocale;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
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
                    code: $e->getErrorCode(),
                    errors: $e->getData()
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
                    code: ErrorCode::NOT_FOUND->code(),
                    errors: [
                        'resource_type' => $model,
                        'resource_id' => $ids[0] ?? null,
                    ]
                );
            }
        });

        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error(
                    status: 404,
                    message: __(ErrorCode::ROUTE_NOT_FOUND->translationKey()),
                    code: ErrorCode::ROUTE_NOT_FOUND->code(),
                    errors: [
                        'url' => $request->fullUrl(),
                        'method' => $request->method(),
                    ]
                );
            }
        });

        $exceptions->renderable(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error(
                    status: 405,
                    message: __(ErrorCode::METHOD_NOT_ALLOWED->translationKey()),
                    code: ErrorCode::METHOD_NOT_ALLOWED->code(),
                    errors: [
                        'method' => $request->method(),
                        'allowed_methods' => $e->getHeaders()['Allow'] ?? [],
                    ]
                );
            }
        });

        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error(
                    status: 401,
                    message: __(ErrorCode::UNAUTHORIZED->translationKey()),
                    code: ErrorCode::UNAUTHORIZED->code(),
                    errors: [
                        'message' => $e->getMessage(),
                        'url' => $request->fullUrl(),
                        'method' => $request->method(),
                    ]
                );
            }
        });

        $exceptions->renderable(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error(
                    status: 403,
                    message: __(ErrorCode::FORBIDDEN->translationKey()),
                    code: ErrorCode::FORBIDDEN->code(),
                    errors: [
                        'message' => $e->getMessage(),
                        'url' => $request->fullUrl(),
                        'method' => $request->method(),
                    ]
                );
            }
        });

        $exceptions->renderable(function (ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error(
                    status: 422,
                    message: __(ErrorCode::VALIDATION_ERROR->translationKey()),
                    code: ErrorCode::VALIDATION_ERROR->code(),
                    errors: $e->errors()  // Détails des champs en erreur
                );
            }
        });

        // Global fallback for unhandled exceptions (500 Internal Server Error)
        //send to sentry in production and sandbox, with detailed logging including error_id for correlation
        $exceptions->reportable(function (Throwable $e) {
            $isNotLocal = app()->isProduction() || app()->environment('sandbox');
            $errorId = "ERROR-" . uniqid() . "-" . time();
            $errorData = [
                'error_id' => $errorId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'created_at' => now()->toDateTimeString(),
            ];
            //in production/sandbox, log the error with error_id and send to Sentry if available
            if ($isNotLocal && !$e instanceof ApiException) {

                logger()->error(__(ErrorCode::INTERNAL_SERVER_ERROR->translationKey()), $errorData);


                //TODO: Sentry captureException with $errorData context (requires Sentry SDK setup in the app)
                if (app()->bound('sentry')) {
                    app('sentry')->captureException($e, ['extra' => $errorData]);
                }

                return ApiResponse::error(
                    status: 500,
                    message: __(ErrorCode::INTERNAL_SERVER_ERROR->translationKey()),
                    code: ErrorCode::INTERNAL_SERVER_ERROR->code(),
                    errors: ['error_id' => $errorId]
                );
            }

            return ApiResponse::error(
                status: 500,
                message: $e->getMessage(),
                code: ErrorCode::INTERNAL_SERVER_ERROR->code(),
                errors: $errorData
            );
        });
    })->create();
