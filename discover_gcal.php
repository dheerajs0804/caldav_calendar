<?php
/**
 * Quick Gcal URL Discovery
 * This script will help find the gcal calendar URL
 */

echo "🔍 Discovering gcal calendar URL...\n";
echo "==================================\n\n";

// Include the CalDAV client
require_once 'backend/classes/CalDAVClient.php';

try {
    // Create CalDAV client
    $caldavClient = new CalDAVClient();
    
    echo "📡 Discovering calendars...\n";
    $calendars = $caldavClient->discoverCalendars();
    
    if ($calendars && is_array($calendars)) {
        echo "✅ Found " . count($calendars) . " calendars:\n\n";
        
        foreach ($calendars as $index => $calendar) {
            $name = $calendar['name'] ?? 'Unknown';
            $url = $calendar['href'] ?? '';
            $color = $calendar['color'] ?? 'No color';
            
            echo "Calendar " . ($index + 1) . ":\n";
            echo "  Name: $name\n";
            echo "  URL: $url\n";
            echo "  Color: $color\n";
            echo "  ---\n";
            
            // Check if this is gcal (case insensitive)
            if (strtolower($name) === 'gcal') {
                echo "  🟢 THIS IS GCAL!\n";
                echo "  🎯 Add this URL to calendar_colors.json with color #00ff00\n";
            }
        }
        
    } else {
        echo "❌ Failed to discover calendars\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n💡 Instructions:\n";
echo "1. Find the gcal calendar URL above\n";
echo "2. Add it to backend/data/calendar_colors.json with color #00ff00\n";
echo "3. Restart the backend server\n";
echo "4. Refresh the frontend\n";
?>
