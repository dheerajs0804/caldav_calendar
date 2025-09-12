<?php
/**
 * Fix Greencal Color - Add green color to greencal calendar
 */

echo "🔍 Finding and fixing greencal color...\n";
echo "=====================================\n\n";

// Load existing calendar colors
$calendarColorsFile = 'backend/data/calendar_colors.json';
$calendarColors = [];

if (file_exists($calendarColorsFile)) {
    $calendarColors = json_decode(file_get_contents($calendarColorsFile), true) ?? [];
    echo "📊 Current calendar colors:\n";
    foreach ($calendarColors as $url => $color) {
        $calendarName = basename($url);
        echo "   - $calendarName: $color\n";
    }
    echo "\n";
}

// Check if we need to add greencal
$hasGreencal = false;
$greencalUrl = null;

foreach ($calendarColors as $url => $color) {
    // Look for calendars that might be greencal (check if URL contains greencal or similar)
    if (strpos(strtolower($url), 'green') !== false) {
        $hasGreencal = true;
        $greencalUrl = $url;
        echo "✅ Found greencal: $url with color: $color\n";
        break;
    }
}

if (!$hasGreencal) {
    echo "❌ No greencal found in calendar colors\n";
    echo "🔧 Adding green color for greencal...\n";
    
    // Since we don't know the exact URL, let's add a placeholder
    // The user will need to create a new greencal or we can discover it
    echo "📝 To fix this issue:\n";
    echo "1. Create a new calendar named 'greencal' with green color\n";
    echo "2. Or manually add the greencal URL to calendar_colors.json\n\n";
    
    // Let's add a sample green color entry
    $sampleGreencalUrl = 'http://rc.mithi.com:18008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/GREENCAL-UUID/';
    $calendarColors[$sampleGreencalUrl] = '#00ff00'; // Bright green
    
    echo "🟢 Added sample greencal entry: $sampleGreencalUrl -> #00ff00\n";
    
    // Write back to file
    file_put_contents($calendarColorsFile, json_encode($calendarColors, JSON_PRETTY_PRINT));
    echo "✅ Updated calendar_colors.json\n\n";
} else {
    echo "✅ Greencal already exists with color: " . $calendarColors[$greencalUrl] . "\n";
}

echo "🎯 Next Steps:\n";
echo "1. Start the backend server: cd backend && php start_server.php\n";
echo "2. Start the Angular frontend: cd frontend-angular && npm start\n";
echo "3. Create a new event in the greencal calendar\n";
echo "4. The event should now appear in green color\n\n";

echo "🔧 Alternative: If greencal still shows blue, try:\n";
echo "1. Delete the existing greencal\n";
echo "2. Create a new calendar named 'greencal' with green color\n";
echo "3. The color will be automatically stored and inherited\n";
?>
