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
    
'line' => [
    // --- LINEログイン（LIFF） ---
    'channel_id' => env('LINE_CHANNEL_ID'),
    'channel_secret' => env('LINE_CHANNEL_SECRET'),
    'liff_id' => env('VITE_LIFF_ID'),
    'redirect'       => env('LINE_REDIRECT_URI'),
    // --- Messaging API ---
    'message_channel_id' => env('LINE_MESSAGE_CHANNEL_ID'),
    'message_channel_secret' => env('LINE_MESSAGE_CHANNEL_SECRET'),
    'channel_access_token' => env('LINE_MESSAGE_ACCESS_TOKEN'),

    // --- 共通情報 ---
    'bot_add_url' => env('LINE_BOT_ADD_URL') ?: env('VITE_LINE_BOT_ADD_URL'),
    'bot_qr'      => env('LINE_BOT_QR') ?: env('VITE_LINE_BOT_QR'),
],

];
