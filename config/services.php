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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        // Relative, so it follows whichever host served the request and does
        // not depend on APP_URL. Must match an "Authorized redirect URI" on
        // the OAuth client in Google Cloud Console.
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    // "Sign in with ApiSpi". Credentials come from the apispi.com admin
    // (Grounding apps → issue credentials), with <site>/auth/apispi/callback
    // as the redirect URI.
    'apispi' => [
        'client_id' => env('APISPI_CLIENT_ID'),
        'client_secret' => env('APISPI_CLIENT_SECRET'),
        'redirect' => env('APISPI_REDIRECT_URI', '/auth/apispi/callback'),
        'base_url' => env('APISPI_OAUTH_URL', 'https://apispi.com'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
