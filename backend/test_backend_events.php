<?php
// Test script to check what the backend returns for events
require_once 'classes/CalDAVClient.php';

// Load environment variables
if (file_exists('env.txt')) {
    $envContent = file_get_contents('env.txt');
    $lines = explode("\n", $envContent);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '=') !== false && !empty($line)) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Set up session (simulate a logged-in user)
session_start();
$_SESSION['caldav_credentials'] = [
    'serverUrl' => $_ENV['CALDAV_SERVER_URL'] ?? 'http://rc.mithi.com:8008',
    'username' => 'mithi',
    'password' => 'mithi123'
];

// Test the getEvents function
function testGetEvents() {
    try {
        // Check if user is authenticated via session credentials
        if (!isset($_SESSION['caldav_credentials']) || empty($_SESSION['caldav_credentials']['username'])) {
            echo "User not authenticated\n";
            return;
        }
        
        $credentials = $_SESSION['caldav_credentials'];
        $caldavClient = new CalDAVClient($credentials['serverUrl'], $credentials['username'], $credentials['password']);
        
        // Get calendars first
        $calendars = $caldavClient->discoverCalendars();
        if (empty($calendars)) {
            echo "No calendars found\n";
            return;
        }
        
        $calendarUrl = $calendars[0]['href'];
        echo "Testing with calendar URL: " . $calendarUrl . "\n";
        
        // Get date range for events (default to current month)
        $startDate = date('Ymd\THis\Z', strtotime('-1 month'));
        $endDate = date('Ymd\THis\Z', strtotime('+1 month'));
        
        echo "Date range: " . $startDate . " to " . $endDate . "\n";
        
        // Fetch real events from CalDAV server
        $events = $caldavClient->getEvents($calendarUrl, $startDate, $endDate);
        
        echo "Found " . count($events) . " events\n";
        
        foreach ($events as $index => $event) {
            echo "\nEvent " . ($index + 1) . ":\n";
            echo "  Title: " . ($event['title'] ?? 'N/A') . "\n";
            echo "  UID: " . ($event['uid'] ?? 'N/A') . "\n";
            echo "  Start: " . ($event['start_time'] ?? 'N/A') . "\n";
            echo "  End: " . ($event['end_time'] ?? 'N/A') . "\n";
            echo "  Recurrence: " . json_encode($event['recurrence'] ?? null) . "\n";
            echo "  Status: " . ($event['status'] ?? 'N/A') . "\n";
            echo "  Availability: " . ($event['availability'] ?? 'N/A') . "\n";
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

testGetEvents();
?>
