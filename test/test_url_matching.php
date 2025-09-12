<?php
// Test URL matching between discovery and stored colors
echo "=== URL Matching Test ===\n\n";

// Load stored calendar colors
$calendarColorsFile = '../data/calendar_colors.json';
$calendarColors = json_decode(file_get_contents($calendarColorsFile), true) ?? [];

echo "1. Stored calendar colors:\n";
foreach ($calendarColors as $url => $color) {
    echo "   URL: $url\n";
    echo "   Color: $color\n";
    echo "   ---\n";
}

// Simulate calendar discovery URLs (what CalDAV client would return)
echo "\n2. Simulated calendar discovery URLs:\n";
$discoveredUrls = [
    'http://rc.mithi.com:18008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/52FC2A7E-ACAF-DB42-DB5D-883657574B6D/',
    'http://rc.mithi.com:18008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/B6A24496-9482-1561-F12A-05ACF41A1FF4/',
    'http://rc.mithi.com:18008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/29186E4D-03B1-3410-16C5-2F6239B60681/'
];

foreach ($discoveredUrls as $index => $url) {
    $calendarId = $index + 1;
    $storedColor = $calendarColors[$url] ?? 'NOT FOUND';
    
    echo "   Calendar ID: $calendarId\n";
    echo "   URL: $url\n";
    echo "   Stored Color: $storedColor\n";
    echo "   ---\n";
}

echo "\n3. Testing calendar ID 3 (redcal):\n";
$testUrl = $discoveredUrls[2]; // Index 2 = Calendar ID 3
$testColor = $calendarColors[$testUrl] ?? 'NOT FOUND';

echo "   URL: $testUrl\n";
echo "   Color: $testColor\n";

if ($testColor === '#ff0000') {
    echo "   ✅ SUCCESS: URL matches and color is red!\n";
} else {
    echo "   ❌ PROBLEM: URL match failed or color is not red\n";
}

echo "\n=== Test Complete ===\n";
?>
