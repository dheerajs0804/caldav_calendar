<?php
// Test calendar discovery endpoint
echo "=== Testing Calendar Discovery Endpoint ===\n";

$url = 'http://localhost:8000/calendars/user';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET => true,
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
?>
