<?php
// Detailed test to debug the CalDAV request
echo "=== Detailed CalDAV Test ===\n";

$serverUrl = 'http://rc.mithi.com:18008';
$username = 'dheeraj.sharma@mithi.com';
$password = 'M!th!#567';

echo "Testing server: $serverUrl\n";
echo "Username: $username\n\n";

// Test 1: Simple PROPFIND request
$xml = '<?xml version="1.0" encoding="utf-8" ?>
<propfind xmlns="DAV:">
    <prop>
        <current-user-principal/>
    </prop>
</propfind>';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $serverUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PROPFIND',
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . base64_encode($username . ':' . $password),
        'Content-Type: application/xml; charset=utf-8',
        'Depth: 0',
        'User-Agent: CalDAVTest/1.0'
    ],
    CURLOPT_POSTFIELDS => $xml,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_VERBOSE => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
} else {
    echo "Response length: " . strlen($response) . " bytes\n";
    echo "Response preview:\n" . substr($response, 0, 500) . "\n";
    
    if ($httpCode >= 200 && $httpCode < 300) {
        echo "\n🎉 SUCCESS! CalDAV discovery should work!\n";
    } else {
        echo "\n❌ Still having issues with the request\n";
    }
}
?>
