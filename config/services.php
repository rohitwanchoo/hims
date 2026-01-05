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

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | ABDM (Ayushman Bharat Digital Mission) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for ABHA integration with India's National Digital Health
    | Mission. Use sandbox URL for development/testing.
    |
    | Production: https://abdm.gov.in/gateway
    | Sandbox: https://dev.abdm.gov.in/gateway
    |
    */

    'abdm' => [
        'gateway_url' => env('ABDM_GATEWAY_URL', 'https://dev.abdm.gov.in/gateway'),
        'client_id' => env('ABDM_CLIENT_ID'),
        'client_secret' => env('ABDM_CLIENT_SECRET'),
        'hip_id' => env('ABDM_HIP_ID'),
        'hip_name' => env('ABDM_HIP_NAME'),
        'callback_url' => env('ABDM_CALLBACK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Default SMS gateway settings. Individual gateways can be configured
    | in the database via the sms_gateways table.
    |
    */

    'sms' => [
        'default_provider' => env('SMS_DEFAULT_PROVIDER', 'msg91'),

        'msg91' => [
            'api_key' => env('MSG91_API_KEY'),
            'sender_id' => env('MSG91_SENDER_ID'),
            'route' => env('MSG91_ROUTE', '4'), // Transactional
        ],

        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'from' => env('TWILIO_FROM'),
        ],

        'textlocal' => [
            'api_key' => env('TEXTLOCAL_API_KEY'),
            'sender' => env('TEXTLOCAL_SENDER'),
        ],
    ],

];
