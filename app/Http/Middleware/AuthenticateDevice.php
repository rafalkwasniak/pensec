<?php

namespace App\Http\Middleware;

use App\Enums\ApiErrorCode;
use App\Models\Device;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDevice
{
    public const DEVICE_ATTRIBUTE = 'device';

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            return ApiResponse::error(
                ApiErrorCode::DeviceTokenMissing,
                __('api.errors.token_missing'),
                401,
            );
        }

        $device = Device::where('token_hash', Device::hashToken($token))->first();

        if ($device === null) {
            return ApiResponse::error(
                ApiErrorCode::DeviceTokenInvalid,
                __('api.errors.token_invalid'),
                401,
            );
        }

        if (! $device->isActive()) {
            return ApiResponse::error(
                ApiErrorCode::DeviceDisabled,
                __('api.errors.device_disabled'),
                403,
            );
        }

        $device->forceFill(['last_seen_at' => now()])->save();

        $request->attributes->set(self::DEVICE_ATTRIBUTE, $device);

        return $next($request);
    }

    public static function device(Request $request): Device
    {
        return $request->attributes->get(self::DEVICE_ATTRIBUTE);
    }
}
