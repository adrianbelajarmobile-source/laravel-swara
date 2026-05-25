<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    */

    'default' => env('BROADCAST_DRIVER', 'reverb'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other parts of your application as well as other
    | applications on your network. Samples of each available type of connection
    | are provided inside this array.
    |
    */

    'connections' => [

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'host' => env('PUSHER_HOST') ?: 'api-' . env('PUSHER_REGION', 'mt') . '.pusher.com',
                'port' => env('PUSHER_PORT', 443),
                'scheme' => env('PUSHER_SCHEME', 'https'),
                'encrypted' => true,
                'useTLS' => env('PUSHER_USE_TLS', true),
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request_options.html
            ],
        ],

        'ably' => [
            'driver' => 'ably',
            'authUrl' => '/api/broadcasting/auth',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => env('REVERB_HOST', 'localhost'),
                'port' => env('REVERB_PORT', 8080),
                'scheme' => env('REVERB_SCHEME', 'http'),
                'useTLS' => env('REVERB_SCHEME') === 'https',
            ],
            'client_options' => [
                'verify' => false,
            ],
        ],

        'websockets' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY', 'laravel-websockets-key'),
            'secret' => env('PUSHER_APP_SECRET', 'laravel-websockets-secret'),
            'app_id' => env('PUSHER_APP_ID', '12345'),
            'options' => [
                'host' => env('WEBSOCKETS_HOST', 'localhost'),
                'port' => env('WEBSOCKETS_PORT', 6001),
                'scheme' => env('WEBSOCKETS_SCHEME', 'http'),
                'encrypted' => false,
            ],
            'client_options' => [
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                ],
            ],
        ],

    ],

];
