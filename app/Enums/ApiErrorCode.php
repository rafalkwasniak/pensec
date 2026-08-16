<?php

namespace App\Enums;

/**
 * Machine-readable failure identifiers. Clients branch on these, never on the
 * message, so a value must not change meaning once published in the contract.
 */
enum ApiErrorCode: string
{
    case ValidationFailed = 'validation_failed';
    case DeviceTokenMissing = 'device_token_missing';
    case DeviceTokenInvalid = 'device_token_invalid';
    case DeviceDisabled = 'device_disabled';
    case PayloadTooLarge = 'payload_too_large';
    case RateLimitExceeded = 'rate_limit_exceeded';
    case ServerError = 'server_error';
}
