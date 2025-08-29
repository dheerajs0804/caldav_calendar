<?php
/**
 * CalDAV Configuration File
 * 
 * Configure your CalDAV server settings here
 */

return [
    'server_url' => 'http://rc.mithi.com:8008',
    'username' => 'dheeraj.sharma@mithi.com',
    'password' => 'M!th!#567', // Replace with actual password
    
    // Optional: Calendar discovery settings
    'discovery_path' => '/.well-known/caldav',
    'calendar_path' => '/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/calendar/',
    
    // Optional: Connection settings
    'timeout' => 30,
    'user_agent' => 'Mithi Calendar/1.0',
    
    // Optional: Authentication method (basic, digest, oauth2)
    'auth_method' => 'basic'
];
?>
