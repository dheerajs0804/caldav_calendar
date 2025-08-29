<?php
/**
 * Email Configuration File
 * 
 * Configure your email service settings here
 */

// Company SMTP Configuration (Primary) - Integrated with CalDAV
return [
    'company_smtp' => [
        'enabled' => true,                                  // Enable company SMTP
        'host' => 'intmail.mithi.com',                     // Company SMTP server
        'port' => 587,                                      // Standard STARTTLS port
        'security' => 'tls',                                // Use TLS (server requires it)
        'auth_type' => 'LOGIN',                             // Authentication type
        'use_caldav_auth' => true,                          // Use CalDAV user credentials
        'fallback_username' => 'noreply@mithi.com',         // Fallback username if CalDAV auth fails
        'fallback_password' => '',                          // Fallback password (set if needed)
        'from_email' => 'noreply@mithi.com',                // From email address
        'from_name' => 'Mithi Calendar',                    // From name
        'timeout' => 30,                                    // Connection timeout
        'debug' => true,                                    // Enable debug logging
        'try_no_tls_fallback' => false,                     // Don't try without TLS (server requires it)
        'alternative_ports' => [587, 465, 25],              // Alternative ports to try
        'alternative_security' => ['tls', 'ssl', 'none']    // Alternative security methods
    ],
    
    // SendGrid Configuration (Disabled)
    'sendgrid' => [
        'api_key' => '',
        'from_email' => 'dheeraj2004.sharma@gmail.com',
        'from_name' => 'Mithi Calendar',
        'enabled' => false                                 // Disabled - using company SMTP instead
    ],
    
    // Alternative: Gmail SMTP Configuration (Disabled)
    'gmail' => [
        'enabled' => false,
        'username' => 'your-email@gmail.com',
        'password' => 'your-app-password',
        'from_name' => 'Mithi Calendar'
    ]
];
?>
