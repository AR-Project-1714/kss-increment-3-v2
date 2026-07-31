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

    /*
    |--------------------------------------------------------------------------
    | IDCloudHost
    |--------------------------------------------------------------------------
    |
    | Kredensial hanya dibaca oleh backend. Jangan pernah meneruskan API key
    | ke Blade, JavaScript, log, atau response browser.
    |
    */
    'idcloudhost' => [
        'api_key' => env('IDCLOUDHOST_API_KEY'),
        'billing_account_id' => env('IDCLOUDHOST_BILLING_ACCOUNT_ID'),
        'base_url' => env('IDCLOUDHOST_API_BASE_URL', 'https://api.idcloudhost.com/v1'),
        'currency' => env('IDCLOUDHOST_CURRENCY', 'IDR'),
        'cache_seconds' => (int) env('IDCLOUDHOST_CACHE_SECONDS', 900),
        'timeout_seconds' => (int) env('IDCLOUDHOST_TIMEOUT_SECONDS', 8),
        'warning_days' => (float) env('IDCLOUDHOST_WARNING_DAYS', 7),
        'critical_days' => (float) env('IDCLOUDHOST_CRITICAL_DAYS', 3),
        'runway_target_days' => (float) env('IDCLOUDHOST_RUNWAY_TARGET_DAYS', 180),
        'low_credit_threshold' => (float) env('IDCLOUDHOST_LOW_CREDIT_THRESHOLD', 100000),
        'estimated_monthly_cost' => env('IDCLOUDHOST_ESTIMATED_MONTHLY_COST'),
    ],

];
