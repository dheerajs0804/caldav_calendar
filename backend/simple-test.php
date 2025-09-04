<?php
// Simple test to isolate CORS and JSON issues
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Log the request
error_log("=== SIMPLE TEST REQUEST ===");
error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("HTTP_ORIGIN: " . ($_SERVER['HTTP_ORIGIN'] ?? 'none'));

// Set CORS headers
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    error_log("=== OPTIONS Request Handled ===");
    http_response_code(200);
    exit();
}

// Set content type
header('Content-Type: application/json');

// Get the request path
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

error_log("Path: " . $path);

// Simple routing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $path === 'auth/login') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit();
    }
    
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    $serverUrl = $input['serverUrl'] ?? '';
    
    if (empty($username) || empty($password) || empty($serverUrl)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username, password, and serverUrl are required']);
        exit();
    }
    
    // For testing, just return success
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'user' => [
                'username' => $username,
                'serverUrl' => $serverUrl,
                'calendars' => 1
            ]
        ]
    ]);
    exit();
}

// Default response
echo json_encode([
    'message' => 'Simple test server running',
    'path' => $path,
    'method' => $_SERVER['REQUEST_METHOD'],
    'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'none'
]);
?>
