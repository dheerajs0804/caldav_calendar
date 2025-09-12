<?php
/**
 * Auto-fix Greencal Color
 * This script will discover calendars and automatically add green color to greencal
 */

echo "🔍 Auto-fixing greencal color...\n";
echo "==============================\n\n";

// Include the CalDAV client
require_once 'backend/classes/CalDAVClient.php';

try {
    // Create CalDAV client
    $caldavClient = new CalDAVClient();
    
    echo "📡 Discovering calendars...\n";
    $calendars = $caldavClient->discoverCalendars();
    
    if ($calendars && is_array($calendars)) {
        echo "✅ Found " . count($calendars) . " calendars:\n";
        
        $greencalFound = false;
        $greencalUrl = null;
        
        foreach ($calendars as $calendar) {
            $name = $calendar['name'] ?? 'Unknown';
            $url = $calendar['href'] ?? '';
            echo "   - $name: " . substr($url, 0, 60) . "...\n";
            
            // Check if this is greencal (case insensitive)
            if (strtolower($name) === 'greencal') {
                $greencalFound = true;
                $greencalUrl = $url;
                echo "     🟢 FOUND GREENCAL!\n";
            }
        }
        
        if ($greencalFound) {
            echo "\n🔧 Adding green color to greencal...\n";
            
            // Load existing calendar colors
            $calendarColorsFile = 'backend/data/calendar_colors.json';
            $calendarColors = [];
            
            if (file_exists($calendarColorsFile)) {
                $calendarColors = json_decode(file_get_contents($calendarColorsFile), true) ?? [];
            }
            
            // Add green color for greencal
            $calendarColors[$greencalUrl] = '#00ff00'; // Bright green
            
            // Write back to file
            file_put_contents($calendarColorsFile, json_encode($calendarColors, JSON_PRETTY_PRINT));
            
            echo "✅ Added green color (#00ff00) to greencal\n";
            echo "📍 URL: " . substr($greencalUrl, 0, 80) . "...\n";
            
        } else {
            echo "\n❌ Greencal not found in discovered calendars\n";
            echo "💡 Make sure greencal exists and try again\n";
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
echo "3. Create a new event in greencal\n";
echo "4. The event should now appear in green!\n";
?>
