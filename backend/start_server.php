<?php
/**
 * Simple PHP Development Server for CalDAV Calendar Backend
 * 
 * Usage: php start_server.php
 * 
 * This will start a PHP development server on port 8000
 */

echo "🚀 Starting PHP CalDAV Calendar Backend...\n";
echo "🔐 Authentication: SSO-based (no environment variables needed)\n";
echo "📅 CalDAV Server: Configured per user login\n";
echo "🌐 Frontend: http://localhost:4200\n";
echo "🔧 Backend API: http://localhost:8001\n";
echo "📁 Document Root: " . __DIR__ . "\n";
echo "\n";
echo "Starting PHP development server on port 8001...\n";
echo "Press Ctrl+C to stop the server\n\n";

// Start PHP development server
$command = sprintf(
    'php -S localhost:8001 -t %s %s/index.php',
    escapeshellarg(__DIR__),
    escapeshellarg(__DIR__)
);

echo "Running: $command\n\n";

// Execute the command
system($command);
?>
