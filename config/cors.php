<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    /* 'allowed_origins' => ['*'], */
    'allowed_origins' => [
        'https://bonnita-glam.vercel.app',
        'https://bonnitaglammakeup.com.ar',
        'http://localhost:4321' 
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
