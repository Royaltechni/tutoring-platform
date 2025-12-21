<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Zoom - Server to Server OAuth
    |--------------------------------------------------------------------------
    | يُستخدم لإنشاء الاجتماعات + جلب ZAK
    | (حساب المنصّة هو الـ Host الحقيقي)
    */

     // Zoom S2S OAuth (إنشاء الاجتماعات وجلب ZAK)
'zoom' => [
    'sdk_key' => env('ZOOM_MEETING_SDK_KEY'),
    'sdk_secret' => env('ZOOM_MEETING_SDK_SECRET'),
    'client_id' => env('ZOOM_CLIENT_ID'),
    'client_secret' => env('ZOOM_CLIENT_SECRET'),
    'account_id' => env('ZOOM_ACCOUNT_ID'),
    'default_host_email' => env('ZOOM_DEFAULT_HOST_EMAIL'),
],

];
