<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CalDev\Calendar\Config\Database;
use CalDev\Calendar\Models\Calendar as CalendarModel;
use CalDev\Calendar\Models\Event as EventModel;
use CalDev\Calendar\API\CalendarController;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simple routing
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string and base path
$path = parse_url($requestUri, PHP_URL_PATH);
$path = str_replace('/api', '', $path);

try {
    // Initialize database connection
    $db = Database::getInstance();
    
    // Initialize models
    $calendarModel = new CalendarModel($db);
    $eventModel = new EventModel($db);
    
    // Initialize controller
    $calendarController = new CalendarController($calendarModel, $eventModel);
    
    // Route handling
    switch ($path) {
        case '/calendars':
            if ($requestMethod === 'GET') {
                // For demo purposes, using user ID 1
                $response = $calendarController->index(1);
            } elseif ($requestMethod === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $response = $calendarController->store($input, 1);
            } else {
                $response = ['success' => false, 'error' => 'Method not allowed'];
            }
            break;
            
        case '/calendars/sync':
            if ($requestMethod === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $calendarId = $input['calendar_id'] ?? 1;
                $response = $calendarController->sync($calendarId, 1);
            } else {
                $response = ['success' => false, 'error' => 'Method not allowed'];
            }
            break;
            
        case '/events':
            if ($requestMethod === 'GET') {
                // Get events for a date range
                $startDate = $_GET['start'] ?? date('Y-m-d');
                $endDate = $_GET['end'] ?? date('Y-m-d', strtotime('+1 month'));
                $events = $eventModel->findByUserId(1, $startDate, $endDate);
                $response = ['success' => true, 'data' => $events];
            } elseif ($requestMethod === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $eventId = $eventModel->create($input);
                $event = $eventModel->findById($eventId);
                $response = ['success' => true, 'data' => $event];
            } else {
                $response = ['success' => false, 'error' => 'Method not allowed'];
            }
            break;
            
        case '/health':
            $response = [
                'success' => true,
                'message' => 'CalDAV Calendar API is running',
                'timestamp' => date('c'),
                'version' => '1.0.0'
            ];
            break;
            
        default:
            $response = [
                'success' => false,
                'error' => 'Endpoint not found',
                'available_endpoints' => [
                    'GET /api/calendars' => 'Get all calendars',
                    'POST /api/calendars' => 'Create a new calendar',
                    'POST /api/calendars/sync' => 'Sync calendar with CalDAV server',
                    'GET /api/events' => 'Get events for a date range',
                    'POST /api/events' => 'Create a new event',
                    'GET /api/health' => 'API health check'
                ]
            ];
            break;
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ];
    http_response_code(500);
}

// Send response
echo json_encode($response, JSON_PRETTY_PRINT);
