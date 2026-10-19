<?php

return [

    'frontend_url' => env('FRONTEND_URL', env('APP_URL')),

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

'stripe' => [
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
],

'jnt' => [
    'base_url' => env('JNT_BASE_URL'),
    'username' => env('JNT_USERNAME'),
    'password' => env('JNT_PASSWORD'),
    'service_domestic_code' => env('JNT_SERVICE_DOMESTIC_CODE'),
    'service_international_code' => env('JNT_SERVICE_INTERNATIONAL_INTERNATIONAL_CODE'),    
    'merchant_code' => env('JNT_MERCHANT_CODE'),
    'pickup_postcode' => env('JNT_PICKUP_POSTCODE'),
],
];

