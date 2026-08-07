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

    'zion_shipping' => [
        'api_url' => env('ZION_SHIPPING_API_URL', 'https://dev.zionshipping.com/'),
        'web_url' => env('ZION_SHIPPING_WEB_URL', env('ZION_SHIPPING_API_URL', 'https://dev.zionshipping.com/')),
        'timeout' => env('ZION_SHIPPING_API_TIMEOUT', 45),
    ],

    'zeptomail' => [
        'host' => env('ZEPTOMAIL_HOST', 'api.zeptomail.com'),
        'token' => env('ZEPTOMAIL_TOKEN'),
        'agent_alias' => env('ZEPTOMAIL_AGENT_ALIAS'),
        'bounce_address' => env('ZEPTOMAIL_BOUNCE_ADDRESS'),
    ],

];
