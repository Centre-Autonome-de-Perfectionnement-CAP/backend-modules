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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'twilio' => [

    // Account SID → console.twilio.com → "Account Info"
    'sid' => env('TWILIO_ACCOUNT_SID'),

    // Auth Token → console.twilio.com → "Account Info"
    'token' => env('TWILIO_AUTH_TOKEN'),

    // Numéro expéditeur WhatsApp
    // Sandbox Twilio (tests) : whatsapp:+14155238886
    // Production (numéro approuvé) : whatsapp:+229XXXXXXXX
    'whatsapp_from' => env('TWILIO_WHATSAPP_FROM', 'whatsapp:+14155238886'),

],

];
