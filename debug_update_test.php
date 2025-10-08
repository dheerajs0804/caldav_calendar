<?php
// Debug script to test event update functionality
echo "Testing Event Update Debug\n";
echo "========================\n";

// Test the updateEvent function with minimal data
$testEventId = 'event_68e63dca5cfb6'; // Use a real event ID from our tests
$testData = [
    'title' => 'Updated Test Event',
    'description' => 'This event has been updated',
    'start_date' => date('Y-m-d'),
    'start_time' => date('H:i:s'),
    'end_date' => date('Y-m-d'),
    'end_time' => date('H:i:s', strtotime('+1 hour')),
    'calendar_id' => 1,
    'location' => 'Updated Location',
    'all_day' => false
];

echo "Test Event ID: $testEventId\n";
echo "Test Data: " . json_encode($testData) . "\n";

// Make a PUT request to test the update
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/events/$testEventId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "\nResponse Code: $httpCode\n";
echo "Response: $response\n";
if ($error) {
    echo "cURL Error: $error\n";
}

// Also test with a non-existent event ID
echo "\n--- Testing with non-existent event ID ---\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/events/nonexistent123");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Response Code: $httpCode\n";
echo "Response: $response\n";
if ($error) {
    echo "cURL Error: $error\n";
}
?>
