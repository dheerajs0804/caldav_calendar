<?php
// Enable CORS for cross-origin requests with credentials support
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
$allowedOrigins = [
    'http://localhost:4200',
    'http://localhost:3000',
    'http://127.0.0.1:4200',
    'http://127.0.0.1:3000',
    'null' // For file:// URLs
];

// Always set the Access-Control-Allow-Origin header for development
if (in_array($origin, $allowedOrigins) || $origin === 'null') {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    // For development, allow any origin but set it explicitly
    header('Access-Control-Allow-Origin: ' . $origin);
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400'); // 24 hours

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'CORS test successful',
    'origin' => $origin,
    'method' => $_SERVER['REQUEST_METHOD']
]);
?>
