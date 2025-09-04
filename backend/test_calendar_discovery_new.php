<?php
// Test the new calendar discovery functionality
require_once 'classes/CalDAVClient.php';

echo "Testing Calendar Discovery...\n";

try {
    // Test with environment variables
    $caldavClient = new CalDAVClient();
    echo "✅ CalDAVClient created successfully\n";
    
    // Test calendar discovery
    echo "🔍 Discovering calendars...\n";
    $result = $caldavClient->discoverCalendars();
    
    if ($result && isset($result['calendars'])) {
        echo "✅ Calendar discovery successful!\n";
        echo "Found " . count($result['calendars']) . " calendars:\n";
        
        foreach ($result['calendars'] as $calendar) {
            echo "  - " . $calendar['name'] . " (" . $calendar['url'] . ")\n";
            if (isset($calendar['description']) && $calendar['description']) {
                echo "    Description: " . $calendar['description'] . "\n";
            }
            echo "    Color: " . $calendar['color'] . "\n";
        }
    } else {
        echo "❌ Calendar discovery failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "Test completed!\n";
?>
