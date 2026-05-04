<?php

return [
    'url'            => env('CENTRIFUGO_URL', 'http://localhost:8000'),
    'api_key'        => env('CENTRIFUGO_KEY', ''),
    'secret'         => env('CENTRIFUGO_SECRET', ''),
    'hmac_secret'    => env('CENTRIFUGO_HMAC_SECRET', ''),
    'show_queries'   => env('CENTRIFUGO_SHOW_QUERIES', false),
    'verify'         => env('CENTRIFUGO_VERIFY', false),
    'ssl_key'        => env('CENTRIFUGO_SSL_KEY', null),
];
