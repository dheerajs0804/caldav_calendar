<?php
/**
 * Simple Authentication Test
 */

// Test authentication flow
echo "🔐 Testing Authentication Flow\n";
echo "=============================\n\n";

// Test 1: Login
echo "1. Testing Login...\n";
$loginData = json_encode(['username' => 'test', 'password' => 'test']);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/auth/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login Response ($httpCode): " . $response . "\n\n";

// Test 2: Check Auth Status
echo "2. Testing Auth Status...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/auth/status');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Auth Status Response ($httpCode): " . $response . "\n\n";

// Test 3: Try Calendar Discovery
echo "3. Testing Calendar Discovery...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/calendars');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Calendar Discovery Response ($httpCode): " . $response . "\n\n";

// Clean up
if (file_exists('cookies.txt')) {
    unlink('cookies.txt');
}

echo "✅ Authentication test complete!\n";
?>
