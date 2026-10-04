<?php

return [

    'reports' => [
        // Largest report the API accepts, measured uncompressed - and on the wire
        // too, which only matters for a body that grows when gzipped. The
        // document is held in memory once while it is validated, so this must
        // stay well under PHP's memory_limit (512 MB on this host).
        'max_payload_bytes' => 256 * 1024 * 1024,

        // Submissions per minute per device. A device submits once per scan, so
        // this only ever bites a misbehaving or hostile client.
        'rate_limit_per_minute' => 30,

        // The probe writes scan_time with no zone in it, so one has to be
        // assumed before it can be compared with received_at, which is UTC.
        // Every probe so far runs on Polish local time.
        'probe_timezone' => 'Europe/Warsaw',

        // Above this, the panel marks the gap between the scan and its arrival
        // as worth a look. A healthy submission lands within minutes.
        'delivery_lag_warning_minutes' => 60,

        // Largest document the panel offers to show inline. Pretty-printed
        // into the page, tens of megabytes freeze the browser tab; above this
        // the page offers the file instead.
        'preview_max_bytes' => 5 * 1024 * 1024,
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
