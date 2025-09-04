<?php
// Enable CORS for cross-origin requests
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

// Get the request path
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

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
echo json_encode(['message' => 'Minimal CORS test server running', 'path' => $path, 'method' => $_SERVER['REQUEST_METHOD']]);
?>
