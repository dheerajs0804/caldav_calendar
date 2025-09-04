<?php
// Test integration of the new calendar discovery
require_once 'classes/CalDAVClient.php';

echo "Testing CalDAVClient integration...\n";

try {
    // Test with environment variables
    $caldavClient = new CalDAVClient();
    echo "CalDAVClient created successfully\n";
    
    // Test calendar discovery
    $result = $caldavClient->discoverCalendars();
    
    if ($result && isset($result['calendars'])) {
        echo "✅ Calendar discovery successful!\n";
        echo "Found " . count($result['calendars']) . " calendars:\n";
        
        foreach ($result['calendars'] as $calendar) {
            echo "  - " . $calendar['name'] . " (" . $calendar['url'] . ")\n";
        }
    } else {
        echo "❌ Calendar discovery failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "Test completed!\n";
?>
