<?php
// Simple test to check if CalDAVClient can be instantiated
require_once 'classes/CalDAVClient.php';

echo "Testing CalDAVClient instantiation...\n";

try {
    // Test with environment variables
    $caldavClient = new CalDAVClient();
    echo "✅ CalDAVClient created successfully\n";
    
    // Test basic methods
    echo "Server URL: " . $caldavClient->getServerUrl() . "\n";
    echo "Username: " . $caldavClient->getUsername() . "\n";
    
    // Test authentication token
    $authToken = $caldavClient->getAuthToken();
    if ($authToken) {
        echo "✅ Authentication token generated successfully\n";
    } else {
        echo "❌ Failed to generate authentication token\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "Test completed!\n";
?>
