<?php
require_once 'classes/CalDAVClient.php';

echo "=== Testing Updated CalDAVClient (Roundcube Method) ===\n";

try {
    $client = new CalDAVClient();
    $calendars = $client->discoverCalendars();
    
    if ($calendars) {
        echo "✅ Found " . count($calendars) . " calendars:\n";
        foreach ($calendars as $calendar) {
            echo "- {$calendar['name']}: {$calendar['href']}\n";
        }
    } else {
        echo "❌ No calendars found or error occurred\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
