<?php
/**
 * CalDAV Configuration File
 * 
 * Configure your CalDAV server settings here
 * Note: User credentials are NOT stored here - they come from SSO tokens/sessions
 */

return [
    // CalDAV server configuration
    'server_url' => 'http://rc.mithi.com:18008',
    'discovery_url' => 'http://rc.mithi.com:18008/.well-known/caldav',
    'environment' => 'development',
    
    // Fallback configurations
    // 'server_url' => 'http://localhost:18008', // Local CalDAV server
    // 'server_url' => 'http://localhost:8000/mock_caldav.php', // Mock server
    
    // No hardcoded username/password - these come from user login via SSO
    
    // Optional: Calendar discovery settings
    'discovery_path' => '/.well-known/caldav',
    
    // Optional: Connection settings
    'timeout' => 10,
    'connect_timeout' => 5,
    'user_agent' => 'Mithi Calendar/1.0',
    
    // Optional: Authentication method (basic, digest, oauth2)
    'auth_method' => 'basic'
];
?>
