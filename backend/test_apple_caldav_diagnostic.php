<?php
// Advanced CalDAV Server Diagnostic based on Apple's CalDAV Client Library approach
echo "=== Advanced CalDAV Server Diagnostic ===\n";
echo "Based on Apple's CalDAV Client Library patterns\n\n";

$serverUrl = 'http://rc.mithi.com:8008';
$username = 'dheeraj.sharma@mithi.com';
$password = 'M!th!#567';
$authToken = base64_encode($username . ':' . $password);

echo "Server: $serverUrl\n";
echo "Username: $username\n\n";

// Test 1: Check server capabilities (like Apple's runshell.py)
echo "1. Checking server capabilities...\n";
$capabilitiesXml = '<?xml version="1.0" encoding="utf-8" ?>
<propfind xmlns="DAV:">
    <prop>
        <resourcetype/>
        <current-user-principal/>
        <calendar-home-set/>
        <calendar-user-address-set/>
        <supported-calendar-component-set/>
        <supported-calendar-data/>
        <supported-collation-set/>
        <supported-report-set/>
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
        'User-Agent: CalDAV-Diagnostic/1.0'
    ],
    CURLOPT_POSTFIELDS => $capabilitiesXml,
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
    echo "✅ Response received (Status: $httpCode)\n";
    if ($httpCode >= 200 && $httpCode < 300) {
        echo "✅ Server supports CalDAV protocol\n";
        echo "Response length: " . strlen($response) . " bytes\n";
        
        // Parse the response to check capabilities
        if (strpos($response, 'calendar-home-set') !== false) {
            echo "✅ Server supports calendar-home-set\n";
        }
        if (strpos($response, 'current-user-principal') !== false) {
            echo "✅ Server supports current-user-principal\n";
        }
        if (strpos($response, 'supported-calendar-component-set') !== false) {
            echo "✅ Server supports calendar components\n";
        }
        
        echo "\nResponse preview:\n" . substr($response, 0, 500) . "...\n";
    } else {
        echo "❌ Server returned error: $httpCode\n";
        echo "Response: " . substr($response, 0, 300) . "\n";
    }
}

// Test 2: Try different server paths (common CalDAV server configurations)
echo "\n2. Testing common CalDAV server paths...\n";
$paths = [
    '/',
    '/caldav/',
    '/calendars/',
    '/principals/',
    '/dav/',
    '/webdav/',
    '/.well-known/caldav',
    '/.well-known/dav',
    '/calendar/',
    '/user/',
    '/users/'
];

foreach ($paths as $path) {
    $url = $serverUrl . $path;
    echo "Testing: $url\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . $authToken,
            'User-Agent: CalDAV-Diagnostic/1.0'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "  Status: $httpCode\n";
    if ($httpCode === 200) {
        echo "  ✅ Accessible endpoint found!\n";
        break;
    } elseif ($httpCode === 401) {
        echo "  ⚠️  Authentication required\n";
    } elseif ($httpCode === 404) {
        echo "  ❌ Not found\n";
    } elseif ($httpCode === 405) {
        echo "  ❌ Method not allowed\n";
    }
}

// Test 3: Check for specific user principal path (Apple's pattern)
echo "\n3. Testing user principal discovery...\n";
$userPrincipalPaths = [
    "/principals/users/$username/",
    "/principals/__uids__/$username/",
    "/users/$username/",
    "/user/$username/",
    "/principals/$username/"
];

foreach ($userPrincipalPaths as $principalPath) {
    $url = $serverUrl . $principalPath;
    echo "Testing principal: $url\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . $authToken,
            'User-Agent: CalDAV-Diagnostic/1.0'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "  Status: $httpCode\n";
    if ($httpCode === 200) {
        echo "  ✅ User principal found!\n";
        break;
    }
}

echo "\n=== Diagnostic Complete ===\n";
echo "Based on Apple's CalDAV Client Library patterns\n";
echo "Reference: https://www.calendarserver.org/CalDAVClientLibrary.html\n";
?>

