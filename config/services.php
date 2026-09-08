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
     * Which ProfileSource reads a prospect's public Instagram profile.
     *
     * "web" is the anonymous call and Instagram now answers it with a login wall, so it
     * is only a fallback. Setting INSTAGRAM_SESSION_ID switches to "session" on its own,
     * because a configured cookie is only ever there to be used.
     */
    'instagram' => [
        'source' => env('INSTAGRAM_SOURCE', env('INSTAGRAM_SESSION_ID') ? 'session' : 'web'),
        'session_id' => env('INSTAGRAM_SESSION_ID'),
        'csrf_token' => env('INSTAGRAM_CSRF_TOKEN'),

        /*
         * Shared hosting is refused by Instagram whatever cookie it sends, so
         * INSTAGRAM_SOURCE=worker parks audits as pending rows for the local PC in
         * local_pc_instagram_script to fetch and post back. The token is the only thing
         * standing between that queue and the internet, so it has to be a long random
         * string and has to match the worker's own config.
         */
        'worker_token' => env('INSTAGRAM_WORKER_TOKEN'),
        'sources' => [
            'web' => App\Services\SmartLink\Instagram\WebProfileSource::class,
            'session' => App\Services\SmartLink\Instagram\SessionCookieSource::class,
        ],
    ],

];
