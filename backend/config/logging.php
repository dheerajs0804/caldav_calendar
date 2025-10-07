<?php

return [
    'default' => 'file',
    
    'channels' => [
        'file' => [
            'driver' => 'file',
            'path' => __DIR__ . '/../logs/app.log',
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 30,
        ],
        
        'error' => [
            'driver' => 'file',
            'path' => __DIR__ . '/../logs/error.log',
            'level' => 'error',
            'days' => 30,
        ],
        
        'caldav' => [
            'driver' => 'file',
            'path' => __DIR__ . '/../logs/caldav.log',
            'level' => env('CALDAV_LOG_LEVEL', 'info'),
            'days' => 30,
        ],
        
        'security' => [
            'driver' => 'file',
            'path' => __DIR__ . '/../logs/security.log',
            'level' => 'warning',
            'days' => 90, // Keep security logs longer
        ],
    ],
    
    'sensitive_fields' => [
        'password', 'passwd', 'pwd', 'secret', 'token', 'key', 'auth',
        'authorization', 'cookie', 'session', 'csrf', 'api_key', 'private_key',
        'client_secret', 'access_token', 'refresh_token', 'bearer_token'
    ],
    
    'environment' => env('APP_ENV', 'production'),
];
