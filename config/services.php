<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    /*
    | Brickset, used to mirror how many copies of a set are owned back to the
    | Brickset collection, and BrickLink, used to price them.
    |
    | Both are read through the config rather than getenv() so the values
    | survive `php artisan config:cache`, which stops the .env file from being
    | loaded at all and would otherwise leave every credential empty.
    */

    'brickset' => [
        'user_hash' => env('MURSTEN_TRACK_USER_HASH'),
    ],

    'bricklink' => [
        'consumer_key' => env('MURSTEN_STOCK_CONSUMER_KEY'),
        'consumer_secret' => env('MURSTEN_STOCK_CONSUMER_SECRET'),
        'token' => env('MURSTEN_STOCK_TOKEN'),
        'token_secret' => env('MURSTEN_STOCK_TOKEN_SECRET'),
    ],

];
