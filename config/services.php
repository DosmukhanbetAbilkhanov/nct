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

    'national_catalog' => [
        'api_key' => env('NATIONAL_CATALOG_API_KEY'),
        'base_url' => env('NATIONAL_CATALOG_BASE_URL', 'https://nationalcatalog.kz/gwp'),
        'timeout' => 30,
        'retry_times' => 3,
    ],

    'mobizon' => [
        'api_key' => env('MOBIZON_API_KEY'),
        'base_url' => env('MOBIZON_BASE_URL', 'https://api.mobizon.kz/service'),
        'sender_name' => env('MOBIZON_SENDER_NAME', 'NCT'),
    ],

];
