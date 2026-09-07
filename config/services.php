<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'pagespeed' => [
        'key' => env('PAGESPEED_API_KEY'),
    ],

    /*
     * Which ProfileSource reads a prospect's public Instagram profile. "web" needs no
     * key but Instagram now answers it with a login wall, so a paid provider has to be
     * registered here and selected with INSTAGRAM_SOURCE before the feature returns data.
     */
    'instagram' => [
        'source' => env('INSTAGRAM_SOURCE', 'web'),
        'sources' => [
            'web' => App\Services\SmartLink\Instagram\WebProfileSource::class,
        ],
    ],

];
