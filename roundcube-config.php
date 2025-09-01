<?php

/* Local configuration for Roundcube Webmail */

// ----------------------------------
// SQL DATABASE
// ----------------------------------
// Database connection string (DSN) for read+write operations
// Format (compatible with PEAR MDB2): db_provider://user:password@host/database
// Currently supported db_providers: mysql, pgsql, sqlite, mssql, sqlsrv, oracle
// For examples see http://pear.php.net/manual/en/package.database.mdb2.intro-dsn.php
// Note: for SQLite use absolute path (Linux): 'sqlite:////full/path/to/sqlite.db?mode=0646'
//       or (Windows): 'sqlite:///C:/full/path/to/sqlite.db'
// Note: Various drivers support various additional arguments for connection,
//       for Mysql: key, cipher, cert, capath, ca, verify_server_cert,
//       for Postgres: application_name, sslmode, sslcert, sslkey, sslrootcert, sslcrl, sslcompression, service.
//       e.g. 'mysql://roundcube:@localhost/roundcubemail?verify_server_cert=false'
$config['db_dsnw'] = 'sqlite:////var/roundcube/db/sqlite.db?mode=0646';

// ----------------------------------
// LOGGING/DEBUGGING
// ----------------------------------
// log driver:  'syslog', 'stdout' or 'file'.
$config['log_driver'] = 'stdout';

// Log successful/failed logins to <log_dir>/userlogins.log or to syslog
$config['log_logins'] = true;

// Mail server configuration (using correct parameter names)
$config['imap_host'] = 'ssl://intmail.mithi.com:993';

// Authentication settings - let users enter their own credentials
$config['imap_auth_type'] = 'LOGIN';

// IMAP socket context options
// See http://php.net/manual/en/context.ssl.php
// The example below enables server certificate validation
//$config['imap_conn_options'] = [
//  'ssl'         => [
//     'verify_peer'  => true,
//     'verify_depth' => 3,
//     'cafile'       => '/etc/openssl/certs/ca.crt',
//   ],
// ];
// Note: These can be also specified as an array of options indexed by hostname
$config['imap_conn_options'] = array (
  'ssl' => 
  array (
    'verify_peer' => false,
    'verify_peer_name' => false,
    'allow_self_signed' => true,
  ),
);

// IMAP connection settings
$config['imap_timeout'] = 30;

// ----------------------------------
// SMTP
// ----------------------------------
// SMTP server host (and optional port number) for sending mails.
// Enter hostname with prefix ssl:// to use Implicit TLS, or use
// prefix tls:// to use STARTTLS.
// If port number is omitted it will be set to 465 (for ssl://) or 587 otherwise.
// Supported replacement variables:
// %h - user's IMAP hostname
// %n - hostname ($_SERVER['SERVER_NAME'])
// %t - hostname without the first part
// %d - domain (http hostname $_SERVER['HTTP_HOST'] without the first part)
// %z - IMAP domain (IMAP hostname without the first part)
// For example %n = mail.domain.tld, %t = domain.tld
// To specify different SMTP servers for different IMAP hosts provide an array
// of IMAP host (no prefix or port) and SMTP server e.g. ['imap.example.com' => 'smtp.example.net']
$config['smtp_host'] = 'tls://intmail.mithi.com:587';

// SMTP AUTH type (DIGEST-MD5, CRAM-MD5, LOGIN, PLAIN or empty to use
// best server supported one)
$config['smtp_auth_type'] = 'LOGIN';

// SMTP connection settings  
$config['smtp_timeout'] = 30;

// SMTP socket context options
// See http://php.net/manual/en/context.ssl.php
// The example below enables server certificate validation, and
// requires 'smtp_timeout' to be non zero.
// $config['smtp_conn_options'] = [
//     'ssl' => [
//         'verify_peer'  => true,
//         'verify_depth' => 3,
//         'cafile'       => '/etc/openssl/certs/ca.crt',
//     ],
// ];
// Note: These can be also specified as an array of options indexed by hostname
$config['smtp_conn_options'] = array (
  'ssl' => 
  array (
    'verify_peer' => false,
    'verify_peer_name' => false,
    'allow_self_signed' => true,
  ),
);

// provide an URL where a user can get support for this Roundcube installation
// PLEASE DO NOT LINK TO THE ROUNDCUBE.NET WEBSITE HERE!
$config['support_url'] = '';

// Other settings
$config['temp_dir'] = '/tmp/roundcube-temp';

// This key is used for encrypting purposes, like storing of imap password
// in the session. For historical reasons it's called DES_key, but it's used
// with any configured cipher_method (see below).
// For the default cipher_method a required key length is 24 characters.
$config['des_key'] = 'JdS+aGoxBtI1hnjQZtLXx2SF';

// Automatically add this domain to user names for login
// Only for IMAP servers that require full e-mail addresses for login
// Specify an array with 'host' => 'domain' values to support multiple hosts
// Supported replacement variables:
// %h - user's IMAP hostname
// %n - hostname ($_SERVER['SERVER_NAME'])
// %t - hostname without the first part
// %d - domain (http hostname $_SERVER['HTTP_HOST'] without the first part)
// %z - IMAP domain (IMAP hostname without the first part)
// For example %n = mail.domain.tld, %t = domain.tld
$config['username_domain'] = 'mithi.com';

// Specifies the full path of the original HTTP request, either as a real path or
// $_SERVER field name. This might be useful when Roundcube runs behind a reverse
// proxy using a subpath. This is a path part of the URL, not the full URL!
// The reverse proxy config can specify a custom header (e.g. X-Forwarded-Path) containing
// the path under which Roundcube is exposed to the outside world (e.g. /rcube/).
// This header value is then available in PHP with $_SERVER['HTTP_X_FORWARDED_PATH'].
// By default the path comes from  'REDIRECT_SCRIPT_URL', 'SCRIPT_NAME' or 'REQUEST_URI',
// whichever is set (in this order).
$config['request_path'] = '/';

// Plugins - initialize as array first
$config['plugins'] = ['archive', 'zipdownload', 'calendar_link'];

// Make use of the built-in spell checker.
$config['enable_spellcheck'] = true;

// Set the spell checking engine. Possible values:
// - 'googie'  - the default (also used for connecting to Nox Spell Server, see 'spellcheck_uri' setting)
// - 'pspell'  - requires the PHP Pspell module and aspell installed
// - 'enchant' - requires the PHP Enchant module
// - 'atd'     - install your own After the Deadline server or check with the people at http://www.afterthedeadline.com before using their API
// Since Google shut down their public spell checking service, the default settings
// connect to http://spell.roundcube.net which is a hosted service provided by Roundcube.
// You can connect to any other googie-compliant service by setting 'spellcheck_uri' accordingly.
$config['spellcheck_engine'] = 'pspell';

$config['zipdownload_selection'] = true;

// Calendar link configuration
$config['calendar_app_url'] = 'http://localhost:4200';

$config['calendar_link_text'] = '📅 Calendar App';

$config['calendar_open_new_tab'] = true;

$config['log_session'] = true;

include(__DIR__ . '/config.docker.inc.php');
