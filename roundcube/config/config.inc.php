<?php

$config = array();



// Database configuration
$config['db_dsnw'] = 'sqlite:///var/www/html/roundcube.db';

// IMAP server configuration
$config['default_host'] = 'ssl://intmail.mithi.com';
$config['default_port'] = 993;
$config['imap_auth_type'] = 'LOGIN';

// SMTP server configuration
$config['smtp_server'] = 'tls://intmail.mithi.com';
$config['smtp_port'] = 587;
$config['smtp_auth_type'] = 'LOGIN';

// Enable the calendar link plugin
$config['plugins'] = array('calendar_link');

// Security settings
$config['use_https'] = false;
$config['session_lifetime'] = 10;
$config['ip_check'] = false;

// UI settings
$config['product_name'] = 'Mithi Roundcube';
$config['support_url'] = '';
$config['des_key'] = 'your-secret-key-here';

// Logging
$config['log_driver'] = 'file';
$config['log_dir'] = '/var/www/html/logs/';
$config['debug_level'] = 1;

// File upload settings
$config['max_message_size'] = '10MB';
$config['max_recipients'] = 5;

// Cache settings
$config['cache_dir'] = '/var/www/html/temp/';
$config['temp_dir'] = '/var/www/html/temp/';
include(__DIR__ . '/config.docker.inc.php');
