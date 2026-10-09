<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Build a successful API response.
     */
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200,
        array $meta = [],
    ): JsonResponse {
        return response()->json(self::payload(true, $message, $data, $meta), $status);
    }

    /**
     * Build a resource-created response (HTTP 201).
     */
    public static function created(
        mixed $data = null,
        string $message = 'Created',
        array $meta = [],
    ): JsonResponse {
        return self::success($data, $message, 201, $meta);
    }

    /**
     * Build an empty successful response (HTTP 204).
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Build a failed API response.
     */
    public static function error(
        string $message = 'Error',
        int $status = 400,
        mixed $data = null,
        array $meta = [],
    ): JsonResponse {
        return response()->json(self::payload(false, $message, $data, $meta), $status);
    }

    /**
     * Assemble the response envelope.
     */
    protected static function payload(bool $success, string $message, mixed $data, array $meta): array
    {
        $payload = [
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ];

        if (! empty($meta)) {
            $payload['meta'] = $meta;
        }

        return $payload;
    }
}
