<?php

return [

    'reports' => [
        // Largest report body the API accepts. The web server and PHP allow far
        // more, so this is the limit that actually decides.
        'max_payload_bytes' => 32 * 1024 * 1024,

        // Submissions per minute per device. A device submits once per scan, so
        // this only ever bites a misbehaving or hostile client.
        'rate_limit_per_minute' => 30,
    ],

    'devices' => [
        // Raw bytes behind a device token. Rendered as hex, so the token the
        // device receives is twice this long.
        'token_bytes' => 32,

        // Leading characters of the token kept in clear, so the panel can tell
        // two devices apart without being able to reconstruct the token.
        'token_prefix_length' => 8,
    ],

];
