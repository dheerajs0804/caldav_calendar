<?php
/**
 * Test Environment Variables Loading
 */

echo "🧪 Testing Environment Variables Loading\n";
echo "=====================================\n\n";

// Test 1: Check if test.env file exists
$testEnvFile = __DIR__ . '/../backend/test.env';
echo "1. Checking test.env file...\n";
if (file_exists($testEnvFile)) {
    echo "✅ test.env file exists: " . $testEnvFile . "\n";
    echo "📄 Contents:\n";
    echo file_get_contents($testEnvFile) . "\n";
} else {
    echo "❌ test.env file not found\n";
}

// Test 2: Load environment variables
echo "\n2. Loading environment variables...\n";
$envFile = __DIR__ . '/../backend/test.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $_ENV[$key] = $value;
            echo "   📝 Loaded: $key = $value\n";
        }
    }
}

// Test 3: Check loaded variables
echo "\n3. Checking loaded variables...\n";
$caldavVars = ['CALDAV_SERVER_URL', 'CALDAV_USERNAME', 'CALDAV_PASSWORD'];
foreach ($caldavVars as $var) {
    if (isset($_ENV[$var])) {
        echo "✅ $var = " . $_ENV[$var] . "\n";
    } else {
        echo "❌ $var not set\n";
    }
}

// Test 4: Test CalDAV Client initialization
echo "\n4. Testing CalDAV Client initialization...\n";
require_once __DIR__ . '/../backend/classes/CalDAVClient.php';

try {
    $client = new CalDAVClient();
    echo "✅ CalDAV Client initialized successfully\n";
    echo "   🌐 Server URL: " . $client->getServerUrl() . "\n";
    echo "   👤 Username: " . $client->getUsername() . "\n";
    echo "   🔐 Password: " . ($client->getPassword() ? '***' : 'not set') . "\n";
} catch (Exception $e) {
    echo "❌ CalDAV Client initialization failed: " . $e->getMessage() . "\n";
}

echo "\n✅ Environment test complete!\n";
?>
