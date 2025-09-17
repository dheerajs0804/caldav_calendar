<?php
// Test script to check recurrence parsing
require_once 'classes/CalDAVClient.php';

// Test RRULE parsing
$testRRULE = "FREQ=DAILY;INTERVAL=1;COUNT=2";

echo "Testing RRULE parsing:\n";
echo "Input RRULE: " . $testRRULE . "\n";

// Create a mock CalDAV client to test the parseRRULE method
class TestCalDAVClient extends CalDAVClient {
    public function testParseRRULE($rrule) {
        return $this->parseRRULE($rrule);
    }
    
    public function testParseICalendarDate($dateString) {
        return $this->parseICalendarDate($dateString);
    }
}

$testClient = new TestCalDAVClient('http://test.com', 'user', 'pass');

// Use reflection to access private method
$reflection = new ReflectionClass($testClient);
$parseRRULEMethod = $reflection->getMethod('parseRRULE');
$parseRRULEMethod->setAccessible(true);

$result = $parseRRULEMethod->invoke($testClient, $testRRULE);

echo "Parsed result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

// Test iCalendar parsing
$testICal = "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//EN
BEGIN:VEVENT
UID:test-event-123
DTSTAMP:20250917T120000Z
DTSTART;TZID=Asia/Kolkata:20250917T122900
DTEND;TZID=Asia/Kolkata:20250917T132900
SUMMARY:Test Recurring Event
RRULE:FREQ=DAILY;INTERVAL=1;COUNT=2
STATUS:CONFIRMED
TRANSP:OPAQUE
END:VEVENT
END:VCALENDAR";

echo "\nTesting iCalendar parsing:\n";
echo "Input iCalendar:\n" . $testICal . "\n";

$parseICalendarMethod = $reflection->getMethod('parseICalendarData');
$parseICalendarMethod->setAccessible(true);

$event = $parseICalendarMethod->invoke($testClient, $testICal);

echo "Parsed event: " . json_encode($event, JSON_PRETTY_PRINT) . "\n";
?>
