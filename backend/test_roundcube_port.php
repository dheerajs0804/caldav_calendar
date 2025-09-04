<?php
// Test to check if CalDAV server is accessible on port 18008 (Roundcube's config)
// vs port 8008 (our current config)

echo "=== Testing CalDAV Server Ports ===\n";
echo "Based on Roundcube configuration analysis\n\n";

$ports = [8008, 18008];
$server = 'rc.mithi.com';
$username = 'dheeraj.sharma@mithi.com';
$password = 'M!th!#567';

foreach ($ports as $port) {
    echo "Testing port $port...\n";
    
    $url = "http://$server:$port";
    
    // Test 1: Basic connectivity
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ Connection error: $error\n";
        continue;
    }
    
    echo "✅ HTTP Status: $httpCode\n";
    
    // Test 2: PROPFIND request (CalDAV method)
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PROPFIND',
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . base64_encode($username . ':' . $password),
            'Content-Type: application/xml; charset=utf-8',
            'Depth: 0',
            'User-Agent: CalDAVTest/1.0'
        ],
        CURLOPT_POSTFIELDS => '<?xml version="1.0" encoding="utf-8" ?>
<propfind xmlns="DAV:">
    <prop>
        <current-user-principal/>
    </prop>
</propfind>',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ PROPFIND error: $error\n";
    } else {
        echo "✅ PROPFIND Status: $httpCode\n";
        if ($httpCode >= 200 && $httpCode < 300) {
            echo "🎉 SUCCESS! Port $port supports CalDAV protocol!\n";
            echo "Response preview: " . substr($response, 0, 200) . "...\n";
        } else {
            echo "❌ PROPFIND not supported on port $port\n";
            echo "Response: " . substr($response, 0, 200) . "...\n";
        }
    }
    
    echo "\n";
}

echo "=== Test Complete ===\n";
?>
