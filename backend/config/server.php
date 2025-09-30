<?php
/**
 * Server Configuration
 * This file contains dynamic server settings that can be updated without rebuilding Docker images
 */

/**
 * Auto-detect server IP address
 */
function autoDetectServerIP() {
    // Try to get the IP from the request
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
        // Remove port if present
        $ip = explode(':', $host)[0];
        // Check if it's a valid IP
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    
    // Try to get server IP from $_SERVER
    if (isset($_SERVER['SERVER_ADDR'])) {
        return $_SERVER['SERVER_ADDR'];
    }
    
    // Try to get from HTTP_X_FORWARDED_FOR (for load balancers)
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    
    // Try to get from HTTP_X_REAL_IP
    if (isset($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    
    // Fallback to localhost
    return 'localhost';
}

return [
    // Server IP/domain configuration
    'server_ip' => $_ENV['SERVER_IP'] ?? autoDetectServerIP(),
    'server_domain' => $_ENV['SERVER_DOMAIN'] ?? null,
    
    // CORS configuration
    'cors' => [
        'allowed_origins' => [
            'http://localhost:4200',
            'http://localhost:8000',
            'null' // For file:// protocol
        ],
        'allow_dynamic_origins' => true, // Allow origins based on server IP
        'fallback_origin' => 'http://localhost:4200'
    ],
    
    // CalDAV server configuration
    'caldav' => [
        'server_url' => $_ENV['CALDAV_SERVER_URL'] ?? 'http://rc.mithi.com:8008',
        'timeout' => 30
    ],
    
    // Application settings
    'app' => [
        'debug' => $_ENV['APP_DEBUG'] ?? false,
        'environment' => $_ENV['APP_ENV'] ?? 'production'
    ]
];
