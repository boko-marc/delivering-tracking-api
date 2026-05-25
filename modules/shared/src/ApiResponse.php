<?php

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

    public static function error(int $status, ?string $message = null, array|object|null $errors = null, ?string $code = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'status' => $status,
            ],
            'data' => $errors ? $errors : null,
        ];

        return response()->json($payload, $status);
    }
}
