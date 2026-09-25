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

    'hitpay' => [

        'mode' => env('HITPAY_MODE', 'sandbox'),

        'api_key' => env('HITPAY_API_KEY'),

        'salt' => env('HITPAY_SALT', env('HITPAY_WEBHOOK_SALT')),

        'webhook_url' => env('HITPAY_WEBHOOK_URL'),

        'success_url' => env('HITPAY_SUCCESS_URL'),

        'cancel_url' => env('HITPAY_CANCEL_URL'),

        'currency' => env('HITPAY_CURRENCY', 'PHP'),

        'base_url' => env('HITPAY_API_URL', env('HITPAY_BASE_URL')),

        'payment_methods' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('HITPAY_PAYMENT_METHODS', ''))
        ))),

        'frontend_url' => env('HITPAY_FRONTEND_URL', env('FRONTEND_URL')),

    ],

];
