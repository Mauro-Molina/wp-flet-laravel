<?php

namespace App\Support\Http;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => (object) $meta,
            'errors' => [],
        ], $status);
    }

    public static function error(string $message, int $status = 400, array $errors = [], array $meta = []): JsonResponse
    {
        $payloadErrors = $errors !== [] ? $errors : [
            ['code' => 'error', 'message' => $message],
        ];

        return response()->json([
            'data' => null,
            'meta' => (object) $meta,
            'errors' => $payloadErrors,
        ], $status);
    }
}
