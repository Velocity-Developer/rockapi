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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whmcs' => [
        'api_url' => env('WHMCS_API_URL'),
        'identifier' => env('WHMCS_API_IDENTIFIER'),
        'secret' => env('WHMCS_API_SECRET'),
        'access_key' => env('WHMCS_ACCESS_KEY'),
        'custom_url' => env('WHMCS_CUSTOM_URL'),
    ],

    'directadmin' => [
        'relay_url' => env('DIRECTADMIN_RELAY_URL'),
        'url' => env('DIRECTADMIN_URL'),
        'username' => env('DIRECTADMIN_USERNAME'),
        'password' => env('DIRECTADMIN_PASSWORD'),
    ],
];
