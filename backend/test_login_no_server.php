<?php
// Test login endpoint without server URL
echo "=== Testing Login Without Server URL ===\n";

$url = 'http://localhost:8000/auth/login';
$data = [
    'username' => 'dheeraj.sharma@mithi.com',
    'password' => 'M!th!#567'
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
} else {
    echo "Response: $response\n";
    
    $responseData = json_decode($response, true);
    if ($responseData && isset($responseData['success'])) {
        if ($responseData['success']) {
            echo "✅ Login successful!\n";
            echo "Username: " . $responseData['data']['user']['username'] . "\n";
        } else {
            echo "❌ Login failed: " . $responseData['message'] . "\n";
        }
    } else {
        echo "❌ Invalid response format\n";
    }
}
?>
