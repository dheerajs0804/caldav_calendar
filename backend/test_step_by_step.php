<?php
// Simple test to see what happens after getting current-user-principal
require_once 'classes/CalDAVClient.php';

echo "=== Step-by-Step CalDAV Test ===\n";

$client = new CalDAVClient();

// Test just the first PROPFIND request
$serverUrl = 'http://rc.mithi.com:18008';
$currentUserPrincipal = array('{DAV:}current-user-principal');
$calAttribs = array('{DAV:}resourcetype', '{DAV:}displayname');

echo "Making first PROPFIND request to: $serverUrl\n";

// Use reflection to access the private propFind method
$reflection = new ReflectionClass($client);
$propFindMethod = $reflection->getMethod('propFind');
$propFindMethod->setAccessible(true);

$response = $propFindMethod->invoke($client, $serverUrl, array_merge($currentUserPrincipal, $calAttribs), 0);

if ($response) {
    echo "✅ First PROPFIND successful!\n";
    echo "Response keys: " . implode(', ', array_keys($response)) . "\n";
    
    // Debug: show the full response
    echo "Full response:\n";
    print_r($response);
    
    if (isset($response['{DAV:}current-user-principal'])) {
        $principalUrl = $serverUrl . $response['{DAV:}current-user-principal'];
        echo "✅ Found principal URL: $principalUrl\n";
    } else {
        echo "❌ No current-user-principal found in response\n";
        echo "Available keys: " . implode(', ', array_keys($response)) . "\n";
    }
} else {
    echo "❌ First PROPFIND failed\n";
}
?>
