<?php
// Simple login test to isolate the issue
echo "=== Login Test ===\n";

// Simulate the login request
$input = [
    'serverUrl' => 'http://rc.mithi.com:8008',
    'username' => 'dheeraj.sharma@mithi.com',
    'password' => 'M!th!#567'
];

echo "1. Testing with credentials:\n";
echo "   Server URL: " . $input['serverUrl'] . "\n";
echo "   Username: " . $input['username'] . "\n";
echo "   Password: [HIDDEN]\n\n";

try {
    echo "2. Creating CalDAVClient...\n";
    $caldavClient = new CalDAVClient($input['serverUrl'], $input['username'], $input['password']);
    echo "✅ CalDAVClient created successfully\n\n";
    
    echo "3. Testing authentication token...\n";
    $authToken = $caldavClient->getAuthToken();
    if ($authToken) {
        echo "✅ Authentication token generated\n\n";
    } else {
        echo "❌ Failed to generate authentication token\n";
        exit(1);
    }
    
    echo "4. Testing calendar discovery...\n";
    $calendars = $caldavClient->discoverCalendars();
    
    if ($calendars && isset($calendars['calendars'])) {
        echo "✅ Calendar discovery successful!\n";
        echo "   Found " . count($calendars['calendars']) . " calendars\n";
    } else {
        echo "❌ Calendar discovery failed\n";
        echo "   Result: " . print_r($calendars, true) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "=== Test Completed ===\n";
?>
