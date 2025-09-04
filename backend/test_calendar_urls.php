<?php
// Test to see what calendar URLs are being discovered
echo "=== Testing Calendar URL Discovery ===\n";

// First, login to get session
echo "1. Logging in...\n";
$loginUrl = 'http://localhost:8000/auth/login';
$loginData = [
    'username' => 'dheeraj.sharma@mithi.com',
    'password' => 'M!th!#567'
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $loginUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($loginData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_COOKIEJAR => 'cookies.txt',
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login HTTP Status: $httpCode\n";
$loginResponse = json_decode($response, true);
if (!$loginResponse || !$loginResponse['success']) {
    echo "❌ Login failed\n";
    exit(1);
}
echo "✅ Login successful\n";

// Now get calendar list
echo "\n2. Getting calendar list...\n";
$calendarListUrl = 'http://localhost:8000/calendars/user';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $calendarListUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_COOKIEFILE => 'cookies.txt',
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Calendar List HTTP Status: $httpCode\n";
$calendarResponse = json_decode($response, true);

if ($calendarResponse && $calendarResponse['success']) {
    echo "✅ Calendar list retrieved successfully!\n";
    echo "Found " . count($calendarResponse['data']['calendars']) . " calendars:\n";
    foreach ($calendarResponse['data']['calendars'] as $i => $calendar) {
        echo "Calendar " . ($i + 1) . ":\n";
        echo "  Name: {$calendar['name']}\n";
        echo "  HREF: {$calendar['href']}\n";
        echo "  Color: {$calendar['color']}\n";
        echo "  Description: {$calendar['description']}\n";
        echo "\n";
    }
} else {
    echo "❌ Failed to get calendar list\n";
    exit(1);
}

// Test events without specifying calendar URL (should use first calendar)
echo "\n3. Testing events without calendar URL (should use first calendar)...\n";
$eventsUrl = 'http://localhost:8000/events';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $eventsUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_COOKIEFILE => 'cookies.txt',
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Events HTTP Status: $httpCode\n";
$eventsResponse = json_decode($response, true);

if ($eventsResponse && $eventsResponse['success']) {
    echo "✅ Events retrieved successfully!\n";
    echo "Calendar URL used: {$eventsResponse['calendar_url']}\n";
    echo "Found " . count($eventsResponse['data']) . " events:\n";
    foreach ($eventsResponse['data'] as $event) {
        echo "- {$event['title']}: {$event['start_time']} to {$event['end_time']}\n";
    }
} else {
    echo "❌ Failed to get events\n";
}

// Clean up
if (file_exists('cookies.txt')) {
    unlink('cookies.txt');
}
?>
