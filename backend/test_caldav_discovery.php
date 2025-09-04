<?php
// Comprehensive CalDAV Discovery Test
// Based on CalDAV standards and Apple implementation patterns

require_once 'classes/CalDAVClient.php';

echo "=== CalDAV Discovery Test ===\n";
echo "Testing the complete CalDAV discovery process...\n\n";

try {
    // Step 1: Initialize CalDAV Client with credentials
    echo "1. Initializing CalDAV Client...\n";
    $caldavClient = new CalDAVClient();
    echo "✅ CalDAVClient created successfully\n";
    echo "   Server URL: " . $caldavClient->getServerUrl() . "\n";
    echo "   Username: " . $caldavClient->getUsername() . "\n\n";

    // Step 2: Test Authentication
    echo "2. Testing Authentication...\n";
    $authToken = $caldavClient->getAuthToken();
    if (!$authToken) {
        echo "❌ Authentication failed - no auth token generated\n";
        exit(1);
    }
    echo "✅ Authentication successful\n\n";

    // Step 3: Discover Calendars (this implements the full CalDAV discovery process)
    echo "3. Discovering Calendars (Principal → Calendar Home → Calendar Collections)...\n";
    $result = $caldavClient->discoverCalendars();
    
    if ($result && isset($result['calendars'])) {
        echo "✅ Calendar discovery successful!\n";
        echo "   Found " . count($result['calendars']) . " calendars:\n\n";
        
        foreach ($result['calendars'] as $index => $calendar) {
            echo "   Calendar " . ($index + 1) . ":\n";
            echo "   ├─ Name: " . $calendar['name'] . "\n";
            echo "   ├─ URL: " . $calendar['url'] . "\n";
            echo "   ├─ Color: " . $calendar['color'] . "\n";
            if (isset($calendar['description']) && $calendar['description']) {
                echo "   └─ Description: " . $calendar['description'] . "\n";
            } else {
                echo "   └─ Description: (none)\n";
            }
            echo "\n";
        }
        
        // Step 4: Test fetching events from the first calendar
        if (!empty($result['calendars'])) {
            echo "4. Testing Event Fetching...\n";
            $firstCalendar = $result['calendars'][0];
            echo "   Testing with calendar: " . $firstCalendar['name'] . "\n";
            
            $events = $caldavClient->getEvents($firstCalendar['url']);
            if ($events && is_array($events)) {
                echo "✅ Event fetching successful!\n";
                echo "   Found " . count($events) . " events\n";
                
                // Show first few events
                $eventCount = min(3, count($events));
                for ($i = 0; $i < $eventCount; $i++) {
                    $event = $events[$i];
                    echo "   Event " . ($i + 1) . ": " . $event['title'] . "\n";
                    echo "      Time: " . $event['start_time'] . " to " . $event['end_time'] . "\n";
                    if ($event['location']) {
                        echo "      Location: " . $event['location'] . "\n";
                    }
                    echo "\n";
                }
            } else {
                echo "❌ Event fetching failed\n";
            }
        }
        
    } else {
        echo "❌ Calendar discovery failed\n";
        echo "   Result: " . print_r($result, true) . "\n";
    }

} catch (Exception $e) {
    echo "❌ Error during CalDAV discovery: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "=== Test Completed ===\n";
?>
