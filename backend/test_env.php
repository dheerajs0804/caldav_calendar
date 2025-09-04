<?php
// Test environment variables loading
echo "Testing environment variables...\n";

// Check if .env file exists
if (file_exists('.env')) {
    echo "✅ .env file exists\n";
    
    // Try to load environment variables
    $envContent = file_get_contents('.env');
    echo "Env file content length: " . strlen($envContent) . "\n";
    
    // Check if we can access environment variables
    if (isset($_ENV['CALDAV_SERVER_URL'])) {
        echo "✅ CALDAV_SERVER_URL: " . $_ENV['CALDAV_SERVER_URL'] . "\n";
    } else {
        echo "❌ CALDAV_SERVER_URL not found in \$_ENV\n";
    }
    
    if (isset($_ENV['CALDAV_USERNAME'])) {
        echo "✅ CALDAV_USERNAME: " . $_ENV['CALDAV_USERNAME'] . "\n";
    } else {
        echo "❌ CALDAV_USERNAME not found in \$_ENV\n";
    }
    
    if (isset($_ENV['CALDAV_PASSWORD'])) {
        echo "✅ CALDAV_PASSWORD: [HIDDEN]\n";
    } else {
        echo "❌ CALDAV_PASSWORD not found in \$_ENV\n";
    }
    
} else {
    echo "❌ .env file not found\n";
}

echo "Test completed!\n";
?>
