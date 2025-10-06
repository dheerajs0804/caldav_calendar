<?php
declare(strict_types=1);

/**
 * Test Bootstrap
 * 
 * Sets up the testing environment for PHPUnit tests
 */

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set timezone for consistent testing
date_default_timezone_set('UTC');

// Define test constants
define('TEST_ROOT', __DIR__);
define('PROJECT_ROOT', dirname(__DIR__));

// Include Composer autoloader
require_once PROJECT_ROOT . '/vendor/autoload.php';

// Set up test environment
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';

// Mock global functions for testing
if (!function_exists('session_start')) {
    function session_start(): bool {
        return true;
    }
}

if (!function_exists('session_destroy')) {
    function session_destroy(): bool {
        return true;
    }
}

if (!function_exists('header')) {
    function header(string $header, bool $replace = true, int $response_code = 0): void {
        // Mock header function for testing
    }
}

if (!function_exists('http_response_code')) {
    function http_response_code(?int $response_code = null): int {
        static $code = 200;
        if ($response_code !== null) {
            $code = $response_code;
        }
        return $code;
    }
}

// Create test data directory
$testDataDir = TEST_ROOT . '/data';
if (!is_dir($testDataDir)) {
    mkdir($testDataDir, 0755, true);
}

// Clean up any existing test data
$testFiles = glob($testDataDir . '/*.json');
foreach ($testFiles as $file) {
    unlink($file);
}

// Set up test session data
$_SESSION = [];

// Load test configuration
require_once TEST_ROOT . '/config/test_config.php';

