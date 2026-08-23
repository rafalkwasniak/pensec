<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // DeepSeek, which will turn a stored report into the PDF. Only the key is a
    // secret, so only the key comes from the environment; the rest are tunables.
    'deepseek' => [
        'key' => env('DEEPSEEK_API_KEY'),

        // OpenAI-compatible, so the chat completion path is appended to this.
        'base_url' => 'https://api.deepseek.com',

        // Name a model the account actually lists. The old `deepseek-chat` and
        // `deepseek-reasoner` aliases still answer, but both now resolve to
        // `deepseek-v4-flash` - so asking for the reasoner silently gets you the
        // cheapest tier instead. `GET /models` is the only honest source here.
        'model' => 'deepseek-v4-flash',

        // The v4 models reason before answering. Left at the default the flash
        // model thinks at full tilt - far slower, and thousands of billed
        // thinking tokens on what is only a writing task.
        'reasoning_effort' => 'low',

        // A whole report is one call, so this is a job timeout, not a web one.
        'timeout' => 240,

        // Low, but not zero: the wording should be readable, the facts fixed.
        'temperature' => 0.3,
    ],

];
