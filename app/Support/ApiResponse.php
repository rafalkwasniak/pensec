<?php

namespace App\Support;

use App\Enums\ApiErrorCode;
use Illuminate\Http\JsonResponse;

/**
 * Every API response leaves through here, so the envelope described in the
 * OpenAPI contract has exactly one implementation.
 */
class ApiResponse
{
    /**
     * @param  array<string, mixed>|null  $data
     */
    public static function success(string $message, ?array $data = null, int $status = 200): JsonResponse
    {
        $body = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $body['data'] = $data;
        }

        return response()->json($body, $status);
    }

    /**
     * @param  array<string, array<int, string>>|null  $errors
     */
    public static function error(ApiErrorCode $code, string $message, int $status, ?array $errors = null): JsonResponse
    {
        $body = [
            'success' => false,
            'code' => $code->value,
            'message' => $message,
        ];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }
}
