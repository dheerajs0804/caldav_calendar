<?php
// Simple environment test
echo "=== Environment Test ===\n";

// Check if .env file exists
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    echo "✅ .env file exists\n";
    
    // Load environment variables
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
    
    // Check environment variables
    if (isset($_ENV['CALDAV_SERVER_URL'])) {
        echo "✅ CALDAV_SERVER_URL: " . $_ENV['CALDAV_SERVER_URL'] . "\n";
    } else {
        echo "❌ CALDAV_SERVER_URL not found\n";
    }
    
    if (isset($_ENV['CALDAV_USERNAME'])) {
        echo "✅ CALDAV_USERNAME: " . $_ENV['CALDAV_USERNAME'] . "\n";
    } else {
        echo "❌ CALDAV_USERNAME not found\n";
    }
    
    if (isset($_ENV['CALDAV_PASSWORD'])) {
        echo "✅ CALDAV_PASSWORD: [HIDDEN]\n";
    } else {
        echo "❌ CALDAV_PASSWORD not found\n";
    }
    
} else {
    echo "❌ .env file not found at: " . $envFile . "\n";
}

echo "=== Test Completed ===\n";
?>
