<?php
/**
 * Find and Fix Gcal Color
 * This script will discover the gcal calendar and add green color
 */

echo "🔍 Finding and fixing gcal color...\n";
echo "==================================\n\n";

// Include the CalDAV client
require_once 'backend/classes/CalDAVClient.php';

try {
    // Create CalDAV client
    $caldavClient = new CalDAVClient();
    
    echo "📡 Discovering calendars...\n";
    $calendars = $caldavClient->discoverCalendars();
    
    if ($calendars && is_array($calendars)) {
        echo "✅ Found " . count($calendars) . " calendars:\n";
        
        $gcalFound = false;
        $gcalUrl = null;
        
        foreach ($calendars as $calendar) {
            $name = $calendar['name'] ?? 'Unknown';
            $url = $calendar['href'] ?? '';
            echo "   - $name: " . substr($url, 0, 60) . "...\n";
            
            // Check if this is gcal (case insensitive)
            if (strtolower($name) === 'gcal') {
                $gcalFound = true;
                $gcalUrl = $url;
                echo "     🟢 FOUND GCAL!\n";
            }
        }
        
        if ($gcalFound) {
            echo "\n🔧 Adding green color (#00ff00) to gcal...\n";
            
            // Load existing calendar colors
            $calendarColorsFile = 'backend/data/calendar_colors.json';
            $calendarColors = [];
            
            if (file_exists($calendarColorsFile)) {
                $calendarColors = json_decode(file_get_contents($calendarColorsFile), true) ?? [];
            }
            
            // Add green color for gcal
            $calendarColors[$gcalUrl] = '#00ff00'; // Bright green
            
            // Write back to file
            file_put_contents($calendarColorsFile, json_encode($calendarColors, JSON_PRETTY_PRINT));
            
            echo "✅ Added green color (#00ff00) to gcal\n";
            echo "📍 URL: " . substr($gcalUrl, 0, 80) . "...\n";
            
            // Show updated calendar colors
            echo "\n📊 Updated calendar colors:\n";
            foreach ($calendarColors as $url => $color) {
                $calendarName = basename($url);
                echo "   - $calendarName: $color\n";
            }
            
        } else {
            echo "\n❌ Gcal not found in discovered calendars\n";
            echo "💡 Make sure gcal exists and try again\n";
        }
        
    } else {
        echo "❌ Failed to discover calendars\n";
        echo "💡 Check CalDAV server connection\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎯 Next Steps:\n";
echo "1. Restart the backend server\n";
echo "2. Refresh the Angular frontend\n";
echo "3. Create a new event in gcal\n";
echo "4. The event should now appear in green!\n";
?>
