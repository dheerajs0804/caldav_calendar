<?php
// Test login and check for session cookies
echo "=== Testing Login and Session Cookies ===\n";

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
    CURLOPT_HEADER => true, // Include headers in response
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
    // Split response into headers and body
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    echo "Response Headers:\n$headers\n";
    echo "Response Body:\n$body\n";
    
    // Check for Set-Cookie header
    if (strpos($headers, 'Set-Cookie') !== false) {
        echo "✅ Session cookie found in response\n";
    } else {
        echo "❌ No session cookie found in response\n";
    }
}
?>
