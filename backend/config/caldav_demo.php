<?php
/**
 * CalDAV Configuration File - DEMO VERSION
 * 
 * This is a demo configuration file with placeholder values.
 * Copy this file to caldav.php and update with your actual credentials.
 * 
 * IMPORTANT: Never commit caldav.php to version control!
 */

return [
    'server_url' => 'http://your-caldav-server.com:8008',
    'username' => 'your-email@yourcompany.com',
    'password' => 'your_caldav_password_here',
    'discovery_path' => '/.well-known/caldav',
    'calendar_path' => '/calendars/__uids__/your-calendar-id/calendar/',
    'timeout' => 30,
    'user_agent' => 'Mithi Calendar/1.0',
    'auth_method' => 'basic'
];
?>
