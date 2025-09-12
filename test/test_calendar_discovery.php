<?php
// Test script to debug calendar discovery and ID assignment
require_once '../backend/src/CalDAV/CalDAVClient.php';

echo "=== Calendar Discovery Debug Test ===\n\n";

try {
    // Initialize CalDAV client
    $caldavClient = new CalDAVClient();
    
    // Discover calendars
    echo "1. Discovering calendars...\n";
    $calendars = $caldavClient->discoverCalendars();
    
    if ($calendars && is_array($calendars)) {
        echo "Found " . count($calendars) . " calendars:\n\n";
        
        foreach ($calendars as $index => $calendar) {
            $calendarId = $index + 1;
            echo "Calendar ID: $calendarId\n";
            echo "Name: " . $calendar['name'] . "\n";
            echo "URL: " . $calendar['href'] . "\n";
            echo "Color: " . ($calendar['color'] ?? 'not set') . "\n";
            echo "---\n";
        }
        
        // Load stored calendar colors
        echo "\n2. Loading stored calendar colors...\n";
        $calendarColorsFile = '../data/calendar_colors.json';
        $calendarColors = [];
        
        if (file_exists($calendarColorsFile)) {
            $calendarColors = json_decode(file_get_contents($calendarColorsFile), true) ?? [];
            echo "Stored colors:\n";
            foreach ($calendarColors as $url => $color) {
                echo "URL: $url\n";
                echo "Color: $color\n";
                echo "---\n";
            }
        } else {
            echo "No stored colors file found.\n";
        }
        
        // Test color inheritance logic
        echo "\n3. Testing color inheritance logic...\n";
        foreach ($calendars as $index => $calendar) {
            $calendarId = $index + 1;
            $calendarUrl = $calendar['href'];
            
            // Get stored color for this calendar URL, or use CalDAV color, or default
            $storedColor = $calendarColors[$calendarUrl] ?? null;
            $caldavColor = $calendar['color'] ?? null;
            $finalColor = $storedColor ?? $caldavColor ?? '#4285f4';
            
            echo "Calendar ID: $calendarId\n";
            echo "Name: " . $calendar['name'] . "\n";
            echo "Stored Color: " . ($storedColor ?? 'none') . "\n";
            echo "CalDAV Color: " . ($caldavColor ?? 'none') . "\n";
            echo "Final Color: $finalColor\n";
            echo "---\n";
        }
        
    } else {
        echo "No calendars found or error occurred.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
