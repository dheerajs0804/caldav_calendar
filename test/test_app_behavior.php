<?php
/**
 * Test that mimics the actual app behavior
 */

echo "🧪 Testing App Behavior vs Automated Tests\n";
echo "==========================================\n\n";

// Test 1: Check if we can access the app endpoints directly
echo "1. Testing direct app endpoints...\n";

// Test login endpoint
$loginData = json_encode(['username' => 'test', 'password' => 'test']);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/auth/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'app_cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'app_cookies.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login Response ($httpCode): " . $response . "\n\n";

// Test 2: Check calendars endpoint
echo "2. Testing calendars endpoint...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/calendars');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, 'app_cookies.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Calendars Response ($httpCode): " . $response . "\n\n";

// Test 3: Check events endpoint
echo "3. Testing events endpoint...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/events');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, 'app_cookies.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Events Response ($httpCode): " . $response . "\n\n";

// Test 4: Check if CalDAV client is using environment variables
echo "4. Testing CalDAV client initialization...\n";
require_once __DIR__ . '/../backend/classes/CalDAVClient.php';

try {
    $client = new CalDAVClient();
    echo "✅ CalDAV Client initialized\n";
    echo "   🌐 Server URL: " . $client->getServerUrl() . "\n";
    echo "   👤 Username: " . $client->getUsername() . "\n";
    echo "   🔐 Password: " . ($client->getPassword() ? '***' : 'not set') . "\n";
    
    // Test calendar discovery
    echo "\n5. Testing calendar discovery...\n";
    $calendars = $client->discoverCalendars();
    if ($calendars && count($calendars) > 0) {
        echo "✅ Calendar discovery successful: " . count($calendars) . " calendars found\n";
        foreach ($calendars as $calendar) {
            echo "   📅 " . $calendar['name'] . " (" . $calendar['url'] . ")\n";
        }
    } else {
        echo "❌ Calendar discovery failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ CalDAV Client error: " . $e->getMessage() . "\n";
}

// Clean up
if (file_exists('app_cookies.txt')) {
    unlink('app_cookies.txt');
}

echo "\n✅ App behavior test complete!\n";
?>
