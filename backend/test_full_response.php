<?php
// Test to see the full CalDAV response
echo "=== Full CalDAV Response Test ===\n";

$serverUrl = 'http://rc.mithi.com:18008';
$username = 'dheeraj.sharma@mithi.com';
$password = 'M!th!#567';

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
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Full Response:\n$response\n";
?>
