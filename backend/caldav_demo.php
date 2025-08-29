<?php
/**
 * CalDAV Configuration Demo File
 * 
 * This is a template file for public GitHub repositories.
 * 
 * IMPORTANT: Replace this file with your actual caldav.php configuration
 * that contains your real CalDAV server credentials.
 * 
 * Copy this file to caldav.php and update the values below:
 * 1. Replace 'your_caldav_server_url' with your actual CalDAV server URL
 * 2. Replace 'your_email@domain.com' with your actual CalDAV username
 * 3. Replace 'your_caldav_password' with your actual CalDAV password
 * 
 * NEVER commit caldav.php with real credentials to a public repository!
 */

return [
    'server_url' => 'your_caldav_server_url',           // e.g., 'http://your-server.com:8008'
    'username' => 'your_email@domain.com',              // e.g., 'user@company.com'
    'password' => 'your_caldav_password',               // Your actual CalDAV password
    'discovery_path' => '/.well-known/caldav',         // Standard CalDAV discovery path
    'calendar_path' => '/calendars/your_calendar_path/', // Your specific calendar path
    'timeout' => 30,                                    // Connection timeout in seconds
    'user_agent' => 'Mithi Calendar/1.0',              // User agent string
    'auth_method' => 'basic'                            // Authentication method (basic or digest)
];
?>
