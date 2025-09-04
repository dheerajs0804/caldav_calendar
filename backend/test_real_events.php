<?php
// Test real event retrieval from CalDAV
echo "=== Testing Real Event Retrieval ===\n";

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

// Now get events from the main calendar
echo "\n2. Getting events from main calendar...\n";
$calendarUrl = 'http://localhost:8000/events?calendar_url=' . urlencode('http://rc.mithi.com:18008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/calendar/');

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $calendarUrl,
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
$error = curl_error($ch);
curl_close($ch);

echo "Events HTTP Status: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
} else {
    echo "Response: $response\n";
    
    $responseData = json_decode($response, true);
    if ($responseData && isset($responseData['success'])) {
        if ($responseData['success']) {
            echo "✅ Events retrieved successfully!\n";
            echo "Found " . count($responseData['data']) . " events:\n";
            foreach ($responseData['data'] as $event) {
                echo "- {$event['title']}: {$event['start_time']} to {$event['end_time']}\n";
            }
        } else {
            echo "❌ Events retrieval failed: " . $responseData['message'] . "\n";
        }
    } else {
        echo "❌ Invalid response format\n";
    }
}

// Clean up
if (file_exists('cookies.txt')) {
    unlink('cookies.txt');
}
?>
