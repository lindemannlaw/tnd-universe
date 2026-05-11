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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'deepl' => [
        'api_key' => env('DEEPL_API_KEY', ''),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'seo_model' => env('OPENAI_SEO_MODEL', 'gpt-4o-mini'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY', ''),
        'seo_model' => env('ANTHROPIC_SEO_MODEL', 'claude-haiku-4-5'),
    ],

    'indexnow' => [
        'key' => env('INDEXNOW_KEY'),
        'host' => env('INDEXNOW_HOST', 'tnduniverse.com'),
    ],

    'google' => [
        'search_console_resource_id' => env('GOOGLE_SC_RESOURCE_ID', 'sc-domain:tnduniverse.com'),
        'indexing_api' => [
            'enabled' => env('GOOGLE_INDEXING_API_ENABLED', false),
            'credentials_path' => env('GOOGLE_INDEXING_API_CREDENTIALS', storage_path('app/google-indexing-credentials.json')),
            'daily_quota' => (int) env('GOOGLE_INDEXING_API_DAILY_QUOTA', 180),
        ],
    ],

];
