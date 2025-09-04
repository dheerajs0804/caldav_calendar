<?php
// Test to debug the XML parsing issue
require_once 'classes/CalDAVClient.php';

echo "=== Testing CalDAVClient with Debug ===\n";

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
?>
