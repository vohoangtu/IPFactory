<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o'),
    ],

    'loom' => [
        'url' => env('NARRATIVE_LOOM_URL', 'http://narrative_loom:8001'),
        'timeout' => env('NARRATIVE_LOOM_TIMEOUT', 600),
    ],

    'narrative_loom' => [
        'url' => env('NARRATIVE_LOOM_URL', 'http://narrative_loom:8001'),
        'provider' => env('NARRATIVE_LOOM_PROVIDER', 'local'),
        'model' => env('NARRATIVE_LOOM_MODEL', env('LOCAL_LLM_MODEL', 'qwen3.5-9b-uncensored-hauhaucs-aggressive')),
        'timeout' => env('NARRATIVE_LOOM_TIMEOUT', 600),
    ],

    'social_engine' => [
        'url' => env('SOCIAL_ENGINE_URL', 'http://127.0.0.1:5001/api/v1'),
        'agents_count' => env('SOCIAL_ENGINE_AGENTS_COUNT', 10),
        'mock_mode' => env('SOCIAL_ENGINE_MOCK', false),
    ],

];
