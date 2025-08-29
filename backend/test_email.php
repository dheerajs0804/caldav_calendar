<?php
/**
 * Email Test Script
 * 
 * Test the email configuration and SMTP connection
 */

// Load email configuration
$emailConfig = require_once 'config/email.php';

echo "=== Email Configuration Test ===\n";
echo "Company SMTP Enabled: " . ($emailConfig['company_smtp']['enabled'] ? 'Yes' : 'No') . "\n";
echo "SMTP Host: " . $emailConfig['company_smtp']['host'] . "\n";
echo "SMTP Port: " . $emailConfig['company_smtp']['port'] . "\n";
echo "Security: " . $emailConfig['company_smtp']['security'] . "\n";
echo "Auth Type: " . $emailConfig['company_smtp']['auth_type'] . "\n";
echo "Use CalDAV Auth: " . ($emailConfig['company_smtp']['use_caldav_auth'] ? 'Yes' : 'No') . "\n";
echo "From Email: " . $emailConfig['company_smtp']['from_email'] . "\n";
echo "From Name: " . $emailConfig['company_smtp']['from_name'] . "\n";
echo "Debug: " . ($emailConfig['company_smtp']['debug'] ? 'Yes' : 'No') . "\n\n";

// Test SMTP connection
echo "=== Testing SMTP Connection ===\n";
try {
    $smtp = fsockopen(
        $emailConfig['company_smtp']['host'], 
        $emailConfig['company_smtp']['port'], 
        $errno, 
        $errstr, 
        $emailConfig['company_smtp']['timeout']
    );
    
    if (!$smtp) {
        echo "❌ Failed to connect to SMTP server: $errstr ($errno)\n";
    } else {
        echo "✅ Successfully connected to SMTP server\n";
        
        // Read server response
        $response = fgets($smtp, 515);
        echo "Server Response: " . trim($response) . "\n";
        
        // Test EHLO
        fputs($smtp, "EHLO " . gethostname() . "\r\n");
        $response = fgets($smtp, 515);
        echo "EHLO Response: " . trim($response) . "\n";
        
        // Test STARTTLS if required
        if ($emailConfig['company_smtp']['security'] === 'tls') {
            echo "Testing STARTTLS...\n";
            fputs($smtp, "STARTTLS\r\n");
            $response = fgets($smtp, 515);
            echo "STARTTLS Response: " . trim($response) . "\n";
            
            if (strpos($response, '220') === 0) {
                echo "✅ STARTTLS supported\n";
            } else {
                echo "❌ STARTTLS not supported\n";
            }
        }
        
        // Close connection
        fputs($smtp, "QUIT\r\n");
        fclose($smtp);
        echo "✅ SMTP connection test completed successfully\n";
    }
    
} catch (Exception $e) {
    echo "❌ SMTP connection test failed: " . $e->getMessage() . "\n";
}

// Test CalDAV integration
echo "\n=== Testing CalDAV Integration ===\n";
try {
    if (file_exists('classes/CalDAVClient.php')) {
        require_once 'classes/CalDAVClient.php';
        
        if (file_exists('config/caldav.php')) {
            $caldavConfig = require_once 'config/caldav.php';
            echo "CalDAV Config Found: Yes\n";
            echo "CalDAV Server: " . $caldavConfig['server_url'] . "\n";
            echo "CalDAV Username: " . $caldavConfig['username'] . "\n";
            echo "CalDAV Password: " . (strlen($caldavConfig['password']) > 0 ? '***' : 'Not Set') . "\n";
            
            // Test CalDAV client creation
            $caldavClient = new CalDAVClient(
                $caldavConfig['server_url'],
                $caldavConfig['username'],
                $caldavConfig['password']
            );
            
            echo "✅ CalDAV Client created successfully\n";
            echo "Username from client: " . $caldavClient->getUsername() . "\n";
            echo "Password from client: " . (strlen($caldavClient->getPassword()) > 0 ? '***' : 'Not Set') . "\n";
            
        } else {
            echo "❌ CalDAV config file not found\n";
        }
    } else {
        echo "❌ CalDAV client class not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ CalDAV integration test failed: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
