<?php
// Test different CalDAV endpoints
echo "=== Testing Different CalDAV Endpoints ===\n";

$baseUrl = 'http://rc.mithi.com:8008';
$username = 'dheeraj.sharma@mithi.com';
$password = 'M!th!#567';
$authToken = base64_encode($username . ':' . $password);

$endpoints = [
    '/',
    '/caldav/',
    '/calendars/',
    '/principals/',
    '/dav/',
    '/webdav/',
    '/.well-known/caldav',
    '/.well-known/dav'
];

foreach ($endpoints as $endpoint) {
    $url = $baseUrl . $endpoint;
    echo "\nTesting: $url\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . $authToken,
            'User-Agent: CalDAV-Test/1.0'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "  ❌ Error: $error\n";
    } else {
        echo "  ✅ Status: $httpCode\n";
        if ($httpCode === 200) {
            echo "  ✅ Endpoint accessible\n";
        } elseif ($httpCode === 401) {
            echo "  ⚠️  Authentication required\n";
        } elseif ($httpCode === 404) {
            echo "  ❌ Not found\n";
        } elseif ($httpCode === 405) {
            echo "  ❌ Method not allowed\n";
        }
    }
}

echo "\n=== Endpoint Test Complete ===\n";
?>
