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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'ai' => [
        'url' => env('AI_SERVICE_URL', 'http://localhost:5000'),
    ],

    'otp' => [
        'driver' => env('OTP_DRIVER', 'log'),              // log | twilio     (phone SMS)
        'email_driver' => env('OTP_EMAIL_DRIVER', 'log'),  // log | smtp | brevo_api  (email)
        'expiry_minutes' => env('OTP_EXPIRY', 10),
    ],

    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
    ],

    'ultramsg' => [
        'instance_id' => env('ULTRAMSG_INSTANCE_ID'),
        'token'       => env('ULTRAMSG_TOKEN'),
    ],

    'stripe' => [
        'key'            => env('STRIPE_KEY'),
        'secret'         => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency'       => env('STRIPE_CURRENCY', 'egp'),
    ],

];
