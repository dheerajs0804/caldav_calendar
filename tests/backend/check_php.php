<?php
// Simple PHP test to check if PHP is working
echo "PHP is working!\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Current Directory: " . __DIR__ . "\n";
echo "Backend Directory: " . __DIR__ . "/../backend\n";

// Check if backend files exist
$backendIndex = __DIR__ . "/../backend/index.php";
if (file_exists($backendIndex)) {
    echo "✅ Backend index.php found\n";
} else {
    echo "❌ Backend index.php not found\n";
}

// Check PHP extensions
$extensions = ['curl', 'json', 'mbstring'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext extension loaded\n";
    } else {
        echo "❌ $ext extension not loaded\n";
    }
}

echo "\nBackend environment check completed.\n";
