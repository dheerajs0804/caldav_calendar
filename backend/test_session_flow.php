<?php
// Test calendar discovery with session cookies
echo "=== Testing Calendar Discovery with Session ===\n";

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
    CURLOPT_COOKIEJAR => 'cookies.txt', // Save cookies
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login HTTP Status: $httpCode\n";
$loginResponse = json_decode($response, true);
if ($loginResponse && $loginResponse['success']) {
    echo "✅ Login successful\n";
} else {
    echo "❌ Login failed\n";
    exit(1);
}

// Now test calendar discovery with the same session
echo "\n2. Discovering calendars...\n";
$calendarUrl = 'http://localhost:8000/calendars/user';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $calendarUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_COOKIEFILE => 'cookies.txt', // Use saved cookies
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Calendar Discovery HTTP Status: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
} else {
    echo "Response: $response\n";
    
    $responseData = json_decode($response, true);
    if ($responseData && isset($responseData['success'])) {
        if ($responseData['success']) {
            echo "✅ Calendar discovery successful!\n";
            echo "Found " . count($responseData['data']['calendars']) . " calendars:\n";
            foreach ($responseData['data']['calendars'] as $calendar) {
                echo "- {$calendar['name']}: {$calendar['href']}\n";
            }
        } else {
            echo "❌ Calendar discovery failed: " . $responseData['message'] . "\n";
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
