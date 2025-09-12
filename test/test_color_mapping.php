<?php
// Simple test to debug calendar discovery and color mapping
echo "=== Calendar Discovery and Color Mapping Test ===\n\n";

// Load calendar colors
$calendarColorsFile = '../data/calendar_colors.json';
$calendarColors = [];

if (file_exists($calendarColorsFile)) {
    $calendarColors = json_decode(file_get_contents($calendarColorsFile), true) ?? [];
    echo "1. Loaded calendar colors:\n";
    foreach ($calendarColors as $url => $color) {
        echo "   URL: $url\n";
        echo "   Color: $color\n";
        echo "   ---\n";
    }
} else {
    echo "1. No calendar colors file found.\n";
}

// Simulate calendar discovery (based on the CalDAV client logic)
echo "\n2. Simulating calendar discovery...\n";

// These are the URLs that would be discovered by CalDAV
$discoveredCalendars = [
    [
        'name' => 'Calendar 1',
        'href' => 'http://rc.mithi.com:18008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/52FC2A7E-ACAF-DB42-DB5D-883657574B6D/',
        'color' => null
    ],
    [
        'name' => 'Calendar 2', 
        'href' => 'http://rc.mithi.com:18008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/B6A24496-9482-1561-F12A-05ACF41A1FF4/',
        'color' => null
    ],
    [
        'name' => 'redcal',
        'href' => 'http://rc.mithi.com:18008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/29186E4D-03B1-3410-16C5-2F6239B60681/',
        'color' => null
    ]
];

echo "Discovered calendars:\n";
foreach ($discoveredCalendars as $index => $calendar) {
    $calendarId = $index + 1;
    $calendarUrl = $calendar['href'];
    
    // Get stored color for this calendar URL, or use CalDAV color, or default
    $storedColor = $calendarColors[$calendarUrl] ?? null;
    $caldavColor = $calendar['color'] ?? null;
    $finalColor = $storedColor ?? $caldavColor ?? '#4285f4';
    
    echo "   Calendar ID: $calendarId\n";
    echo "   Name: " . $calendar['name'] . "\n";
    echo "   URL: $calendarUrl\n";
    echo "   Stored Color: " . ($storedColor ?? 'none') . "\n";
    echo "   CalDAV Color: " . ($caldavColor ?? 'none') . "\n";
    echo "   Final Color: $finalColor\n";
    echo "   ---\n";
}

echo "\n3. Testing event creation for calendar ID 3 (redcal):\n";
$testCalendarId = 3;
if (isset($discoveredCalendars[$testCalendarId - 1])) {
    $calendar = $discoveredCalendars[$testCalendarId - 1];
    $calendarUrl = $calendar['href'];
    
    $storedColor = $calendarColors[$calendarUrl] ?? null;
    $caldavColor = $calendar['color'] ?? null;
    $eventColor = $storedColor ?? $caldavColor ?? '#4285f4';
    
    echo "   Calendar ID: $testCalendarId\n";
    echo "   Calendar Name: " . $calendar['name'] . "\n";
    echo "   Calendar URL: $calendarUrl\n";
    echo "   Event Color: $eventColor\n";
    
    if ($eventColor === '#ff0000') {
        echo "   ✅ SUCCESS: Event should be red!\n";
    } else {
        echo "   ❌ PROBLEM: Event will be $eventColor instead of red\n";
    }
} else {
    echo "   ❌ Calendar ID $testCalendarId not found!\n";
}

echo "\n=== Test Complete ===\n";
?>
