<?php

declare(strict_types=1);

namespace Module\Shared;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $status);
    }

    /**
     * @param  array<array-key, mixed>|object|null  $errors
     */
    public static function error(int $status, ?string $message = null, array|object|null $errors = null, ?string $code = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'status' => $status,
            ],
            'data' => $errors ?: null,
        ];

        return response()->json($payload, $status);
    }
}
