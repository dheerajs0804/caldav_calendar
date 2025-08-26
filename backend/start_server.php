<?php
/**
 * Simple PHP Development Server for CalDAV Calendar Backend
 * 
 * Usage: php start_server.php
 * 
 * This will start a PHP development server on port 8000
 */

echo "🚀 Starting PHP CalDAV Calendar Backend...\n";
echo "📅 CalDAV Server: " . ($_ENV['CALDAV_SERVER_URL'] ?? 'https://apidata.googleusercontent.com/caldav/v2/') . "\n";
echo "👤 Username: " . ($_ENV['CALDAV_USERNAME'] ?? 'your-email@gmail.com') . "\n";
echo "🔗 Calendar Path: " . ($_ENV['CALDAV_CALENDAR_PATH'] ?? 'calid/events') . "\n";
echo "🌐 Frontend: http://localhost:3000\n";
echo "🔧 Backend API: http://localhost:8000\n";
echo "📁 Document Root: " . __DIR__ . "\n";
echo "\n";
echo "Starting PHP development server on port 8000...\n";
echo "Press Ctrl+C to stop the server\n\n";

// Start PHP development server
$command = sprintf(
    'php -S localhost:8000 -t %s %s/index.php',
    escapeshellarg(__DIR__),
    escapeshellarg(__DIR__)
);

echo "Running: $command\n\n";

// Execute the command
system($command);
?>
