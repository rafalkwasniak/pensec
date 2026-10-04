<?php

return [

    'reports' => [
        'stored' => 'Report stored.',
        'already_stored' => 'Report already stored.',
    ],

    'errors' => [
        'token_missing' => 'Authentication token is missing.',
        'token_invalid' => 'Unknown device token.',
        'device_disabled' => 'This device is disabled.',
        'payload_too_large' => 'The report exceeds the maximum accepted size.',
        'unsupported_encoding' => 'The body must be plain or gzip-compressed JSON.',
        'body_not_gzip' => 'The body could not be decompressed as gzip.',
        'body_not_object' => 'The body must be a JSON object.',
        'report_id_uuid' => 'The report id must be a valid UUID.',
        'report_not_object' => 'The report must be a JSON object.',
        'validation_failed' => 'The submitted data is invalid.',
        'rate_limit_exceeded' => 'Too many requests.',
        'server_error' => 'The report could not be stored.',
    ],

];
