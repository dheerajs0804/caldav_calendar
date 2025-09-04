<?php
// Test CalDAV server connectivity and identify specific issues
echo "=== CalDAV Server Diagnostic Test ===\n";

$serverUrl = 'http://rc.mithi.com:8008';
$username = 'dheeraj.sharma@mithi.com';
$password = 'M!th!#567';

echo "Testing connection to: $serverUrl\n";
echo "Username: $username\n\n";

try {
    // Test 1: Basic HTTP connection
    echo "1. Testing basic HTTP connection...\n";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $serverUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ Connection error: $error\n";
    } else {
        echo "✅ HTTP connection successful (Status: $httpCode)\n";
    }
    
    // Test 2: Basic authentication
    echo "\n2. Testing basic authentication...\n";
    $authToken = base64_encode($username . ':' . $password);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $serverUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
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
        echo "❌ Authentication error: $error\n";
    } else {
        echo "✅ Authentication successful (Status: $httpCode)\n";
        if ($httpCode === 401) {
            echo "⚠️  Server returned 401 - credentials may be invalid\n";
        }
    }
    
    // Test 3: PROPFIND request to root
    echo "\n3. Testing PROPFIND request...\n";
    $propfindXml = '<?xml version="1.0" encoding="utf-8" ?>
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
            'Authorization: Basic ' . $authToken,
            'Content-Type: application/xml; charset=utf-8',
            'Depth: 0',
            'User-Agent: CalDAV-Test/1.0'
        ],
        CURLOPT_POSTFIELDS => $propfindXml,
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
        echo "✅ PROPFIND request successful (Status: $httpCode)\n";
        if ($httpCode >= 200 && $httpCode < 300) {
            echo "✅ Server supports CalDAV protocol\n";
            echo "Response length: " . strlen($response) . " bytes\n";
            if (strlen($response) > 0) {
                echo "Response preview: " . substr($response, 0, 200) . "...\n";
            }
        } else {
            echo "❌ Server returned error status: $httpCode\n";
            echo "Response: " . substr($response, 0, 500) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== Diagnostic Complete ===\n";
?>
