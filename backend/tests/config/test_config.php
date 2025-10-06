<?php
declare(strict_types=1);

/**
 * Test Configuration
 * 
 * Configuration settings for the test environment
 */

return [
    'caldav' => [
        'server_url' => 'http://test-caldav-server',
        'username' => 'test_user',
        'password' => 'test_password',
        'timeout' => 30
    ],
    
    'database' => [
        'connection' => 'sqlite',
        'database' => ':memory:',
        'prefix' => 'test_'
    ],
    
    'email' => [
        'smtp_host' => 'test-smtp-server',
        'smtp_port' => 587,
        'smtp_username' => 'test@example.com',
        'smtp_password' => 'test_password',
        'from_email' => 'test@example.com',
        'from_name' => 'Test Calendar'
    ],
    
    'session' => [
        'lifetime' => 3600,
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ],
    
    'logging' => [
        'level' => 'debug',
        'file' => TEST_ROOT . '/logs/test.log'
    ],
    
    'test_data' => [
        'events_file' => TEST_ROOT . '/data/test_events.json',
        'calendars_file' => TEST_ROOT . '/data/test_calendars.json',
        'colors_file' => TEST_ROOT . '/data/test_calendar_colors.json'
    ]
];

