<?php
/**
 * Test Calendar Colors Loading
 * This script tests if the calendar colors are being loaded correctly
 */

echo "🧪 Testing Calendar Colors Loading...\n";
echo "====================================\n\n";

// Test 1: Check if calendar_colors.json exists and is readable
$calendarColorsFile = 'backend/data/calendar_colors.json';
echo "1. Checking calendar_colors.json file:\n";
if (file_exists($calendarColorsFile)) {
    echo "   ✅ File exists\n";
    $content = file_get_contents($calendarColorsFile);
    echo "   📄 File size: " . strlen($content) . " bytes\n";
    echo "   📄 Content preview:\n";
    echo "   " . substr($content, 0, 200) . "...\n\n";
} else {
    echo "   ❌ File does not exist\n\n";
    exit(1);
}

// Test 2: Parse JSON content
echo "2. Parsing JSON content:\n";
$calendarColors = json_decode($content, true);
if ($calendarColors === null) {
    echo "   ❌ Failed to parse JSON: " . json_last_error_msg() . "\n\n";
    exit(1);
} else {
    echo "   ✅ JSON parsed successfully\n";
    echo "   📊 Found " . count($calendarColors) . " calendar colors\n\n";
}

// Test 3: Check for gcal URL
echo "3. Looking for gcal calendar:\n";
$gcalUrl = 'http://rc.mithi.com:18008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/270504F1-5121-3A64-AD06-EA742A150C39/';
$gcalColor = $calendarColors[$gcalUrl] ?? null;

if ($gcalColor) {
    echo "   ✅ Found gcal color: $gcalColor\n";
    if ($gcalColor === '#00ff00') {
        echo "   ✅ Color is correct (green)\n";
    } else {
        echo "   ❌ Color is wrong (expected #00ff00, got $gcalColor)\n";
    }
} else {
    echo "   ❌ gcal color not found\n";
    echo "   🔍 Available URLs:\n";
    foreach (array_keys($calendarColors) as $url) {
        echo "      - " . substr($url, 0, 80) . "...\n";
    }
}

echo "\n4. Testing URL matching:\n";
$testUrls = [
    'http://rc.mithi.com:18008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/270504F1-5121-3A64-AD06-EA742A150C39/',
    'http://rc.mithi.com:18008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/270504F1-5121-3A64-AD06-EA742A150C39',
    'http://rc.mithi.com:18008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/270504F1-5121-3A64-AD06-EA742A150C39/',
];

foreach ($testUrls as $testUrl) {
    $color = $calendarColors[$testUrl] ?? 'NOT FOUND';
    echo "   URL: " . substr($testUrl, -20) . " -> Color: $color\n";
}

echo "\n🎯 Summary:\n";
if ($gcalColor === '#00ff00') {
    echo "✅ Calendar colors are correctly configured\n";
    echo "✅ gcal should appear green\n";
} else {
    echo "❌ Calendar colors need to be fixed\n";
    echo "💡 Check the URL format in calendar_colors.json\n";
}
?>
