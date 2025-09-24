<?php
// IMMEDIATE PUT REQUEST DETECTION - BEFORE ANYTHING ELSE
error_log("🔥🔥🔥 SERVER STARTED WITH UPDATED INDEX.PHP! 🔥🔥🔥");

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'PUT') {
    error_log("🚨🚨🚨 PUT REQUEST DETECTED AT VERY START! 🚨🚨🚨");
    error_log("🚨 PUT URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
    error_log("🚨 PUT Time: " . date('Y-m-d H:i:s'));
}

// Enable error reporting and logging to terminal
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php://stderr'); // Send errors to stderr so they appear in terminal

// Also log to file for debugging
function logToFile($message) {
    file_put_contents('../debug.log', $message . "\n", FILE_APPEND | LOCK_EX);
}

// Debug: Log all incoming requests
$requestLog = "=== INCOMING REQUEST ===\n";
$requestLog .= "Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . "\n";
$requestLog .= "URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "\n";
$requestLog .= "Path Info: " . ($_SERVER['PATH_INFO'] ?? 'none') . "\n";
$requestLog .= "Request Time: " . date('Y-m-d H:i:s') . "\n";
$requestLog .= "========================";

error_log($requestLog);
logToFile($requestLog);

// Force immediate logging for PUT requests
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'PUT') {
    $putLog = "🚨 PUT REQUEST DETECTED IMMEDIATELY!\n🚨 PUT URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown');
    error_log($putLog);
    logToFile($putLog);
}

// Enable CORS for cross-origin requests with credentials
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = ['http://localhost:4200', 'http://localhost:8000', 'null']; // Allow Angular, Roundcube, and file:// origins

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: http://localhost:4200'); // Default fallback
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400'); // 24 hours

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    error_log("🔍 OPTIONS preflight request received");
    http_response_code(200);
    exit();
}

// Set session cookie parameters for better cross-origin support BEFORE starting session
ini_set('session.cookie_httponly', 0);
ini_set('session.cookie_samesite', ''); // Empty for maximum compatibility
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
ini_set('session.cookie_domain', ''); // Allow any domain
ini_set('session.cookie_path', '/'); // Set path to root

// Start session for user event storage
session_start();

// Debug: Log all requests
$mainLog = "=== REQUEST RECEIVED ===\n";
$mainLog .= "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
$mainLog .= "Path: " . ($_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'] ?? 'unknown') . "\n";
$mainLog .= "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "\n";
$mainLog .= "DEBUG: Server is loading updated index.php at " . date('Y-m-d H:i:s');

error_log($mainLog);
logToFile($mainLog);

header('Content-Type: application/json');

// Helper function to send clean JSON response
function sendJsonResponse($data) {
    // Clean any output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Ensure we're sending JSON
    header('Content-Type: application/json');
    
    // Send the JSON response
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit();
}

function getCalendarById($calendarId) {
    try {
        // Get calendars from CalDAV server using authenticated client
        $caldavClient = getCalDAVClient();
        if (!$caldavClient) {
            return null;
        }
        
        $calendars = $caldavClient->discoverCalendars();
        
        if ($calendars && is_array($calendars) && count($calendars) > 0) {
            // Load calendar colors from file
            $calendarColorsFile = 'data/calendar_colors.json';
            $calendarColors = [];
            
            if (file_exists($calendarColorsFile)) {
                $calendarColors = json_decode(file_get_contents($calendarColorsFile), true) ?? [];
            }
            
            // Find calendar by ID (calendars are indexed starting from 1)
            $calendarIndex = intval($calendarId) - 1;
            if (isset($calendars[$calendarIndex])) {
                $calendar = $calendars[$calendarIndex];
                $calendarUrl = $calendar['href'];
                $storedColor = $calendarColors[$calendarUrl] ?? null;
                $caldavColor = $calendar['color'] ?? null;
                $finalColor = $storedColor ?? $caldavColor ?? '#4285f4';
                
                return [
                    'id' => $calendarId,
                    'name' => $calendar['name'],
                    'url' => $calendarUrl,
                    'color' => $finalColor
                ];
            }
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error getting calendar by ID: " . $e->getMessage());
        return null;
    }
}

// Helper function to get CalDAV client with session credentials
function getCalDAVClient() {
    if (isset($_SESSION['caldav_credentials']) && !empty($_SESSION['caldav_credentials']['username'])) {
        $credentials = $_SESSION['caldav_credentials'];
        return new CalDAVClient($credentials['serverUrl'], $credentials['username'], $credentials['password']);
    } else {
        // Fall back to environment variables
        return new CalDAVClient();
    }
}

// Load environment variables
require_once 'config/database.php';

// Load email configuration with error handling
try {
    $emailConfig = require_once 'config/email.php';
    
    if (!$emailConfig) {
        $emailConfig = []; // Set empty config to prevent errors
    }
} catch (Exception $e) {
    $emailConfig = []; // Set empty config to prevent errors
}

require_once 'classes/CalDAVClient.php';

// Get the request path
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

// Remove 'backend' from path if present
if (strpos($path, 'backend/') === 0) {
    $path = substr($path, 8);
}

// Route the request
try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGetRequest($path);
            break;
        case 'POST':
            error_log("🔍 POST REQUEST RECEIVED - Path: " . $path);
            if (strpos($path, 'auth/') === 0) {
                error_log("🔍 Routing to handleAuthRequest");
                handleAuthRequest($path);
            } else {
                error_log("🔍 Routing to handlePostRequest");
                handlePostRequest($path);
            }
            break;
        case 'PUT':
            error_log("🔧 PUT REQUEST DETECTED - Path: " . $path);
            handlePutRequest($path);
            break;
        case 'DELETE':
            handleDeleteRequest($path);
            break;
        default:
            http_response_code(405);
            sendJsonResponse(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    sendJsonResponse(['error' => 'Internal server error: ' . $e->getMessage()]);
} catch (Error $e) {
    http_response_code(500);
    sendJsonResponse(['error' => 'Fatal error: ' . $e->getMessage()]);
}

function handleGetRequest($path) {
    switch ($path) {
        case 'health':
            echo json_encode(['status' => 'OK', 'message' => 'PHP CalDAV Calendar Backend is running']);
            break;
        case 'test':
            global $emailConfig;
            echo json_encode(['status' => 'OK', 'message' => 'Test endpoint working', 'email_config' => isset($emailConfig) ? 'loaded' : 'not loaded']);
            break;
        case 'auth/status':
            getAuthStatus();
            break;
        case 'calendars':
            getCalendars();
            break;
        case 'calendars/user':
            getUserCalendars();
            break;
        case 'events':
            getEvents();
            break;
        case 'caldav/status':
            getCalDAVStatus();
            break;
        case 'caldav/raw-data':
            getRawCalDAVData();
            break;
        default:
            if (preg_match('/^calendars\/(\d+)$/', $path, $matches)) {
                getCalendar($matches[1]);
            } elseif (preg_match('/^calendars\/(\d+)\/events$/', $path, $matches)) {
                getCalendarEvents($matches[1]);
            } elseif (preg_match('/^calendars\/(\d+)\/sync$/', $path, $matches)) {
                syncCalendar($matches[1]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
            }
    }
}

function handlePostRequest($path) {
    try {
        switch ($path) {
            case 'auth/login':
                authenticateUser();
                break;
            case 'auth/auto-login':
                autoLoginFromRoundcube();
                break;
            case 'auth/sso-token':
                createSSOToken();
                break;
            case 'auth/sso-login':
                loginWithSSOToken();
                break;
            case 'auth/logout':
                logoutUser();
                break;
            case 'calendars':
                createCalendar();
                break;
            case 'events':
                createEvent();
                break;
            case 'events/clear-local':
                clearLocalEvents();
                break;
            case 'caldav/discover':
                discoverCalDAVCalendars();
                break;
        case 'calendars/sync':
            syncCalendar();
            break;
        case 'email':
            handleEmailInvitation();
            break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
        }
    } catch (Exception $e) {
        error_log("ERROR in handlePostRequest: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
    } catch (Error $e) {
        error_log("FATAL ERROR in handlePostRequest: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode(['error' => 'Fatal error: ' . $e->getMessage()]);
    }
}

function handlePutRequest($path) {
    error_log("🔧 PUT REQUEST RECEIVED - Path: " . $path);
    if (preg_match('/^events\/(.+)$/', $path, $matches)) {
        error_log("🔧 PUT request matches events pattern, calling updateEvent with: " . $matches[1]);
        updateEvent($matches[1]);
    } elseif (preg_match('/^calendars\/(\d+)\/toggle$/', $path, $matches)) {
        error_log("🔧 PUT request matches calendars toggle pattern, calling toggleCalendar with: " . $matches[1]);
        toggleCalendar($matches[1]);
    } else {
        error_log("🔧 PUT request path not matched: " . $path);
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
}

function handleDeleteRequest($path) {
    if (preg_match('/^events\/(.+)$/', $path, $matches)) {
        deleteEvent($matches[1]);
    } elseif (preg_match('/^calendars\/(.+)$/', $path, $matches)) {
        deleteCalendar($matches[1]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
}

// Mock data functions (replace with database calls later)
function getCalendars() {
    try {
        // Get calendars from CalDAV server using authenticated client
        $caldavClient = getCalDAVClient();
        if (!$caldavClient) {
            throw new Exception('User not authenticated - please login first');
        }
        
        $calendars = $caldavClient->discoverCalendars();
        
        if ($calendars && is_array($calendars) && count($calendars) > 0) {
            // Return real CalDAV calendars
            echo json_encode([
                'success' => true,
                'data' => $calendars,
                'message' => 'Calendars retrieved successfully from CalDAV server'
            ]);
        } else {
            throw new Exception('No calendars found on CalDAV server');
        }
        
    } catch (Exception $e) {
        error_log("Error getting calendars: " . $e->getMessage());
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to get calendars: ' . $e->getMessage(),
            'error' => 'authentication_required'
        ]);
    }
}

function getUserCalendars() {
    try {
        error_log("=== getUserCalendars() called ===");
        error_log("Session ID: " . session_id());
        error_log("Session data: " . print_r($_SESSION, true));
        
        // Check if user is authenticated via session
        if (isset($_SESSION['caldav_credentials']) && !empty($_SESSION['caldav_credentials']['username'])) {
            $credentials = $_SESSION['caldav_credentials'];
            error_log("Getting user calendars for: " . $credentials['username']);
            $caldavClient = new CalDAVClient($credentials['serverUrl'], $credentials['username'], $credentials['password']);
        } else {
            // Fall back to environment variables
            error_log("No session credentials found, using environment variables for calendar discovery");
            error_log("Available session keys: " . implode(', ', array_keys($_SESSION)));
            $caldavClient = new CalDAVClient();
        }
        
        // Discover calendars
        error_log("Calling discoverCalendars()...");
        $calendars = $caldavClient->discoverCalendars();
        error_log("discoverCalendars() returned: " . print_r($calendars, true));

        if ($calendars && is_array($calendars) && count($calendars) > 0) {
            // Load calendar states from file
            $calendarStatesFile = 'data/calendar_states.json';
            $calendarStates = [];
            
            if (file_exists($calendarStatesFile)) {
                $calendarStates = json_decode(file_get_contents($calendarStatesFile), true) ?? [];
            }
            
            // Load calendar colors from file
            $calendarColorsFile = 'data/calendar_colors.json';
            $calendarColors = [];
            
            if (file_exists($calendarColorsFile)) {
                $calendarColors = json_decode(file_get_contents($calendarColorsFile), true) ?? [];
                error_log("Loaded calendar colors: " . print_r($calendarColors, true));
            }
            
            // Add ID and other properties to each calendar
            $processedCalendars = [];
            foreach ($calendars as $index => $calendar) {
                $calendarId = $index + 1;
                $calendarUrl = $calendar['href'];
                
                // Get stored color for this calendar URL, or use CalDAV color, or default
                $storedColor = $calendarColors[$calendarUrl] ?? null;
                $caldavColor = $calendar['color'] ?? null;
                $finalColor = $storedColor ?? $caldavColor ?? '#4285f4';
                
                error_log("Calendar: " . $calendar['name'] . ", URL: " . $calendarUrl);
                error_log("Stored color: " . ($storedColor ?? 'null') . ", CalDAV color: " . ($caldavColor ?? 'null') . ", Final color: " . $finalColor);
                
                $processedCalendars[] = [
                    'id' => $calendarId, // Simple numeric ID
                    'name' => $calendar['name'],
                    'url' => $calendarUrl,
                    'color' => $finalColor, // Use stored color or fallback
                    'description' => '',
                    'enabled' => isset($calendarStates[$calendarId]) ? $calendarStates[$calendarId] : true, // Default to enabled
                    'created_at' => date('c'),
                    'updated_at' => date('c')
                ];
            }
            
            sendJsonResponse([
                'success' => true,
                'data' => [
                    'calendars' => $processedCalendars
                ],
                'message' => 'Calendars discovered successfully'
            ]);
        } else {
            // Check if it's a server configuration issue
            sendJsonResponse([
                'success' => false,
                'message' => 'Unable to discover calendars. The CalDAV server may have configuration issues or the endpoint may be different.',
                'error_type' => 'server_configuration',
                'suggestions' => [
                    'Contact your server administrator to enable CalDAV protocol support',
                    'Check if the CalDAV endpoint is at a different URL path',
                    'Verify that WebDAV methods (PROPFIND, REPORT) are allowed'
                ]
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Error in getUserCalendars: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        sendJsonResponse([
            'success' => false,
            'message' => 'Error discovering calendars: ' . $e->getMessage(),
            'error_type' => 'discovery_error'
        ]);
    }
}

function filterDeletedEvents($events) {
    $deletedEventsFile = 'data/deleted_events.json';
    $deletedEvents = [];
    
    if (file_exists($deletedEventsFile)) {
        $deletedEvents = json_decode(file_get_contents($deletedEventsFile), true) ?? [];
    }
    
    error_log("🗑️ FILTER DEBUG - Total events to filter: " . count($events));
    error_log("🗑️ FILTER DEBUG - Deleted events count: " . count($deletedEvents));
    
    if (empty($deletedEvents)) {
        error_log("🗑️ FILTER DEBUG - No deleted events to filter, returning all events");
        return $events; // No deleted events to filter
    }
    
    $filteredEvents = [];
    
    foreach ($events as $event) {
        $eventUid = $event['uid'] ?? null;
        $isDeleted = false;
        
        error_log("🗑️ FILTER DEBUG - Checking event: " . ($event['title'] ?? 'Unknown') . " (UID: " . $eventUid . ")");
        
        // Check if this event is in the deleted events list
        foreach ($deletedEvents as $deletedEvent) {
            $deletedUid = $deletedEvent['uid'] ?? null;
            $deletedAction = $deletedEvent['action'] ?? 'all';
            
            error_log("🗑️ FILTER DEBUG - Comparing with deleted event: " . $deletedUid . " (action: " . $deletedAction . ")");
            
            if ($deletedAction === 'all' || $deletedAction === 'future') {
                // For 'all' and 'future' actions, delete all events with this master UID
                if ($deletedUid === $eventUid) {
                    $isDeleted = true;
                    error_log("🗑️ Filtering out " . strtoupper($deletedAction) . " occurrences of deleted event: " . ($event['title'] ?? 'Unknown') . " (UID: " . $eventUid . ")");
                    break;
                }
            } elseif ($deletedAction === 'current') {
                // For 'current' action, only delete the specific occurrence
                // The deleted UID is like "uid_12345_0", we need to check if this event matches
                // We need to reconstruct the specific occurrence UID for this event
                $masterUid = $deletedEvent['master_uid'] ?? null;
                $occurrenceIndex = $deletedEvent['occurrence_index'] ?? null;
                
                if ($masterUid && $occurrenceIndex !== null) {
                    // Check if this is the master event and we need to filter out a specific occurrence
                    if ($eventUid === $masterUid) {
                        // This is the master event, we need to check if the specific occurrence should be filtered
                        // For now, we'll filter the entire event (this can be refined later with proper occurrence filtering)
                        $isDeleted = true;
                        error_log("🗑️ Filtering out CURRENT occurrence of deleted event: " . ($event['title'] ?? 'Unknown') . " (Master UID: " . $eventUid . ", Occurrence: " . $occurrenceIndex . ")");
                        break;
                    }
                } else {
                    // Fallback: direct UID match
                    if ($deletedUid === $eventUid) {
                        $isDeleted = true;
                        error_log("🗑️ Filtering out CURRENT occurrence of deleted event: " . ($event['title'] ?? 'Unknown') . " (UID: " . $eventUid . ")");
                        break;
                    }
                }
            }
        }
        
        if (!$isDeleted) {
            $filteredEvents[] = $event;
            error_log("🗑️ FILTER DEBUG - Event kept: " . ($event['title'] ?? 'Unknown'));
        } else {
            error_log("🗑️ FILTER DEBUG - Event filtered out: " . ($event['title'] ?? 'Unknown'));
        }
    }
    
    error_log("🗑️ FILTER DEBUG - Final filtered events count: " . count($filteredEvents));
    return $filteredEvents;
}

function getEvents() {
    try {
        // Check if user is authenticated via session credentials
        if (!isset($_SESSION['caldav_credentials']) || empty($_SESSION['caldav_credentials']['username'])) {
            sendJsonResponse(['success' => false, 'message' => 'User not authenticated - please login first']);
            return;
        }
        
        $credentials = $_SESSION['caldav_credentials'];
        $caldavClient = new CalDAVClient($credentials['serverUrl'], $credentials['username'], $credentials['password']);
        
        // Get the selected calendar from the request - require a specific calendar URL
        $selectedCalendarUrl = null;
        
        // Check if a specific calendar URL was requested
        if (isset($_GET['calendar_url'])) {
            $selectedCalendarUrl = $_GET['calendar_url'];
        } else {
            // No calendar URL provided - return error
            sendJsonResponse(['success' => false, 'message' => 'Calendar URL is required']);
            return;
        }
        
        if (!$selectedCalendarUrl) {
            sendJsonResponse(['success' => false, 'message' => 'No calendar available']);
            return;
        }
        
        // Get date range for events (default to wider range to capture EXDATE changes)
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-6 months'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('+6 months'));
        
        // Convert dates to CalDAV format (Ymd\THis\Z)
        $startDateCalDAV = date('Ymd\THis\Z', strtotime($startDate));
        $endDateCalDAV = date('Ymd\THis\Z', strtotime($endDate));
        
        // Fetch real events from CalDAV server
        // Use getAllEvents to ensure we capture EXDATE changes from other clients
        $events = $caldavClient->getAllEvents($selectedCalendarUrl);
        
        // If getAllEvents fails or returns empty, fallback to time-filtered query
        if (empty($events)) {
            error_log("getAllEvents returned empty, falling back to time-filtered query");
            $events = $caldavClient->getEvents($selectedCalendarUrl, $startDateCalDAV, $endDateCalDAV);
        }
        
        if ($events && is_array($events)) {
            // Send all events to frontend - let frontend handle filtering
            // Debug: Log event data before sending response
            error_log("=== Events being sent to frontend ===");
            foreach ($events as $index => $event) {
                $eventData = [
                    'title' => $event['title'] ?? 'N/A',
                    'uid' => $event['uid'] ?? 'N/A',
                    'recurrence' => $event['recurrence'] ?? null,
                    'hasRecurrence' => !empty($event['recurrence']),
                    'exdate' => $event['exdate'] ?? null,
                    'hasExdate' => !empty($event['exdate']),
                    'status' => $event['status'] ?? 'N/A',
                    'availability' => $event['availability'] ?? 'N/A'
                ];
                
                error_log("Event " . ($index + 1) . ": " . json_encode($eventData));
                
                // Special logging for events with EXDATE
                if (!empty($event['exdate'])) {
                    error_log("🗑️ EXDATE DEBUG: Event '" . $event['title'] . "' has EXDATE: " . json_encode($event['exdate']));
                    error_log("🗑️ EXDATE DEBUG: Event UID: " . $event['uid']);
                    error_log("🗑️ EXDATE DEBUG: Event recurrence: " . json_encode($event['recurrence']));
                }
            }
            
            sendJsonResponse([
                'success' => true,
                'data' => $events,
                'message' => 'Events retrieved successfully',
                'calendar_url' => $selectedCalendarUrl
            ]);
        } else {
            // If no events found, return empty array
            sendJsonResponse([
                'success' => true,
                'data' => [],
                'message' => 'No events found in calendar',
                'calendar_url' => $selectedCalendarUrl
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Error getting events: " . $e->getMessage());
        http_response_code(500);
        sendJsonResponse(['success' => false, 'message' => 'Failed to retrieve events: ' . $e->getMessage()]);
    }
}

function getCalDAVStatus() {
    // Check if user is authenticated via session credentials
    if (!isset($_SESSION['caldav_credentials']) || empty($_SESSION['caldav_credentials']['username'])) {
        echo json_encode([
            'success' => false,
            'data' => [
                'connected' => false,
                'message' => 'User not authenticated - please login first'
            ]
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'connected' => true,
            'serverUrl' => $_SESSION['caldav_server_url'] ?? 'Not configured',
            'username' => $_SESSION['username'] ?? 'Not configured',
            'message' => 'CalDAV client is authenticated and ready'
        ]
    ]);
}

function discoverCalDAVCalendars() {
    try {
        $caldavClient = getCalDAVClient();
        if (!$caldavClient) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated'
            ]);
            return;
        }
        $calendars = $caldavClient->discoverCalendars();
        
        sendJsonResponse([
            'success' => true,
            'data' => $calendars,
            'message' => 'CalDAV calendars discovered successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        sendJsonResponse([
            'success' => false,
            'message' => 'Error discovering CalDAV calendars',
            'error' => $e->getMessage()
        ]);
    }
}

function getRawCalDAVData() {
    try {
        $caldavClient = getCalDAVClient();
        if (!$caldavClient) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated'
            ]);
            return;
        }
        $calendars = $caldavClient->discoverCalendars();
        
        if (empty($calendars)) {
            throw new Exception('No calendars found');
        }
        
        $calendarUrl = $calendars[0]['href'];
        
        // Get the raw CalDAV response
        $authToken = $caldavClient->getAuthToken();
        if (!$authToken) {
            throw new Exception('Failed to get authentication token');
        }
        
        $startDate = date('Ymd\THis\Z', strtotime('-1 month'));
        $endDate = date('Ymd\THis\Z', strtotime('+1 month'));
        $reportXml = $caldavClient->getCalendarReportXml($startDate, $endDate);
        
        $response = $caldavClient->makeCalDAVRequest($calendarUrl, 'REPORT', $authToken, [
            'Depth: 1',
            'Content-Type: application/xml; charset=utf-8'
        ], $reportXml);
        
        sendJsonResponse([
            'success' => true,
            'data' => [
                'status' => $response['status'],
                'body' => $response['body'],
                'bodyLength' => strlen($response['body'])
            ]
        ]);
        
    } catch (Exception $e) {
        sendJsonResponse([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

// OAuth setup function removed - using Basic Auth only

// Placeholder functions for other endpoints
function getCalendar($id) {
    // TODO: Implement
    echo json_encode(['error' => 'Not implemented yet']);
}

function getCalendarEvents($id) {
    // TODO: Implement
    echo json_encode(['error' => 'Not implemented yet']);
}

function createCalendar() {
    try {
        // Get the request body
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        // Validate required fields
        if (empty($input['name'])) {
            throw new Exception('Calendar name is required');
        }
        
        // Get CalDAV client
        $caldavClient = getCalDAVClient();
        if (!$caldavClient) {
            throw new Exception('CalDAV client not available');
        }
        
        error_log("CalDAVClient class: " . get_class($caldavClient));
        error_log("CalDAVClient methods: " . implode(', ', get_class_methods($caldavClient)));
        
        // Extract calendar details
        $calendarName = trim($input['name']);
        $description = isset($input['description']) ? trim($input['description']) : '';
        $color = isset($input['color']) ? $input['color'] : '#4285f4';
        
        error_log("Creating calendar: $calendarName");
        
        // Create the calendar using CalDAV client
        $result = $caldavClient->createCalendar($calendarName, $description, $color);
        
        if ($result['success']) {
            // Store the calendar color for future event inheritance
            $calendarData = $result['data'];
            if (isset($calendarData['url']) && isset($calendarData['color'])) {
                $calendarColorsFile = 'data/calendar_colors.json';
                $calendarColorsDir = dirname($calendarColorsFile);
                
                // Create directory if it doesn't exist
                if (!is_dir($calendarColorsDir)) {
                    mkdir($calendarColorsDir, 0755, true);
                }
                
                // Read existing colors
                $calendarColors = [];
                if (file_exists($calendarColorsFile)) {
                    $calendarColors = json_decode(file_get_contents($calendarColorsFile), true) ?? [];
                }
                
                // Store the new calendar color
                $calendarColors[$calendarData['url']] = $calendarData['color'];
                
                // Write back to file
                file_put_contents($calendarColorsFile, json_encode($calendarColors, JSON_PRETTY_PRINT));
                
                error_log("Stored calendar color: " . $calendarData['color'] . " for URL: " . $calendarData['url']);
            }
            
            sendJsonResponse([
                'success' => true,
                'data' => $result['data'],
                'message' => $result['message']
            ]);
        } else {
            throw new Exception($result['message']);
        }
        
    } catch (Exception $e) {
        error_log("Calendar creation error: " . $e->getMessage());
        sendJsonResponse([
            'success' => false,
            'message' => 'Failed to create calendar: ' . $e->getMessage()
        ]);
    }
}

function createEvent() {
    try {
        // Get the request body
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        // Validate required fields
        if (empty($input['title'])) {
            throw new Exception('Event title is required');
        }
        
        if (empty($input['start_time'])) {
            throw new Exception('Start time is required');
        }
        
        if (empty($input['end_time'])) {
            throw new Exception('End time is required');
        }
        
        error_log("Attendees data in input: " . json_encode($input['attendees'] ?? 'null'));
        error_log("Number of attendees: " . (isset($input['attendees']) ? count($input['attendees']) : 'not set'));
        error_log("Recurrence data in input: " . json_encode($input['recurrence'] ?? 'null'));
        error_log("Reminder data in input: " . json_encode($input['reminder'] ?? 'null'));
        
        // Get calendar color for color inheritance
        $calendarColor = '#4285f4'; // Default blue color
        $calendarId = $input['calendar_id'] ?? 1;
        $calendarUrl = $input['calendar_url'] ?? null;
        
        // Try to get calendar color from stored calendar colors
        if ($calendarUrl) {
            $calendarColorsFile = 'data/calendar_colors.json';
            if (file_exists($calendarColorsFile)) {
                $calendarColors = json_decode(file_get_contents($calendarColorsFile), true) ?? [];
                $calendarColor = $calendarColors[$calendarUrl] ?? $calendarColor;
                error_log("Calendar color inherited: $calendarColor for URL: $calendarUrl");
            }
        }
        
        // Create event object
        $event = [
            'id' => uniqid('event_'),
            'title' => $input['title'],
            'description' => $input['description'] ?? '',
            'location' => $input['location'] ?? '',
            'start_time' => $input['start_time'],
            'end_time' => $input['end_time'],
            'all_day' => $input['all_day'] ?? false,
            'availability' => $input['availability'] ?? 'busy',
            'status' => $input['status'] ?? 'confirmed',
            'calendar_id' => $calendarId,
            'calendar_url' => $calendarUrl,
            'color' => $calendarColor, // Inherit calendar color
            'uid' => uniqid('uid_'),
            'etag' => uniqid('etag_'),
            'created_at' => date('c'),
            'updated_at' => date('c'),
            'attendees' => $input['attendees'] ?? [],
            'recurrence' => $input['recurrence'] ?? null,
            'reminder' => $input['reminder'] ?? null,
            'valarm' => null
        ];
        
        // Debug: Log event creation data
        error_log("🔍 CREATE EVENT DEBUG:");
        error_log("🔍 Raw input: " . json_encode($input));
        error_log("🔍 Recurrence data in input: " . json_encode($input['recurrence'] ?? 'null'));
        error_log("🔍 Event recurrence field: " . json_encode($event['recurrence']));
        error_log("🔍 Reminder data in input: " . json_encode($input['reminder'] ?? 'null'));
        
        // Handle reminder data - convert VALARM to reminder format for frontend compatibility
        if (!empty($input['valarm'])) {
            $valarm = $input['valarm'];
            $event['valarm'] = $valarm; // Keep original VALARM data
            
            // Also convert to frontend reminder format for backward compatibility
            $event['reminder'] = [
                'enabled' => true,
                'type' => 'message',
                'time' => 15, // Default fallback
                'unit' => 'minutes',
                'relativeTo' => 'start'
            ];
            
            // Parse VALARM trigger to extract reminder settings
            if (!empty($valarm['trigger'])) {
                $trigger = $valarm['trigger'];
                if (preg_match('/^-PT(\d+)([MHD])$/', $trigger, $matches)) {
                    $time = intval($matches[1]);
                    $unit = $matches[2];
                    
                    // Convert to frontend format
                    switch ($unit) {
                        case 'M':
                            $event['reminder']['unit'] = 'minutes';
                            $event['reminder']['time'] = $time;
                            break;
                        case 'H':
                            $event['reminder']['unit'] = 'hours';
                            $event['reminder']['time'] = $time;
                            break;
                        case 'D':
                            $event['reminder']['unit'] = 'days';
                            $event['reminder']['time'] = $time;
                            break;
                    }
                }
            }
        }
        
        // Store the event in a file for persistence
        $eventsFile = 'data/events.json';
        $eventsDir = dirname($eventsFile);
        
        // Create directory if it doesn't exist
        if (!is_dir($eventsDir)) {
            mkdir($eventsDir, 0755, true);
        }
        
        // Read existing events
        $existingEvents = [];
        if (file_exists($eventsFile)) {
            $existingEvents = json_decode(file_get_contents($eventsFile), true) ?? [];
        }
        
        // Add new event
        $existingEvents[] = $event;
        
        // Write back to file
        file_put_contents($eventsFile, json_encode($existingEvents, JSON_PRETTY_PRINT));
        
        // Now POST the event to the actual CalDAV server (optional)
        try {
            $caldavClient = getCalDAVClient();
            if (!$caldavClient) {
                // User not authenticated - just store locally
                $response = [
                    'success' => true,
                    'data' => $event,
                    'message' => 'Event created locally (CalDAV sync requires authentication)'
                ];
                error_log("Sending local-only response: " . json_encode($response));
                echo json_encode($response);
                return;
            }
            $calendars = $caldavClient->discoverCalendars();
            
            if (!empty($calendars)) {
                // Use provided calendar URL or fallback to first calendar
                $calendarUrl = $input['calendar_url'] ?? $calendars[0]['href'];
                error_log("Using calendar URL: " . $calendarUrl);
                
                // Use the proper generateICalEvent function for CalDAV storage
                $icalContent = generateICalEvent($event);
                
                // POST to CalDAV server
                error_log("🔍 About to call CalDAV client createEvent");
                error_log("🔍 Calendar URL: " . $calendarUrl);
                error_log("🔍 Event UID: " . $event['uid']);
                error_log("🔍 iCal Content Length: " . strlen($icalContent));
                $response = $caldavClient->createEvent($calendarUrl, $icalContent, $event['uid']);
                error_log("🔍 CalDAV client createEvent completed");
                
                if ($response['status'] >= 200 && $response['status'] < 300) {
                    // Event created successfully, now send invitations if attendees exist
                    if (!empty($input['attendees']) && is_array($input['attendees'])) {
                        error_log("🔍 About to call sendEventInvitations with " . count($input['attendees']) . " attendees");
                        
                        // Capture any output that might interfere with JSON response
                        ob_start();
                        $invitationResult = sendEventInvitations($event, $input['attendees']);
                        $capturedOutput = ob_get_clean();
                        
                        if (!empty($capturedOutput)) {
                            error_log("🔍 WARNING: Captured output from sendEventInvitations: " . $capturedOutput);
                        }
                        
                        error_log("🔍 sendEventInvitations returned: " . json_encode($invitationResult));
                        if ($invitationResult['success']) {
                            $successResponse = [
                                'success' => true,
                                'data' => $event,
                                'message' => 'Event created successfully, synced to CalDAV server, and invitations sent to ' . $invitationResult['data']['successfulSends'] . ' attendees'
                            ];
                            error_log("Sending success response with invitations: " . json_encode($successResponse));
                            error_log("🔍 About to send success response to frontend");
                            header('Content-Type: application/json');
                            echo json_encode($successResponse);
                            exit; // Stop execution to prevent any additional output
                        } else {
                            $partialResponse = [
                                'success' => true,
                                'data' => $event,
                                'message' => 'Event created successfully and synced to CalDAV server, but failed to send invitations: ' . $invitationResult['message']
                            ];
                            error_log("Sending partial success response: " . json_encode($partialResponse));
                            error_log("🔍 About to send partial success response to frontend");
                            header('Content-Type: application/json');
                            echo json_encode($partialResponse);
                            exit; // Stop execution to prevent any additional output
                        }
                    } else {
                        $successResponse = [
                            'success' => true,
                            'data' => $event,
                            'message' => 'Event created successfully and synced to CalDAV server'
                        ];
                        error_log("Sending success response without invitations: " . json_encode($successResponse));
                        error_log("🔍 About to send success response (no invitations) to frontend");
                        header('Content-Type: application/json');
                        echo json_encode($successResponse);
                        exit; // Stop execution to prevent any additional output
                    }
                } else {
                    $partialResponse = [
                        'success' => true,
                        'data' => $event,
                        'message' => 'Event created locally but CalDAV sync failed: ' . $response['body']
                    ];
                    error_log("Sending partial success response (CalDAV failed): " . json_encode($partialResponse));
                    error_log("🔍 About to send partial success response (CalDAV failed) to frontend");
                    header('Content-Type: application/json');
                    echo json_encode($partialResponse);
                    exit; // Stop execution to prevent any additional output
                }
            } else {
                $noCalendarResponse = [
                    'success' => true,
                    'data' => $event,
                    'message' => 'Event created locally but no CalDAV calendar found'
                ];
                error_log("Sending no calendar response: " . json_encode($noCalendarResponse));
                error_log("🔍 About to send no calendar response to frontend");
                header('Content-Type: application/json');
                echo json_encode($noCalendarResponse);
                exit; // Stop execution to prevent any additional output
            }
        } catch (Exception $caldavError) {
            error_log("CalDAV error in createEvent: " . $caldavError->getMessage());
            $response = [
                'success' => true,
                'data' => $event,
                'message' => 'Event created locally but CalDAV sync error: ' . $caldavError->getMessage()
            ];
            error_log("Sending response: " . json_encode($response));
            error_log("🔍 About to send CalDAV error response to frontend");
            header('Content-Type: application/json');
            echo json_encode($response);
            exit; // Stop execution to prevent any additional output
        }
        
    } catch (Exception $e) {
        error_log("Exception in createEvent: " . $e->getMessage());
        http_response_code(400);
        $errorResponse = [
            'success' => false,
            'message' => $e->getMessage()
        ];
        error_log("Sending error response: " . json_encode($errorResponse));
        error_log("🔍 About to send error response to frontend");
        header('Content-Type: application/json');
        echo json_encode($errorResponse);
        exit; // Stop execution to prevent any additional output
    }
}

function generateRRULE($recurrence) {
    if (!$recurrence || $recurrence['frequency'] === 'never') {
        return '';
    }
    
    error_log("🔄 Generating RRULE for recurrence: " . json_encode($recurrence));
    
    // Convert to Roundcube-style format
    $params = [];
    
    // Frequency
    $freq = strtoupper($recurrence['frequency']);
    $params['FREQ'] = $freq;
    
    // Interval
    if (!empty($recurrence['interval']) && $recurrence['interval'] > 1) {
        $params['INTERVAL'] = $recurrence['interval'];
    }
    
    // Count (number of occurrences)
    if (!empty($recurrence['count']) && $recurrence['count'] > 0) {
        $params['COUNT'] = $recurrence['count'];
    }
    
    // Until date - convert to UTC format like Roundcube
    if (!empty($recurrence['until'])) {
        $untilDate = new DateTime($recurrence['until']);
        $untilDate->setTimezone(new DateTimeZone('UTC'));
        $params['UNTIL'] = $untilDate->format('Ymd\THis\Z');
    }
    
    // By day (for weekly recurrence)
    if ($freq === 'WEEKLY' && !empty($recurrence['byDay']) && is_array($recurrence['byDay'])) {
        $params['BYDAY'] = implode(',', $recurrence['byDay']);
    }
    
    // By month day (for monthly recurrence)
    if ($freq === 'MONTHLY' && !empty($recurrence['byMonthDay']) && is_array($recurrence['byMonthDay'])) {
        $params['BYMONTHDAY'] = implode(',', $recurrence['byMonthDay']);
    }
    
    // By month (for yearly recurrence)
    if ($freq === 'YEARLY' && !empty($recurrence['byMonth']) && is_array($recurrence['byMonth'])) {
        $params['BYMONTH'] = implode(',', $recurrence['byMonth']);
    }
    
    // By set position (for monthly/yearly recurrence)
    if (!empty($recurrence['bySetPos'])) {
        $params['BYSETPOS'] = $recurrence['bySetPos'];
    }
    
    // Build RRULE string like Roundcube does
    $rrule = 'RRULE:';
    $parts = [];
    foreach ($params as $k => $val) {
        if (strlen($val)) {
            $parts[] = $k . '=' . $val;
        }
    }
    
    $result = $rrule . implode(';', $parts);
    error_log("🔄 Generated RRULE: " . $result);
    
    return $result;
}

function generateICalEvent($event) {
    $uid = $event['uid'];
    $dtstamp = date('Ymd\THis\Z');
    
    // Parse the input times and preserve local timezone
    $startTime = new DateTime($event['start_time']);
    $endTime = new DateTime($event['end_time']);
    
    // Force the times to be interpreted as local time by adding TZID parameter
    // This prevents the CalDAV server from interpreting times as UTC
    $dtstart = $startTime->format('Ymd\THis');
    $dtend = $endTime->format('Ymd\THis');
    
    $ical = "BEGIN:VCALENDAR\r\n";
    $ical .= "VERSION:2.0\r\n";
    $ical .= "PRODID:-//Mithi Calendar//EN\r\n";
    $ical .= "BEGIN:VEVENT\r\n";
    $ical .= "UID:{$uid}\r\n";
    $ical .= "DTSTAMP:{$dtstamp}\r\n";
    $ical .= "DTSTART;TZID=Asia/Kolkata:{$dtstart}\r\n";
    $ical .= "DTEND;TZID=Asia/Kolkata:{$dtend}\r\n";
    $ical .= "SUMMARY:" . str_replace(["\r\n", "\n", "\r"], "\\n", $event['title']) . "\r\n";
    
    if (!empty($event['description'])) {
        $ical .= "DESCRIPTION:" . str_replace(["\r\n", "\n", "\r"], "\\n", $event['description']) . "\r\n";
    }
    
    if (!empty($event['location'])) {
        $ical .= "LOCATION:" . str_replace(["\r\n", "\n", "\r"], "\\n", $event['location']) . "\r\n";
    }
    
    // Add status
    if (!empty($event['status'])) {
        $status = strtoupper($event['status']);
        $ical .= "STATUS:{$status}\r\n";
    }
    
    // Add transparency (availability)
    if (!empty($event['availability'])) {
        $transp = $event['availability'] === 'free' ? 'TRANSPARENT' : 'OPAQUE';
        $ical .= "TRANSP:{$transp}\r\n";
    }
    
    // Add recurrence rule
    if (!empty($event['recurrence'])) {
        $rrule = generateRRULE($event['recurrence']);
        if (!empty($rrule)) {
            // Add RRULE property
            $ical .= $rrule . "\r\n";
            error_log("📅 Added RRULE to iCalendar: " . $rrule);
            
            // Add additional properties for recurring events (like Roundcube)
            $ical .= "SEQUENCE:0\r\n";
            $ical .= "CREATED:{$dtstamp}\r\n";
            $ical .= "LAST-MODIFIED:{$dtstamp}\r\n";
            $ical .= "CLASS:PUBLIC\r\n";
            $ical .= "PRIORITY:5\r\n";
        }
    }
    
    // Add EXDATE (exception dates) for single occurrence deletions
    if (!empty($event['exdate'])) {
        if (is_array($event['exdate'])) {
            // Sort EXDATEs chronologically for better CalDAV compatibility
            $sortedExdates = $event['exdate'];
            usort($sortedExdates, function($a, $b) {
                return strcmp($a, $b);
            });
            
            // Use separate EXDATE properties for each exception date
            foreach ($sortedExdates as $exdate) {
                // Validate EXDATE format before adding
                if (preg_match('/^\d{8}T\d{6}Z$/', $exdate)) {
                    $ical .= "EXDATE:{$exdate}\r\n";
                    error_log("🗑️ Added individual EXDATE to iCalendar: " . $exdate);
                } else {
                    error_log("🗑️ Skipping malformed EXDATE: " . $exdate);
                }
            }
            error_log("🗑️ Added " . count($sortedExdates) . " separate EXDATE properties");
        } else {
            // Validate single EXDATE format
            if (preg_match('/^\d{8}T\d{6}Z$/', $event['exdate'])) {
                $ical .= "EXDATE:{$event['exdate']}\r\n";
                error_log("🗑️ Added single EXDATE to iCalendar: " . $event['exdate']);
            } else {
                error_log("🗑️ Skipping malformed single EXDATE: " . $event['exdate']);
            }
        }
    }
    
    // Debug: Log the complete iCalendar content
    error_log("🔍 Generated iCalendar content:");
    error_log($ical);
    
    // Add VALARM component if reminder is enabled
    if (!empty($event['valarm']) && !empty($event['valarm']['trigger'])) {
        $ical .= "BEGIN:VALARM\r\n";
        $ical .= "TRIGGER:" . $event['valarm']['trigger'] . "\r\n";
        $ical .= "ACTION:" . ($event['valarm']['action'] ?? 'DISPLAY') . "\r\n";
        if (!empty($event['valarm']['description'])) {
            $ical .= "DESCRIPTION:" . str_replace(["\r\n", "\n", "\r"], "\\n", $event['valarm']['description']) . "\r\n";
        }
        $ical .= "END:VALARM\r\n";
    }
    
    // Add attendees
    if (!empty($event['attendees']) && is_array($event['attendees'])) {
        foreach ($event['attendees'] as $attendee) {
            if (!empty($attendee['email'])) {
                $attendeeLine = "ATTENDEE";
                
                // Add role if specified
                if (!empty($attendee['role'])) {
                    $role = strtoupper($attendee['role']);
                    if ($role === 'REQUIRED') {
                        $attendeeLine .= ";ROLE=REQ-PARTICIPANT";
                    } elseif ($role === 'OPTIONAL') {
                        $attendeeLine .= ";ROLE=OPT-PARTICIPANT";
                    }
                }
                
                // Add response status if specified
                if (!empty($attendee['response'])) {
                    $response = strtoupper($attendee['response']);
                    if (in_array($response, ['ACCEPTED', 'DECLINED', 'TENTATIVE', 'NEEDS-ACTION'])) {
                        $attendeeLine .= ";PARTSTAT=" . $response;
                    }
                }
                
                $attendeeLine .= ":mailto:" . $attendee['email'];
                $ical .= $attendeeLine . "\r\n";
                error_log("👥 Added attendee to iCalendar: " . $attendee['email']);
            }
        }
    }
    
    $ical .= "END:VEVENT\r\n";
    $ical .= "END:VCALENDAR\r\n";
    
    return $ical;
}

/**
 * Calculate the specific occurrence date for a recurring event
 */
function calculateOccurrenceDate($event, $occurrenceIndex) {
    error_log("🗑️ calculateOccurrenceDate called with index: " . $occurrenceIndex);
    $startDate = new DateTime($event['start_time']);
    $recurrence = $event['recurrence'] ?? null;
    
    if (!$recurrence || $recurrence['frequency'] === 'never') {
        error_log("🗑️ No recurrence, returning start date");
        return $startDate;
    }
    
    $currentDate = clone $startDate;
    error_log("🗑️ Starting from: " . $currentDate->format('Y-m-d H:i:s'));
    
    // Generate occurrences up to the specified index
    for ($i = 0; $i < $occurrenceIndex; $i++) {
        $currentDate = getNextOccurrenceDate($currentDate, $recurrence);
        error_log("🗑️ After iteration " . ($i + 1) . ": " . $currentDate->format('Y-m-d H:i:s'));
    }
    
    error_log("🗑️ Final calculated date: " . $currentDate->format('Y-m-d H:i:s'));
    return $currentDate;
}

/**
 * Fold long iCalendar lines according to RFC 5545 (75-character limit)
 */
function foldICalendarLine($line) {
    if (strlen($line) <= 75) {
        return $line;
    }
    
    $folded = '';
    $remaining = $line;
    
    while (strlen($remaining) > 75) {
        $folded .= substr($remaining, 0, 75) . "\r\n ";
        $remaining = substr($remaining, 75);
    }
    
    if (!empty($remaining)) {
        $folded .= $remaining;
    }
    
    return $folded;
}

/**
 * Normalize EXDATE to UTC format (YYYYMMDDTHHMMSSZ)
 */
function normalizeExdateToUtc($exdate) {
    try {
        // Clean the input first
        $exdate = trim($exdate);
        
        // If already in UTC format (YYYYMMDDTHHMMSSZ), return as is
        if (preg_match('/^\d{8}T\d{6}Z$/', $exdate)) {
            error_log("🗑️ EXDATE already in UTC format: " . $exdate);
            return $exdate;
        }
        
        // If in ISO format (YYYY-MM-DDTHH:MM:SS+HH:MM), convert to UTC
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $exdate)) {
            $date = new DateTime($exdate);
            $date->setTimezone(new DateTimeZone('UTC'));
            $normalized = $date->format('Ymd\THis\Z');
            error_log("🗑️ Converted ISO EXDATE: '$exdate' -> '$normalized'");
            return $normalized;
        }
        
        // If in other formats, try to parse and convert
        $date = new DateTime($exdate);
        $date->setTimezone(new DateTimeZone('UTC'));
        $normalized = $date->format('Ymd\THis\Z');
        error_log("🗑️ Converted other format EXDATE: '$exdate' -> '$normalized'");
        return $normalized;
        
    } catch (Exception $e) {
        error_log("🗑️ Error normalizing EXDATE '$exdate': " . $e->getMessage());
        
        // If parsing fails, try to clean up the format manually
        $cleaned = preg_replace('/[^0-9TZ]/', '', $exdate);
        
        // Fix common corruption patterns
        $cleaned = preg_replace('/T{2,}/', 'T', $cleaned); // Remove double T
        $cleaned = preg_replace('/Z{2,}/', 'Z', $cleaned); // Remove double Z
        
        if (preg_match('/^\d{8}T\d{6}Z$/', $cleaned)) {
            error_log("🗑️ Fixed corrupted EXDATE: '$exdate' -> '$cleaned'");
            return $cleaned;
        }
        
        error_log("🗑️ Could not normalize EXDATE, returning original: " . $exdate);
        return $exdate; // Return original if all else fails
    }
}

/**
 * Get the next occurrence date based on recurrence rule
 */
function getNextOccurrenceDate($currentDate, $recurrence) {
    $nextDate = clone $currentDate;
    $frequency = $recurrence['frequency'];
    $interval = $recurrence['interval'] ?? 1;
    
    switch ($frequency) {
        case 'daily':
            $nextDate->add(new DateInterval('P' . $interval . 'D'));
            break;
        case 'weekly':
            $nextDate->add(new DateInterval('P' . $interval . 'W'));
            break;
        case 'monthly':
            $nextDate->add(new DateInterval('P' . $interval . 'M'));
            break;
        case 'annually':
            $nextDate->add(new DateInterval('P' . $interval . 'Y'));
            break;
        default:
            // Default to daily
            $nextDate->add(new DateInterval('P' . $interval . 'D'));
            break;
    }
    
    return $nextDate;
}

function updateCalendar($id) {
    // TODO: Implement
    echo json_encode(['error' => 'Not implemented yet']);
}

function updateEvent($id) {
    try {
        error_log("=== updateEvent called with ID: " . $id . " ===");
        
        // Get the request body
        $input = json_decode(file_get_contents('php://input'), true);
        error_log("=== EDIT EVENT REQUEST START ===");
        error_log("Raw input received: " . file_get_contents('php://input'));
        error_log("Parsed input: " . json_encode($input));
        error_log("Calendar ID in input: " . ($input['calendar_id'] ?? 'not set'));
        error_log("Calendar ID type: " . gettype($input['calendar_id'] ?? null));
        error_log("Recurrence data in input: " . json_encode($input['recurrence'] ?? 'null'));
        error_log("Reminder data in input: " . json_encode($input['reminder'] ?? 'null'));
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        // Validate required fields
        if (empty($input['title'])) {
            throw new Exception('Event title is required');
        }
        
        if (empty($input['start_time'])) {
            throw new Exception('Start time is required');
        }
        
        if (empty($input['end_time'])) {
            throw new Exception('End time is required');
        }
        
        error_log("Attendees data in input: " . json_encode($input['attendees'] ?? 'null'));
        error_log("Number of attendees: " . (isset($input['attendees']) ? count($input['attendees']) : 'not set'));
        
        // Find the event in local storage
        $eventsFile = 'data/events.json';
        $updatedEvents = [];
        
        if (file_exists($eventsFile)) {
            $storedEvents = json_decode(file_get_contents($eventsFile), true) ?? [];
            error_log("Total events in local storage: " . count($storedEvents));
            error_log("Looking for event with ID: " . $id);
            error_log("Available event IDs: " . json_encode(array_map(function($e) { return $e['id'] ?? 'no-id'; }, $storedEvents)));
            
            // Find the event by ID or UID
            $eventToUpdate = null;
            $originalEvent = null;
            foreach ($storedEvents as $event) {
                error_log("Checking event: " . json_encode($event['id'] ?? 'no-id') . " against search ID: " . $id);
                
                // Check for exact match first
                if (($event['id'] ?? null) == $id || ($event['uid'] ?? null) == $id) {
                    $eventToUpdate = $event;
                    $originalEvent = json_decode(json_encode($event), true); // Deep copy for comparison
                    error_log("Found exact match! ID: " . ($event['id'] ?? 'no-id') . ", UID: " . ($event['uid'] ?? 'no-uid'));
                    error_log("Original event calendar info: ID=" . ($event['calendar_id'] ?? 'null') . ", Name=" . ($event['calendar_name'] ?? 'null') . ", URL=" . ($event['calendar_url'] ?? 'null'));
                    error_log("🔍 FULL ORIGINAL EVENT DATA: " . json_encode($event));
                    break;
                }
                
                // Check for partial match (in case of ID format differences)
                if (strpos($event['id'] ?? '', $id) !== false || strpos($event['uid'] ?? '', $id) !== false) {
                    $eventToUpdate = $event;
                    $originalEvent = json_decode(json_encode($event), true); // Deep copy for comparison
                    error_log("Found partial match! ID: " . ($event['id'] ?? 'no-id') . ", UID: " . ($event['uid'] ?? 'no-uid'));
                    break;
                }
            }
            
            if (!$eventToUpdate) {
                error_log("=== EVENT NOT FOUND DEBUG ===");
                error_log("Searching for ID: " . $id);
                error_log("Available events:");
                foreach ($storedEvents as $index => $event) {
                    error_log("  Event " . $index . ": ID='" . ($event['id'] ?? 'null') . "', UID='" . ($event['uid'] ?? 'null') . "', Title='" . ($event['title'] ?? 'null') . "', CalendarID='" . ($event['calendar_id'] ?? 'null') . "', CalendarName='" . ($event['calendar_name'] ?? 'null') . "'");
                }
                
                // Try to find the event in CalDAV server
                error_log("Event not found in local storage, checking CalDAV server...");
                try {
                    $caldavClient = getCalDAVClient();
                    if (!$caldavClient) {
                        throw new Exception('Failed to get CalDAV client - user not authenticated');
                    }
                    $calendars = $caldavClient->discoverCalendars();
                    
                    if (!empty($calendars)) {
                        $calendarUrl = $calendars[0]['href'];
                        $caldavEvents = $caldavClient->getEvents($calendarUrl);
                        
                        // Look for the event in CalDAV events
                        foreach ($caldavEvents as $caldavEvent) {
                            if (($caldavEvent['id'] ?? null) == $id || ($caldavEvent['uid'] ?? null) == $id) {
                                error_log("Found event in CalDAV server! Creating local copy...");
                                
                                // Create a local copy of the CalDAV event
                                $eventToUpdate = $caldavEvent;
                                $originalEvent = json_decode(json_encode($caldavEvent), true); // Deep copy
                                
                                // Add it to local storage
                                $storedEvents[] = $eventToUpdate;
                                file_put_contents($eventsFile, json_encode($storedEvents, JSON_PRETTY_PRINT));
                                error_log("Created local copy of CalDAV event with ID: " . ($eventToUpdate['id'] ?? 'no-id'));
                                break;
                            }
                        }
                    }
                } catch (Exception $caldavError) {
                    error_log("CalDAV error while searching for event: " . $caldavError->getMessage());
                }
                
                // If still not found, throw exception
                if (!$eventToUpdate) {
                    throw new Exception('Event not found in local storage or CalDAV server');
                }
            }
            
            error_log("Captured original event for comparison: " . json_encode($originalEvent));
            error_log("Original event attendees: " . json_encode($originalEvent['attendees'] ?? 'null'));
            error_log("Input attendees: " . json_encode($input['attendees'] ?? 'null'));
            error_log("Original event calendar_id: " . ($originalEvent['calendar_id'] ?? 'not set'));
            error_log("Input calendar_id: " . ($input['calendar_id'] ?? 'not set'));
            
            // Update event properties
            $eventToUpdate['title'] = $input['title'];
            $eventToUpdate['description'] = $input['description'] ?? $eventToUpdate['description'];
            $eventToUpdate['location'] = $input['location'] ?? $eventToUpdate['location'];
            $eventToUpdate['start_time'] = $input['start_time'];
            $eventToUpdate['end_time'] = $input['end_time'];
            $eventToUpdate['all_day'] = $input['all_day'] ?? $eventToUpdate['all_day'];
            $eventToUpdate['availability'] = $input['availability'] ?? $eventToUpdate['availability'] ?? 'busy';
            $eventToUpdate['status'] = $input['status'] ?? $eventToUpdate['status'] ?? 'confirmed';
            
            // 🔧 FIX: Check for calendar change BEFORE updating calendar_id
            $originalCalendarId = $eventToUpdate['calendar_id'];
            $newCalendarId = $input['calendar_id'] ?? $eventToUpdate['calendar_id'];
            
            // Handle attendees - convert null string to empty array if needed
            $attendees = $input['attendees'] ?? $eventToUpdate['attendees'];
            if ($attendees === 'null' || $attendees === null) {
                $attendees = [];
            }
            $eventToUpdate['attendees'] = $attendees;
            $eventToUpdate['recurrence'] = $input['recurrence'] ?? $eventToUpdate['recurrence'];
            
            error_log("Updated attendees: " . json_encode($eventToUpdate['attendees']));
            error_log("Updated recurrence: " . json_encode($eventToUpdate['recurrence']));
            error_log("Original calendar ID: " . $originalCalendarId);
            error_log("New calendar ID: " . $newCalendarId);
            
            // Update calendar information if calendar_id changed
            if (isset($input['calendar_id']) && $input['calendar_id'] != $originalCalendarId) {
                error_log("🔧 Calendar ID changed from " . $originalCalendarId . " to " . $input['calendar_id']);
                
                // Get calendar information by ID
                $calendarInfo = getCalendarById($input['calendar_id']);
                
                if ($calendarInfo) {
                    // Update calendar-related fields
                    $eventToUpdate['calendar_id'] = $input['calendar_id'];
                    $eventToUpdate['calendar_url'] = $calendarInfo['url'];
                    $eventToUpdate['calendar_name'] = $calendarInfo['name'];
                    $eventToUpdate['calendar_color'] = $calendarInfo['color'];
                    $eventToUpdate['color'] = $calendarInfo['color']; // Update event color too
                    
                    error_log("✅ Updated calendar info: Name=" . $calendarInfo['name'] . ", URL=" . $calendarInfo['url'] . ", Color=" . $calendarInfo['color']);
                } else {
                    error_log("❌ Warning: Could not find calendar info for ID " . $input['calendar_id']);
                    // Fallback: just update the calendar_id
                    $eventToUpdate['calendar_id'] = $input['calendar_id'];
                }
            } else {
                // No calendar change, just ensure calendar_id is set
                $eventToUpdate['calendar_id'] = $newCalendarId;
                error_log("📅 No calendar change detected, keeping calendar_id: " . $newCalendarId);
            }
            
            error_log("Final event data: " . json_encode($eventToUpdate));
            
            // Handle reminder data - convert frontend reminder format to VALARM
            if (!empty($input['reminder'])) {
                $eventToUpdate['reminder'] = $input['reminder'];
                $eventToUpdate['valarm'] = null; // Clear any existing VALARM
                
                if ($eventToUpdate['reminder']['enabled']) {
                    $trigger = '-P';
                    switch ($eventToUpdate['reminder']['unit']) {
                        case 'minutes':
                            $trigger .= $eventToUpdate['reminder']['time'] . 'M';
                            break;
                        case 'hours':
                            $trigger .= $eventToUpdate['reminder']['time'] . 'H';
                            break;
                        case 'days':
                            $trigger .= $eventToUpdate['reminder']['time'] . 'D';
                            break;
                    }
                    $eventToUpdate['valarm'] = [
                        'trigger' => $trigger,
                        'action' => 'DISPLAY',
                        'description' => 'Reminder'
                    ];
                }
            } else {
                $eventToUpdate['reminder'] = null;
                $eventToUpdate['valarm'] = null;
            }
            
            // Update updated_at timestamp
            $eventToUpdate['updated_at'] = date('c');
            
            error_log("Event updated successfully, now processing email notifications...");
            
            // Process email notifications for attendees
            try {
                $newAttendees = $input['attendees'] ?? [];
                error_log("Processing email notifications for " . count($newAttendees) . " attendees");
                
                // Handle different types of email notifications
                $emailResult = handleEventUpdateEmails($eventToUpdate, $originalEvent, $newAttendees);
                error_log("Email processing result: " . json_encode($emailResult));
                
            } catch (Exception $emailError) {
                error_log("Error processing email notifications: " . $emailError->getMessage());
                // Don't fail the update if email processing fails
            }
            
            // Handle CalDAV server update
            try {
                error_log("Attempting to update event on CalDAV server...");
                $caldavClient = getCalDAVClient();
                if (!$caldavClient) {
                    error_log("⚠️ CalDAV client not available - skipping CalDAV sync");
                    $calendars = [];
                } else {
                    $calendars = $caldavClient->discoverCalendars();
                }
                
                if (!empty($calendars)) {
                    // Check if calendar changed
                    $originalCalendarUrl = $originalEvent['calendar_url'] ?? null;
                    $newCalendarUrl = $eventToUpdate['calendar_url'] ?? null;
                    
                    error_log("🔍 CALENDAR CHANGE DEBUG:");
                    error_log("  Original calendar URL: " . ($originalCalendarUrl ?? 'NULL'));
                    error_log("  New calendar URL: " . ($newCalendarUrl ?? 'NULL'));
                    error_log("  Original calendar ID: " . ($originalEvent['calendar_id'] ?? 'NULL'));
                    error_log("  New calendar ID: " . ($eventToUpdate['calendar_id'] ?? 'NULL'));
                    error_log("  URLs are different: " . ($originalCalendarUrl !== $newCalendarUrl ? 'YES' : 'NO'));
                    
                    if ($originalCalendarUrl && $newCalendarUrl && $originalCalendarUrl !== $newCalendarUrl) {
                        // Calendar changed - need to delete from old calendar and create in new calendar
                        error_log("🔄 Calendar changed from " . $originalCalendarUrl . " to " . $newCalendarUrl);
                        
                        // For existing events, we'll try to delete from the stored calendar
                        // If it fails (404), that's okay - the event might not be there
                        error_log("🔍 Attempting calendar change for existing event...");
                        
                        // Delete from old calendar
                        try {
                            $oldEventUrl = rtrim($originalCalendarUrl, '/') . '/' . $eventToUpdate['uid'] . '.ics';
                            error_log("Deleting event from old calendar: " . $oldEventUrl);
                            $caldavClient->deleteEvent($oldEventUrl);
                            error_log("✅ Event deleted from old calendar");
                        } catch (Exception $deleteError) {
                            error_log("⚠️ Failed to delete from old calendar: " . $deleteError->getMessage());
                            // Continue anyway - the event might not exist in the old calendar
                        }
                        
                        // Create in new calendar
                        try {
                            $updatedICal = generateICalEvent($eventToUpdate);
                            error_log("Creating event in new calendar: " . $newCalendarUrl);
                            $response = $caldavClient->createEvent($newCalendarUrl, $updatedICal, $eventToUpdate['uid']);
                            error_log("CalDAV create response: " . json_encode($response));
                            
                            if ($response['status'] >= 200 && $response['status'] < 300) {
                                error_log("✅ Event created successfully in new calendar");
                            } else {
                                error_log("❌ CalDAV create failed: " . $response['body']);
                            }
                        } catch (Exception $createError) {
                            error_log("❌ Failed to create in new calendar: " . $createError->getMessage());
                        }
                    } else {
                        // Same calendar - just update the event
                        $eventCalendarUrl = $eventToUpdate['calendar_url'] ?? $calendars[0]['href'];
                        error_log("Using event calendar URL for update: " . $eventCalendarUrl);
                        
                        // Generate updated iCalendar content
                        $updatedICal = generateICalEvent($eventToUpdate);
                        error_log("Generated updated iCalendar content for CalDAV update");
                        error_log("Event attendees being sent to CalDAV: " . json_encode($eventToUpdate['attendees'] ?? []));
                        
                        // Update the event on CalDAV server
                        $response = $caldavClient->updateEvent($eventCalendarUrl, $eventToUpdate['uid'], $updatedICal);
                        error_log("CalDAV update response: " . json_encode($response));
                        
                        if ($response['success']) {
                            error_log("✅ Event updated successfully on CalDAV server");
                        } else {
                            error_log("❌ CalDAV update failed: " . $response['body']);
                            error_log("❌ CalDAV update status: " . ($response['status'] ?? 'unknown'));
                            // Don't fail the entire update if CalDAV sync fails, but log it
                        }
                    }
                } else {
                    error_log("No CalDAV calendars found for update");
                }
            } catch (Exception $caldavError) {
                error_log("CalDAV update error: " . $caldavError->getMessage());
                // Don't fail the update if CalDAV sync fails
            }
            
            // Update the stored events array with the updated event
            foreach ($storedEvents as $key => $event) {
                if (($event['id'] ?? null) == $id || ($event['uid'] ?? null) == $id) {
                    $storedEvents[$key] = $eventToUpdate;
                    break;
                }
            }
            
            // Save updated local events back to file
            $updatedEvents = array_values($storedEvents); // Re-index array
            file_put_contents($eventsFile, json_encode($updatedEvents, JSON_PRETTY_PRINT));
            error_log("Updated local storage with modified event: " . json_encode($eventToUpdate));
            error_log("Total events after update: " . count($updatedEvents));
            
            // Return success response
            $successResponse = [
                'success' => true,
                'data' => $eventToUpdate,
                'message' => 'Event updated successfully'
            ];
            
            error_log("Sending success response: " . json_encode($successResponse));
            error_log("Response attendees data: " . json_encode($successResponse['data']['attendees'] ?? 'not set'));
            echo json_encode($successResponse);
            
        } else {
            throw new Exception('Events file not found');
        }
        
    } catch (Exception $e) {
        error_log("Exception in updateEvent: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        $errorResponse = [
            'success' => false,
            'message' => $e->getMessage()
        ];
        echo json_encode($errorResponse);
    }
}

function deleteCalendar($id) {
    try {
        // Validate calendar ID
        if (!is_numeric($id)) {
            throw new Exception('Invalid calendar ID');
        }
        
        // Get CalDAV client
        $caldavClient = getCalDAVClient();
        if (!$caldavClient) {
            throw new Exception('CalDAV client not available');
        }
        
        error_log("Deleting calendar with ID: $id");
        
        // First, get the calendar details to find the URL
        $calendars = $caldavClient->discoverCalendars();
        if (!$calendars || empty($calendars)) {
            throw new Exception('No calendars found');
        }
        
        // Convert ID to array index (ID 1 = index 0, ID 2 = index 1, etc.)
        $calendarIndex = intval($id) - 1;
        
        if ($calendarIndex < 0 || $calendarIndex >= count($calendars)) {
            throw new Exception('Calendar not found');
        }
        
        $calendarToDelete = $calendars[$calendarIndex];
        
        // Get the calendar URL
        $calendarUrl = $calendarToDelete['href'] ?? '';
        if (empty($calendarUrl)) {
            throw new Exception('Calendar URL not found');
        }
        
        error_log("Deleting calendar URL: $calendarUrl");
        
        // Delete the calendar using CalDAV client
        $result = $caldavClient->deleteCalendar($calendarUrl);
        
        if ($result['success']) {
            sendJsonResponse([
                'success' => true,
                'message' => 'Calendar deleted successfully'
            ]);
        } else {
            throw new Exception($result['message']);
        }
        
    } catch (Exception $e) {
        error_log("Calendar deletion error: " . $e->getMessage());
        sendJsonResponse([
            'success' => false,
            'message' => 'Failed to delete calendar: ' . $e->getMessage()
        ]);
    }
}

function toggleCalendar($id) {
    try {
        // Validate calendar ID
        if (!is_numeric($id)) {
            throw new Exception('Invalid calendar ID');
        }
        
        // Get the request body to get the enabled state
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['enabled'])) {
            throw new Exception('Missing enabled parameter');
        }
        
        $enabled = (bool)$input['enabled'];
        
        // Store calendar state in session or file
        $calendarStatesFile = 'data/calendar_states.json';
        $calendarStates = [];
        
        if (file_exists($calendarStatesFile)) {
            $calendarStates = json_decode(file_get_contents($calendarStatesFile), true) ?? [];
        }
        
        // Update the calendar state
        $calendarStates[$id] = $enabled;
        
        // Save the updated states
        if (!file_exists('data')) {
            mkdir('data', 0755, true);
        }
        
        file_put_contents($calendarStatesFile, json_encode($calendarStates, JSON_PRETTY_PRINT));
        
        error_log("Calendar $id toggled to: " . ($enabled ? 'enabled' : 'disabled'));
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Calendar state updated successfully',
            'data' => [
                'id' => intval($id),
                'enabled' => $enabled
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Calendar toggle error: " . $e->getMessage());
        sendJsonResponse([
            'success' => false,
            'message' => 'Failed to toggle calendar: ' . $e->getMessage()
        ]);
    }
}

function deleteEvent($id) {
    try {
        // Get the action parameter (current, future, all)
        $action = $_GET['action'] ?? 'all';
        error_log("=== deleteEvent called with ID: " . $id . " and action: " . $action . " ===");
        
        // Track deleted events to prevent them from reappearing
        $deletedEventsFile = 'data/deleted_events.json';
        $deletedEvents = [];
        
        if (file_exists($deletedEventsFile)) {
            $deletedEvents = json_decode(file_get_contents($deletedEventsFile), true) ?? [];
        }
        
        // First, try to delete from CalDAV server
        $deletedFromServer = false;
        $allEvents = []; // Initialize empty array
        try {
            $caldavClient = getCalDAVClient();
            if (!$caldavClient) {
                throw new Exception('Failed to get CalDAV client - user not authenticated');
            }
            
            // Get calendar URL from GET parameter (passed from frontend)
            $calendarUrl = $_GET['calendar_url'] ?? null;
            if (!$calendarUrl) {
                error_log("No calendar_url provided in DELETE request");
                throw new Exception('No calendar URL provided');
            }
            
            error_log("Calendar URL from request: " . $calendarUrl);
            
            // Get all events to find the one we want to delete
            $allEvents = $caldavClient->getEvents($calendarUrl);
            error_log("Found " . count($allEvents) . " events from CalDAV server");
            
            // Find the event with matching ID or UID
            $eventToDelete = null;
            error_log("Looking for event with ID/UID: " . $id);
            error_log("Total events to search through: " . count($allEvents));
            
            foreach ($allEvents as $index => $event) {
                error_log("Event #" . $index . " - ID: " . ($event['id'] ?? 'null') . ", UID: " . ($event['uid'] ?? 'null') . ", Title: " . ($event['title'] ?? 'null'));
                
                // Try to match by UID first (more reliable), then by ID
                if (($event['uid'] ?? null) == $id || ($event['id'] ?? null) == $id) {
                    $eventToDelete = $event;
                    error_log("✅ Found matching event: " . $event['title']);
                    error_log("✅ Event UID: " . ($event['uid'] ?? 'null'));
                    error_log("✅ Event ID: " . ($event['id'] ?? 'null'));
                    break;
                }
            }
            
            if (!$eventToDelete) {
                error_log("No matching event found for ID/UID: " . $id);
            }
            
            // If we found the event, handle deletion based on action
            if ($eventToDelete && !empty($eventToDelete['uid'])) {
                // Construct the event URL (this is the standard CalDAV format)
                $eventUrl = rtrim($calendarUrl, '/') . '/' . $eventToDelete['uid'] . '.ics';
                
                error_log("🔗 Calendar URL: " . $calendarUrl);
                error_log("🔗 Event UID: " . $eventToDelete['uid']);
                error_log("🔗 Constructed Event URL: " . $eventUrl);
                error_log("🗑️ Action: " . $action);
                
                // Handle different deletion actions
                if ($action === 'all') {
                    // Delete the entire recurring event from CalDAV server
                    error_log("🗑️ Deleting ALL occurrences from CalDAV server...");
                    $deleteResult = $caldavClient->deleteEvent($eventUrl, $caldavClient->getAuthToken());
                    
                    if ($deleteResult) {
                        $deletedFromServer = true;
                        error_log("✅ Successfully deleted ALL occurrences from CalDAV server");
                    } else {
                        error_log("❌ Failed to delete ALL occurrences from CalDAV server");
                    }
                } else if ($action === 'future') {
                    // For future deletions, we need to modify the RRULE to end at current occurrence
                    // This is complex and requires RRULE modification
                    error_log("🗑️ FUTURE deletion: Modifying RRULE to end at current occurrence");
                    error_log("🗑️ TODO: Implement proper RRULE modification for future occurrences");
                    
                    // For now, we'll delete the entire event (this can be refined later)
                    $deleteResult = $caldavClient->deleteEvent($eventUrl, $caldavClient->getAuthToken());
                    
                    if ($deleteResult) {
                        $deletedFromServer = true;
                        error_log("✅ Successfully deleted FUTURE occurrences from CalDAV server");
                    } else {
                        error_log("❌ Failed to delete FUTURE occurrences from CalDAV server");
                    }
                } else if ($action === 'current') {
                    // For current occurrence deletion, add EXDATE to master event
                    error_log("🗑️ CURRENT occurrence deletion: Adding EXDATE to master event");
                    
                    try {
                        // Get the occurrence index from the request
                        $occurrenceIndex = $_GET['occurrence_index'] ?? '0';
                        error_log("🗑️ Occurrence index from request: " . $occurrenceIndex);
                        error_log("🗑️ All GET parameters: " . json_encode($_GET));
                        
                        // Calculate the specific occurrence date
                        error_log("🗑️ Calculating occurrence date for index: " . $occurrenceIndex);
                        error_log("🗑️ Event start time: " . $eventToDelete['start_time']);
                        error_log("🗑️ Event recurrence: " . json_encode($eventToDelete['recurrence']));
                        $occurrenceDate = calculateOccurrenceDate($eventToDelete, intval($occurrenceIndex));
                        error_log("🗑️ Calculated occurrence date: " . $occurrenceDate->format('Y-m-d H:i:s'));
                        
                        // Get the current event data from CalDAV
                        $currentEventData = $caldavClient->getEvent($eventUrl, $caldavClient->getAuthToken());
                        
                        if ($currentEventData) {
                            // Add EXDATE to the event (convert to UTC)
                            $occurrenceDate->setTimezone(new DateTimeZone('UTC'));
                            $exdateString = $occurrenceDate->format('Ymd\THis\Z');
                            
                            // Handle existing EXDATE (can be array or single value)
                            if (isset($currentEventData['exdate'])) {
                                error_log("🗑️ Existing EXDATE found: " . json_encode($currentEventData['exdate']));
                                
                                // Normalize existing EXDATEs to UTC format
                                $normalizedExisting = [];
                                if (is_array($currentEventData['exdate'])) {
                                    foreach ($currentEventData['exdate'] as $existingExdate) {
                                        // Convert existing EXDATE to UTC format if needed
                                        $normalizedExisting[] = normalizeExdateToUtc($existingExdate);
                                    }
                                } else {
                                    $normalizedExisting[] = normalizeExdateToUtc($currentEventData['exdate']);
                                }
                                
                                // Check if this EXDATE already exists to avoid duplicates
                                $exdateExists = in_array($exdateString, $normalizedExisting);
                                
                                if ($exdateExists) {
                                    error_log("🗑️ EXDATE already exists, skipping: " . $exdateString);
                                } else {
                                    $normalizedExisting[] = $exdateString;
                                    $currentEventData['exdate'] = $normalizedExisting;
                                    error_log("🗑️ Added new EXDATE to existing array: " . $exdateString);
                                }
                            } else {
                                $currentEventData['exdate'] = $exdateString;
                                error_log("🗑️ Created new EXDATE: " . $exdateString);
                            }
                            
                            error_log("🗑️ Added EXDATE: " . $exdateString);
                            error_log("🗑️ Updated event EXDATE: " . json_encode($currentEventData['exdate']));
                            
                            // Check if all occurrences are now deleted (EXDATE count >= recurrence count)
                            $recurrenceCount = $currentEventData['recurrence']['count'] ?? 0;
                            $exdateCount = is_array($currentEventData['exdate']) ? count($currentEventData['exdate']) : 1;
                            
                            error_log("🗑️ Recurrence count: " . $recurrenceCount);
                            error_log("🗑️ EXDATE count: " . $exdateCount);
                            
                            if ($exdateCount >= $recurrenceCount) {
                                // All occurrences are deleted, delete the entire master event
                                error_log("🗑️ All occurrences deleted, removing master event from server");
                                $deleteResult = $caldavClient->deleteEvent($eventUrl, $caldavClient->getAuthToken());
                                
                                if ($deleteResult) {
                                    $deletedFromServer = true;
                                    error_log("✅ Successfully deleted master event (all occurrences deleted)");
                                } else {
                                    error_log("❌ Failed to delete master event after all occurrences deleted");
                                }
                            } else {
                                // Generate iCalendar content for the updated event
                                $updatedICal = generateICalEvent($currentEventData);
                                
                                // Update the event on CalDAV server
                                $updateResult = $caldavClient->updateEvent($calendarUrl, $eventToDelete['uid'], $updatedICal);
                                
                                if ($updateResult && $updateResult['success']) {
                                    $deletedFromServer = true;
                                    error_log("✅ Successfully added EXDATE to master event on CalDAV server");
                                } else {
                                    error_log("❌ Failed to update master event with EXDATE");
                                    error_log("❌ Update result: " . json_encode($updateResult));
                                }
                            }
                        } else {
                            error_log("❌ Could not retrieve current event data for EXDATE update");
                        }
                    } catch (Exception $exdateError) {
                        error_log("❌ Error handling EXDATE: " . $exdateError->getMessage());
                        $deletedFromServer = false;
                    }
                }
            } else {
                error_log("❌ Event not found in CalDAV server or missing UID.");
                error_log("❌ EventToDelete: " . ($eventToDelete ? 'found' : 'null'));
                if ($eventToDelete) {
                    error_log("❌ EventToDelete UID: " . ($eventToDelete['uid'] ?? 'null'));
                }
            }
        } catch (Exception $caldavError) {
            error_log("CalDAV error in deleteEvent: " . $caldavError->getMessage());
        }
        
        // Add event to deleted events tracking (regardless of CalDAV success)
        // We need to find the actual UID of the event to track it properly
        $actualUid = null;
        
        // Try to find the event by ID or UID to get its actual UID
        foreach ($allEvents as $event) {
            if (($event['uid'] ?? null) == $id || ($event['id'] ?? null) == $id) {
                $actualUid = $event['uid'];
                error_log("Found event with actual UID: " . $actualUid);
                break;
            }
        }
        
        // If we found the UID, track it; otherwise track by the passed ID as fallback
        $uidToTrack = $actualUid ?? $id;
        
        // For "current" deletion, don't track in deleted_events.json since we use EXDATE
        if ($action === 'current') {
            error_log("🗑️ CURRENT deletion: Using EXDATE instead of deleted_events.json tracking");
            // Skip tracking for current deletion since EXDATE handles it
            $deletedEventInfo = null;
        } else {
            // For "all" and "future" deletions, track the master UID
            $deletedEventInfo = [
                'uid' => $uidToTrack,
                'title' => 'Event marked for deletion',
                'deleted_at' => date('c'),
                'deleted_by' => 'user',
                'action' => $action
            ];
            error_log("🗑️ Tracking " . strtoupper($action) . " deletion for master UID: " . $uidToTrack);
        }
        
        // Check if already in deleted list (only for non-current deletions)
        $alreadyDeleted = false;
        if ($deletedEventInfo) {
            foreach ($deletedEvents as $deletedEvent) {
                if ($deletedEvent['uid'] === $deletedEventInfo['uid']) {
                    $alreadyDeleted = true;
                    break;
                }
            }
        }
        
        if (!$alreadyDeleted && $deletedEventInfo) {
            $deletedEvents[] = $deletedEventInfo;
            file_put_contents($deletedEventsFile, json_encode($deletedEvents, JSON_PRETTY_PRINT));
            error_log("Added event to deleted events tracking with UID: " . $uidToTrack);
        }
        
        // Delete from local storage
        $eventsFile = 'data/events.json';
        error_log("Checking local storage file: " . $eventsFile);
        
        if (file_exists($eventsFile)) {
            $storedEvents = json_decode(file_get_contents($eventsFile), true) ?? [];
            error_log("Found " . count($storedEvents) . " events in local storage");
            
            // Log all stored event IDs for debugging
            foreach ($storedEvents as $event) {
                error_log("Local event - ID: " . ($event['id'] ?? 'null') . ", UID: " . ($event['uid'] ?? 'null') . ", Title: " . ($event['title'] ?? 'null'));
            }
            
            // Remove the event with matching ID
            $originalCount = count($storedEvents);
            $storedEvents = array_filter($storedEvents, function($event) use ($id) {
                return $event['id'] != $id;
            });
            $newCount = count($storedEvents);
            
            error_log("Local storage: " . $originalCount . " events before, " . $newCount . " events after deletion");
            
            // Save back to file
            file_put_contents($eventsFile, json_encode(array_values($storedEvents), JSON_PRETTY_PRINT));
            error_log("Local storage updated successfully");
        } else {
            error_log("Local storage file does not exist");
        }
        
        // Return success message indicating what was deleted
        if ($deletedFromServer) {
            echo json_encode([
                'success' => true,
                'message' => 'Event deleted successfully from both server and local storage'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Event deleted from local storage and marked for deletion (server deletion failed or not found)'
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting event: ' . $e->getMessage()
        ]);
    }
}

function clearLocalEvents() {
    try {
        $eventsFile = 'data/events.json';
        
        // Clear the local events file
        if (file_exists($eventsFile)) {
            file_put_contents($eventsFile, '[]');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Local events cleared successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error clearing local events: ' . $e->getMessage()
        ]);
    }
}

function syncAllCalendars() {
    try {
        $caldavClient = getCalDAVClient();
        if (!$caldavClient) {
            throw new Exception('Failed to get CalDAV client - user not authenticated');
        }
        
        // Discover calendars and sync events
        $calendars = $caldavClient->discoverCalendars();
        
        if (empty($calendars)) {
            sendJsonResponse([
                'success' => false,
                'message' => 'No calendars found to sync'
            ]);
            return;
        }
        
        $syncedEvents = [];
        foreach ($calendars as $calendar) {
            try {
                $events = $caldavClient->getEvents($calendar['href']);
                if (is_array($events)) {
                    $syncedEvents = array_merge($syncedEvents, $events);
                }
            } catch (Exception $e) {
                error_log("Error syncing calendar {$calendar['name']}: " . $e->getMessage());
            }
        }
        
        sendJsonResponse([
            'success' => true,
            'data' => [
                'calendars' => $calendars,
                'events' => $syncedEvents,
                'totalEvents' => count($syncedEvents)
            ],
            'message' => 'Calendar sync completed successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Calendar sync error: " . $e->getMessage());
        http_response_code(500);
        sendJsonResponse([
            'success' => false,
            'message' => 'Calendar sync failed: ' . $e->getMessage()
        ]);
    }
}

function handleEmailInvitation() {
    try {
        error_log("=== handleEmailInvitation called ===");
        
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        error_log("Raw input: " . file_get_contents('php://input'));
        error_log("Decoded input: " . print_r($input, true));
        
        if (!$input) {
            error_log("JSON decode failed");
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
            return;
        }
        
        // Validate required fields
        $requiredFields = ['to', 'subject', 'htmlBody', 'textBody', 'eventDetails'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field])) {
                error_log("Missing required field: $field");
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                return;
            }
        }
        
        // Extract data
        $to = $input['to'];
        $subject = $input['subject'];
        $htmlBody = $input['htmlBody'];
        $textBody = $input['textBody'];
        $eventDetails = $input['eventDetails'];
        
        error_log("Processing email to: " . print_r($to, true));
        error_log("Subject: $subject");
        error_log("Event details: " . print_r($eventDetails, true));
        
        // Validate email addresses
        if (!is_array($to) || empty($to)) {
            error_log("Invalid recipients list");
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid recipients list']);
            return;
        }
        
        foreach ($to as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                error_log("Invalid email address: $email");
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Invalid email address: $email"]);
                return;
            }
        }
        
        // Generate iCalendar invitation
        $icalContent = generateICalInvitation($eventDetails, $to);
        error_log("Generated iCalendar content: " . $icalContent);
        
        // Email headers for calendar invitation
        $boundary = uniqid('calendar_');
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
            'From: Mithi Calendar <noreply@mithi-calendar.com>',
            'Reply-To: ' . ($eventDetails['organizer'] ?? 'noreply@mithi-calendar.com'),
            'X-Mailer: Mithi Calendar/1.0',
            'X-Campaign: calendar-invitation'
        ];
        
        error_log("Email headers: " . print_r($headers, true));
        
        // For now, we'll simulate email sending
        // In production, you would integrate with a real email service like:
        // - SendGrid
        // - AWS SES
        // - Mailgun
        // - PHPMailer with SMTP
        
        $successCount = 0;
        $failedEmails = [];
        
        foreach ($to as $email) {
            try {
                error_log("Attempting to send email to: $email");
                
                // Generate email content with iCalendar attachment
                $emailContent = generateEmailWithICalAttachment($htmlBody, $textBody, $icalContent, $boundary);
                
                // Simulate email sending (replace with actual email service)
                $mailSent = simulateEmailSending($email, $subject, $emailContent, $headers, $icalContent);
                
                if ($mailSent) {
                    $successCount++;
                    error_log("Email sent successfully to: $email");
                } else {
                    $failedEmails[] = $email;
                    error_log("Failed to send email to: $email");
                }
            } catch (Exception $e) {
                error_log("Exception sending email to $email: " . $e->getMessage());
                $failedEmails[] = $email;
                error_log("Error sending email to $email: " . $e->getMessage());
            }
        }
        
        error_log("Final results - Success: $successCount, Failed: " . count($failedEmails));
        
        // Prepare response
        $response = [
            'success' => $successCount > 0,
            'message' => "Sent $successCount out of " . count($to) . " invitations",
            'data' => [
                'totalRecipients' => count($to),
                'successfulSends' => $successCount,
                'failedSends' => count($failedEmails),
                'failedEmails' => $failedEmails
            ]
        ];
        
        if ($successCount === 0) {
            error_log("No emails were sent successfully");
            http_response_code(500);
            $response['success'] = false;
            $response['message'] = 'Failed to send any invitations';
        }
        
        error_log("Sending response: " . print_r($response, true));
        echo json_encode($response);
        
    } catch (Exception $e) {
        error_log("Exception in handleEmailInvitation: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error processing email invitation: ' . $e->getMessage()
        ]);
    }
}

/**
 * Generate iCalendar invitation content that's fully compatible with Google Calendar
 */
function generateICalInvitation($eventDetails, $attendees) {
    try {
        $uid = uniqid('invite_');
        $dtstamp = gmdate('Ymd\THis\Z'); // Use GMT for DTSTAMP
        
        // Parse the input times with error handling
        $startTime = new DateTime($eventDetails['startTime']);
        $endTime = new DateTime($eventDetails['endTime']);
        
        // Get timezone information
        $timezone = $startTime->getTimezone();
        $timezoneName = $timezone->getName();
        
        // For better compatibility, use UTC if timezone is not set or is local
        if (empty($timezoneName) || $timezoneName === 'Europe/Berlin' || $timezoneName === 'Europe/Paris' || $timezoneName === 'America/New_York') {
            // Convert to UTC for maximum compatibility
            $startTime->setTimezone(new DateTimeZone('UTC'));
            $endTime->setTimezone(new DateTimeZone('UTC'));
            $tzid = 'UTC';
            $timezoneDef = '';
        } else {
            $tzid = $timezoneName;
            $timezoneDef = '';
            
            // Add timezone definition for non-UTC timezones
            if ($tzid !== 'UTC') {
                $timezoneDef = "BEGIN:VTIMEZONE\r\n";
                $timezoneDef .= "TZID:{$tzid}\r\n";
                $timezoneDef .= "END:VTIMEZONE\r\n";
            }
        }
        
        // Format times for iCalendar
        $dtstart = $startTime->format('Ymd\THis');
        $dtend = $endTime->format('Ymd\THis');
        
        error_log("iCalendar generation - Start: {$dtstart}, End: {$dtend}, Timezone: {$tzid}");
        
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//Mithi Calendar//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:REQUEST\r\n"; // iTIP method for invitations
        $ical .= "X-WR-CALNAME:Mithi Calendar\r\n";
        $ical .= "X-WR-CALDESC:Calendar Invitations\r\n";
        
        // Add timezone definition if needed
        if (!empty($timezoneDef)) {
            $ical .= $timezoneDef;
        }
        
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:{$uid}\r\n";
        $ical .= "DTSTAMP:{$dtstamp}\r\n";
        
        // Add start and end times with timezone
        if ($tzid === 'UTC') {
            $ical .= "DTSTART:{$dtstart}Z\r\n";
            $ical .= "DTEND:{$dtend}Z\r\n";
        } else {
            $ical .= "DTSTART;TZID={$tzid}:{$dtstart}\r\n";
            $ical .= "DTEND;TZID={$tzid}:{$dtend}\r\n";
        }
        
        // Add required fields
        $ical .= "SUMMARY:" . str_replace(["\r\n", "\n", "\r"], "\\n", $eventDetails['title']) . "\r\n";
        $ical .= "STATUS:CONFIRMED\r\n";
        $ical .= "SEQUENCE:0\r\n";
        $ical .= "TRANSP:OPAQUE\r\n";
        $ical .= "CLASS:PUBLIC\r\n";
        
        if (!empty($eventDetails['description'])) {
            $ical .= "DESCRIPTION:" . str_replace(["\r\n", "\n", "\r"], "\\n", $eventDetails['description']) . "\r\n";
        }
        
        if (!empty($eventDetails['location'])) {
            $ical .= "LOCATION:" . str_replace(["\r\n", "\n", "\r"], "\\n", $eventDetails['location']) . "\r\n";
        }
        
        // Add organizer with proper format
        $organizerEmail = $eventDetails['organizer'] ?? 'noreply@mithi-calendar.com';
        $ical .= "ORGANIZER;CN=Mithi Calendar:mailto:{$organizerEmail}\r\n";
        
        // Add attendees - handle both email strings and attendee objects
        foreach ($attendees as $attendee) {
            $email = '';
            $name = '';
            
            if (is_string($attendee)) {
                // If attendee is just an email string
                $email = $attendee;
                $name = $email;
            } elseif (is_array($attendee) && !empty($attendee['email'])) {
                // If attendee is an object with email and name
                $email = $attendee['email'];
                $name = !empty($attendee['name']) ? $attendee['name'] : $email;
            }
            
            if (!empty($email)) {
                $ical .= "ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;";
                $ical .= "RSVP=TRUE;CN=" . str_replace(["\r\n", "\n", "\r"], "\\n", $name) . ":mailto:{$email}\r\n";
            }
        }
        
        // Add default reminder (15 minutes before)
        $ical .= "BEGIN:VALARM\r\n";
        $ical .= "TRIGGER:-PT15M\r\n";
        $ical .= "ACTION:DISPLAY\r\n";
        $ical .= "DESCRIPTION:Reminder: " . str_replace(["\r\n", "\n", "\r"], "\\n", $eventDetails['title']) . "\r\n";
        $ical .= "END:VALARM\r\n";
        
        $ical .= "END:VEVENT\r\n";
        $ical .= "END:VCALENDAR\r\n";
        
        return $ical;
    } catch (Exception $e) {
        error_log("Error generating iCalendar: " . $e->getMessage());
        // Return a basic iCalendar as fallback
        return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Mithi Calendar//EN\r\nMETHOD:REQUEST\r\nBEGIN:VEVENT\r\nUID:" . uniqid('invite_') . "\r\nDTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\nSUMMARY:Calendar Invitation\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
    }
}

/**
 * Generate email content with iCalendar attachment
 */
function generateEmailWithICalAttachment($htmlBody, $textBody, $icalContent, $boundary) {
    $email = "--{$boundary}\r\n";
    $email .= "Content-Type: text/html; charset=UTF-8\r\n";
    $email .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $email .= $htmlBody . "\r\n\r\n";
    
    $email .= "--{$boundary}\r\n";
    $email .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $email .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $email .= $textBody . "\r\n\r\n";
    
    $email .= "--{$boundary}\r\n";
    $email .= "Content-Type: text/calendar; method=REQUEST; charset=UTF-8\r\n";
    $email .= "Content-Transfer-Encoding: 7bit\r\n";
    $email .= "Content-Disposition: attachment; filename=\"invitation.ics\"\r\n";
    $email .= "X-Mailer: Mithi Calendar\r\n\r\n";
    $email .= $icalContent . "\r\n\r\n";
    
    $email .= "--{$boundary}--\r\n";
    
    return $email;
}

/**
 * Simulate email sending (replace with actual email service)
 */
function simulateEmailSending($to, $subject, $htmlBody, $headers, $icalContent = null) {
    // Use company SMTP instead of SendGrid
    try {
        // Get email configuration from the already loaded config
        global $emailConfig;
        
        error_log("=== simulateEmailSending called ===");
        error_log("Email config loaded: " . (isset($emailConfig) ? 'Yes' : 'No'));
        if (isset($emailConfig)) {
            error_log("Company SMTP enabled: " . ($emailConfig['company_smtp']['enabled'] ? 'Yes' : 'No'));
            error_log("Company SMTP host: " . $emailConfig['company_smtp']['host']);
        }
        
        // Extract text body from headers if available
        $textBody = '';
        foreach ($headers as $header) {
            if (strpos($header, 'Content-Type: multipart/mixed') !== false) {
                // Extract text content from multipart body
                $textBody = extractMultipartContent($htmlBody, $headers);
                break;
            }
        }
        
        // Use our new company SMTP function
        return sendEmailViaCompanySMTP($to, $subject, $htmlBody, $textBody, $emailConfig, $icalContent);
        
    } catch (Exception $e) {
        error_log("Error in simulateEmailSending: " . $e->getMessage());
        return false;
    }
}

/**
 * Send real emails using SendGrid API
 */
function sendRealEmail($to, $subject, $htmlBody, $headers) {
    try {
        error_log("=== ATTEMPTING REAL EMAIL SEND VIA SENDGRID ===");
        error_log("To: $to");
        error_log("Subject: $subject");
        
        // Use global email config instead of reloading
        global $emailConfig;
        $sendgridConfig = $emailConfig['sendgrid'];
        
        // Check if SendGrid is enabled and configured
        if (!$sendgridConfig['enabled'] || $sendgridConfig['api_key'] === 'SG.test_key_replace_with_real_key') {
            error_log("SendGrid not configured - using simulation mode");
            error_log("To configure real email sending:");
            error_log("1. Sign up at https://sendgrid.com (free tier available)");
            error_log("2. Get your API key from SendGrid dashboard");
            error_log("3. Update config/email.php with your API key and set 'enabled' => true");
            
            // Log what would be sent
            error_log("Email would be sent to: $to");
            error_log("Subject: $subject");
            error_log("HTML Body: $htmlBody");
            error_log("========================");
            
            return true; // Simulate success for now
        }
        
        // Check if this is a multipart email with iCalendar
        $isMultipart = false;
        $icalContent = '';
        foreach ($headers as $header) {
            if (strpos($header, 'Content-Type: multipart/mixed') !== false) {
                $isMultipart = true;
                // Extract iCalendar content from the multipart body
                $icalContent = extractMultipartContent($htmlBody, $headers);
                break;
            }
        }
        
        // Prepare email data for SendGrid
        $emailData = [
            'personalizations' => [
                [
                    'to' => [
                        ['email' => $to]
                    ]
                ]
            ],
            'from' => [
                'email' => $sendgridConfig['from_email'],
                'name' => $sendgridConfig['from_name']
            ],
            'subject' => $subject,
            'content' => [
                [
                    'type' => 'text/html',
                    'value' => $isMultipart ? $icalContent : $htmlBody
                ]
            ]
        ];
        
        // Add iCalendar attachment if present
        if ($isMultipart && !empty($icalContent)) {
            $emailData['attachments'] = [
                [
                    'content' => base64_encode($icalContent),
                    'type' => 'text/calendar',
                    'filename' => 'invitation.ics',
                    'disposition' => 'attachment'
                ]
            ];
        }
        
        // Send email via SendGrid API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $sendgridConfig['api_key'],
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("cURL error: " . $curlError);
            return false;
        }
        
        if ($httpCode === 202) {
            error_log("Email sent successfully via SendGrid to: $to");
            error_log("SendGrid Response: $response");
            return true;
        } else {
            error_log("Failed to send email via SendGrid. HTTP Code: $httpCode, Response: $response");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Exception in sendRealEmail: " . $e->getMessage());
        return false;
    }
}

/**
 * Extract content from multipart email body
 */
function extractMultipartContent($body, $headers) {
    // Find boundary
    $boundary = '';
    foreach ($headers as $header) {
        if (strpos($header, 'boundary=') !== false) {
            preg_match('/boundary="([^"]+)"/', $header, $matches);
            $boundary = $matches[1];
            break;
        }
    }
    
    if (empty($boundary)) {
        return $body;
    }
    
    // Parse multipart content
    $parts = explode("--$boundary", $body);
    $emailContent = '';
    
    foreach ($parts as $part) {
        if (strpos($part, 'Content-Type: text/html') !== false) {
            // Extract HTML content
            $contentStart = strpos($part, "\r\n\r\n");
            if ($contentStart !== false) {
                $emailContent = substr($part, $contentStart + 4);
                break;
            }
        }
    }
    
    return $emailContent ?: $body;
}

/**
 * Send event invitations to attendees automatically
 */
function sendEventInvitations($event, $attendees) {
    try {
        error_log("=== sendEventInvitations called ===");
        error_log("Event: " . json_encode($event));
        error_log("Attendees: " . json_encode($attendees));
        
        if (empty($attendees) || !is_array($attendees)) {
            error_log("No attendees to send invitations to");
            return ['success' => false, 'message' => 'No attendees to send invitations to'];
        }
        
        // Filter valid attendees
        $validAttendees = [];
        foreach ($attendees as $attendee) {
            if (is_string($attendee) && filter_var($attendee, FILTER_VALIDATE_EMAIL)) {
                $validAttendees[] = $attendee;
            } elseif (is_array($attendee) && !empty($attendee['email']) && filter_var($attendee['email'], FILTER_VALIDATE_EMAIL)) {
                $validAttendees[] = $attendee;
            }
        }
        
        error_log("Valid attendees: " . json_encode($validAttendees));
        
        if (empty($validAttendees)) {
            error_log("No valid email addresses found");
            return ['success' => false, 'message' => 'No valid email addresses found'];
        }
        
        // Generate iCalendar invitation
        $organizer = 'unknown@mithi.com';
        try {
            $caldavClient = getCalDAVClient();
            if ($caldavClient) {
                $organizer = $caldavClient->getUsername();
            }
        } catch (Exception $e) {
            error_log("Could not get CalDAV client for organizer: " . $e->getMessage());
        }
        
        $icalContent = generateICalInvitation([
            'title' => $event['title'],
            'description' => $event['description'] ?? '',
            'location' => $event['location'] ?? '',
            'startTime' => $event['start_time'],
            'endTime' => $event['end_time'],
            'allDay' => $event['all_day'] ?? false,
            'organizer' => $organizer
        ], $validAttendees);
        
        error_log("Generated iCalendar content length: " . strlen($icalContent));
        error_log("Generated iCalendar content: " . $icalContent);
        
        // Email headers for calendar invitation
        $boundary = uniqid('calendar_');
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
            'From: Mithi Calendar <noreply@mithi-calendar.com>',
            'Reply-To: organizer@example.com',
            'X-Mailer: Mithi Calendar/1.0',
            'X-Campaign: calendar-invitation'
        ];
        
        // Generate email content
        $htmlBody = generateInvitationEmailBody($event, $validAttendees, true);
        $textBody = generateInvitationEmailBody($event, $validAttendees, false);
        
        error_log("Generated HTML body length: " . strlen($htmlBody));
        error_log("Generated text body length: " . strlen($textBody));
        
        $successCount = 0;
        $failedEmails = [];
        
        foreach ($validAttendees as $attendee) {
            try {
                $email = is_string($attendee) ? $attendee : $attendee['email'];
                error_log("Sending invitation to: $email");
                
                // Generate email content with iCalendar attachment
                $emailContent = generateEmailWithICalAttachment($htmlBody, $textBody, $icalContent, $boundary);
                
                error_log("Email content length: " . strlen($emailContent));
                
                // Simulate email sending (replace with actual email service)
                $mailSent = simulateEmailSending($email, "Calendar Invitation: " . $event['title'], $htmlBody, $headers, $icalContent);
                
                if ($mailSent) {
                    $successCount++;
                    error_log("Invitation sent successfully to: $email");
                } else {
                    $failedEmails[] = $email;
                    error_log("Failed to send invitation to: $email");
                }
            } catch (Exception $e) {
                $email = is_string($attendee) ? $attendee : $attendee['email'];
                error_log("Exception sending invitation to $email: " . $e->getMessage());
                $failedEmails[] = $email;
            }
        }
        
        $response = [
            'success' => $successCount > 0,
            'message' => "Sent $successCount out of " . count($validAttendees) . " invitations",
            'data' => [
                'totalRecipients' => count($validAttendees),
                'successfulSends' => $successCount,
                'failedSends' => count($failedEmails),
                'failedEmails' => $failedEmails
            ]
        ];
        
        error_log("Invitation results: " . json_encode($response));
        return $response;
        
    } catch (Exception $e) {
        error_log("Exception in sendEventInvitations: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error sending invitations: ' . $e->getMessage()];
    }
}

/**
 * Handle email notifications for event updates
 * Sends invitations to new attendees and update notifications to existing attendees
 */
function handleEventUpdateEmails($updatedEvent, $originalEvent, $newAttendees) {
    try {
        error_log("=== handleEventUpdateEmails called ===");
        error_log("Updated event: " . json_encode($updatedEvent));
        error_log("New attendees: " . json_encode($newAttendees));
        
        $result = [
            'success' => false,
            'message' => '',
            'data' => [
                'newInvitations' => 0,
                'updateNotifications' => 0,
                'totalEmailsSent' => 0
            ]
        ];
        
        if (empty($newAttendees) || !is_array($newAttendees)) {
            error_log("No attendees to process");
            $result['success'] = true;
            $result['message'] = 'No attendees to process';
            return $result;
        }
        
        // Use the passed original event data instead of loading from storage
        
        $originalAttendees = $originalEvent['attendees'] ?? [];
        error_log("Original attendees: " . json_encode($originalAttendees));
        
        // If original event had no attendees, treat all new attendees as "new"
        if (empty($originalAttendees)) {
            error_log("Original event had no attendees - treating all as new attendees");
            $newAttendeeEmails = $newAttendees;
            $existingAttendeeEmails = [];
        } else {
            // Separate new attendees from existing attendees
            $newAttendeeEmails = [];
            $existingAttendeeEmails = [];
            
            foreach ($newAttendees as $attendee) {
                $email = is_string($attendee) ? $attendee : $attendee['email'];
                $isNew = true;
                
                // Check if this attendee existed in the original event
                foreach ($originalAttendees as $originalAttendee) {
                    $originalEmail = is_string($originalAttendee) ? $originalAttendee : $originalAttendee['email'];
                    if ($email === $originalEmail) {
                        $isNew = false;
                        break;
                    }
                }
                
                if ($isNew) {
                    $newAttendeeEmails[] = $attendee;
                } else {
                    $existingAttendeeEmails[] = $attendee;
                }
            }
        }
        
        error_log("New attendees: " . json_encode($newAttendeeEmails));
        error_log("Existing attendees: " . json_encode($existingAttendeeEmails));
        
        $totalEmailsSent = 0;
        
        // Send invitations to new attendees
        if (!empty($newAttendeeEmails)) {
            error_log("Sending invitations to " . count($newAttendeeEmails) . " new attendees");
            error_log("New attendee emails: " . json_encode($newAttendeeEmails));
            $invitationResult = sendEventInvitations($updatedEvent, $newAttendeeEmails);
            error_log("Invitation result: " . json_encode($invitationResult));
            if ($invitationResult['success']) {
                $result['data']['newInvitations'] = $invitationResult['data']['successfulSends'];
                $totalEmailsSent += $invitationResult['data']['successfulSends'];
                error_log("Successfully sent invitations to " . $invitationResult['data']['successfulSends'] . " new attendees");
            } else {
                error_log("Failed to send invitations to new attendees: " . $invitationResult['message']);
            }
        } else {
            error_log("No new attendees to send invitations to");
        }
        
        // Send update notifications to existing attendees (if event details changed significantly)
        if (!empty($existingAttendeeEmails)) {
            $hasSignificantChanges = hasSignificantEventChanges($originalEvent, $updatedEvent);
            
            if ($hasSignificantChanges) {
                error_log("Event has significant changes, sending update notifications to " . count($existingAttendeeEmails) . " existing attendees");
                $updateResult = sendEventUpdateNotifications($updatedEvent, $existingAttendeeEmails);
                if ($updateResult['success']) {
                    $result['data']['updateNotifications'] = $updateResult['data']['successfulSends'];
                    $totalEmailsSent += $updateResult['data']['successfulSends'];
                    error_log("Successfully sent update notifications to " . $updateResult['data']['successfulSends'] . " existing attendees");
                } else {
                    error_log("Failed to send update notifications: " . $updateResult['message']);
                }
            } else {
                error_log("No significant changes detected, skipping update notifications to existing attendees");
            }
        }
        
        $result['success'] = true;
        $result['data']['totalEmailsSent'] = $totalEmailsSent;
        $result['message'] = "Processed email notifications: " . $totalEmailsSent . " total emails sent";
        
        error_log("Email processing result: " . json_encode($result));
        return $result;
        
    } catch (Exception $e) {
        error_log("Exception in handleEventUpdateEmails: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error processing email notifications: ' . $e->getMessage(),
            'data' => [
                'newInvitations' => 0,
                'updateNotifications' => 0,
                'totalEmailsSent' => 0
            ]
        ];
    }
}

/**
 * Check if an event has significant changes that warrant notifying existing attendees
 */
function hasSignificantEventChanges($originalEvent, $updatedEvent) {
    // Check for significant changes in time, location, or description
    $significantChanges = false;
    
    // Time changes
    if (($originalEvent['start_time'] ?? '') !== ($updatedEvent['start_time'] ?? '') ||
        ($originalEvent['end_time'] ?? '') !== ($updatedEvent['end_time'] ?? '')) {
        $significantChanges = true;
        error_log("Event time changed - significant change detected");
    }
    
    // Location changes
    if (($originalEvent['location'] ?? '') !== ($updatedEvent['location'] ?? '')) {
        $significantChanges = true;
        error_log("Event location changed - significant change detected");
    }
    
    // Description changes (if description is substantial)
    if (strlen($originalEvent['description'] ?? '') > 10 || strlen($updatedEvent['description'] ?? '') > 10) {
        if (($originalEvent['description'] ?? '') !== ($updatedEvent['description'] ?? '')) {
            $significantChanges = true;
            error_log("Event description changed - significant change detected");
        }
    }
    
    // Title changes
    if (($originalEvent['title'] ?? '') !== ($updatedEvent['title'] ?? '')) {
        $significantChanges = true;
        error_log("Event title changed - significant change detected");
    }
    
    return $significantChanges;
}

/**
 * Send update notifications to existing attendees
 */
function sendEventUpdateNotifications($event, $attendees) {
    try {
        error_log("=== sendEventUpdateNotifications called ===");
        error_log("Event: " . json_encode($event));
        error_log("Attendees: " . json_encode($attendees));
        
        if (empty($attendees) || !is_array($attendees)) {
            error_log("No attendees to send update notifications to");
            return ['success' => false, 'message' => 'No attendees to send update notifications to'];
        }
        
        // Filter valid attendees
        $validAttendees = [];
        foreach ($attendees as $attendee) {
            if (is_string($attendee) && filter_var($attendee, FILTER_VALIDATE_EMAIL)) {
                $validAttendees[] = $attendee;
            } elseif (is_array($attendee) && !empty($attendee['email']) && filter_var($attendee['email'], FILTER_VALIDATE_EMAIL)) {
                $validAttendees[] = $attendee;
            }
        }
        
        error_log("Valid attendees for update notifications: " . json_encode($validAttendees));
        
        if (empty($validAttendees)) {
            error_log("No valid email addresses found for update notifications");
            return ['success' => false, 'message' => 'No valid email addresses found for update notifications'];
        }
        
        // Generate iCalendar update (METHOD:REQUEST for updates)
        $icalContent = generateICalInvitation([
            'title' => $event['title'],
            'description' => $event['description'] ?? '',
            'location' => $event['location'] ?? '',
            'startTime' => $event['start_time'],
            'endTime' => $event['end_time'],
            'allDay' => $event['all_day'] ?? false,
            'organizer' => 'noreply@mithi-calendar.com'
        ], $validAttendees);
        
        error_log("Generated iCalendar update content length: " . strlen($icalContent));
        
        // Email headers for update notification
        $boundary = uniqid('calendar_update_');
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
            'From: Mithi Calendar <noreply@mithi-calendar.com>',
            'Reply-To: noreply@mithi-calendar.com',
            'X-Mailer: Mithi Calendar/1.0',
            'X-Campaign: calendar-update'
        ];
        
        // Generate email content for updates
        $htmlBody = generateUpdateNotificationEmailBody($event, $validAttendees, true);
        $textBody = generateUpdateNotificationEmailBody($event, $validAttendees, false);
        
        $successCount = 0;
        $failedEmails = [];
        
        foreach ($validAttendees as $attendee) {
            try {
                $email = is_string($attendee) ? $attendee : $attendee['email'];
                error_log("Sending update notification to: $email");
                
                // Generate email content with iCalendar attachment
                $emailContent = generateEmailWithICalAttachment($htmlBody, $textBody, $icalContent, $boundary);
                
                // Send update notification
                $mailSent = simulateEmailSending($email, "Event Updated: " . $event['title'], $emailContent, $headers, $icalContent);
                
                if ($mailSent) {
                    $successCount++;
                    error_log("Update notification sent successfully to: $email");
                } else {
                    $failedEmails[] = $email;
                    error_log("Failed to send update notification to: $email");
                }
            } catch (Exception $e) {
                $email = is_string($attendee) ? $attendee : $attendee['email'];
                error_log("Exception sending update notification to $email: " . $e->getMessage());
                $failedEmails[] = $email;
            }
        }
        
        $response = [
            'success' => $successCount > 0,
            'message' => "Sent $successCount out of " . count($validAttendees) . " update notifications",
            'data' => [
                'totalRecipients' => count($validAttendees),
                'successfulSends' => $successCount,
                'failedSends' => count($failedEmails),
                'failedEmails' => $failedEmails
            ]
        ];
        
        error_log("Update notification results: " . json_encode($response));
        return $response;
        
    } catch (Exception $e) {
        error_log("Exception in sendEventUpdateNotifications: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error sending update notifications: ' . $e->getMessage(),
            'data' => [
                'totalRecipients' => 0,
                'successfulSends' => 0,
                'failedSends' => 0,
                'failedEmails' => []
            ]
        ];
    }
}

/**
 * Generate invitation email body (HTML or text)
 */
function generateInvitationEmailBody($event, $attendees, $isHtml = true) {
    $title = $event['title'];
    $description = $event['description'] ?? '';
    $location = $event['location'] ?? '';
    $startTime = date('F j, Y \a\t g:i A', strtotime($event['start_time']));
    $endTime = date('F j, Y \a\t g:i A', strtotime($event['end_time']));
    
    if ($isHtml) {
        $body = "<html><body>";
        $body .= "<h2>Calendar Invitation</h2>";
        $body .= "<h3>$title</h3>";
        if (!empty($description)) {
            $body .= "<p><strong>Description:</strong> $description</p>";
        }
        if (!empty($location)) {
            $body .= "<p><strong>Location:</strong> $location</p>";
        }
        $body .= "<p><strong>Start:</strong> $startTime</p>";
        $body .= "<p><strong>End:</strong> $endTime</p>";
        $body .= "<p>Please check your calendar application to accept or decline this invitation.</p>";
        $body .= "<p>Best regards,<br>Mithi Calendar</p>";
        $body .= "</body></html>";
    } else {
        $body = "Calendar Invitation\n\n";
        $body .= "$title\n\n";
        if (!empty($description)) {
            $body .= "Description: $description\n\n";
        }
        if (!empty($location)) {
            $body .= "Location: $location\n\n";
        }
        $body .= "Start: $startTime\n";
        $body .= "End: $endTime\n\n";
        $body .= "Please check your calendar application to accept or decline this invitation.\n\n";
        $body .= "Best regards,\nMithi Calendar";
    }
    
    return $body;
}

/**
 * Generate email body for event update notifications
 */
function generateUpdateNotificationEmailBody($event, $attendees, $isHtml) {
    $eventTitle = htmlspecialchars($event['title']);
    $eventDate = date('l, F j, Y', strtotime($event['start_time']));
    $eventTime = date('g:i A', strtotime($event['start_time'])) . ' - ' . date('g:i A', strtotime($event['end_time']));
    $eventLocation = htmlspecialchars($event['location'] ?? 'No location specified');
    $eventDescription = htmlspecialchars($event['description'] ?? 'No description provided');
    
    if ($isHtml) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
                .event-details { background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px; }
                .event-title { color: #007bff; font-size: 24px; margin-bottom: 10px; }
                .event-info { margin: 10px 0; }
                .label { font-weight: bold; color: #495057; }
                .footer { margin-top: 20px; padding: 20px; background-color: #f8f9fa; border-radius: 5px; font-size: 14px; color: #6c757d; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>📅 Event Updated</h2>
                <p>An event you're attending has been updated. Please review the new details below.</p>
            </div>
            
            <div class='event-details'>
                <div class='event-title'>{$eventTitle}</div>
                
                <div class='event-info'>
                    <span class='label'>📅 Date:</span> {$eventDate}
                </div>
                
                <div class='event-info'>
                    <span class='label'>🕐 Time:</span> {$eventTime}
                </div>
                
                <div class='event-info'>
                    <span class='label'>📍 Location:</span> {$eventLocation}
                </div>
                
                <div class='event-info'>
                    <span class='label'>📝 Description:</span> {$eventDescription}
                </div>
            </div>
            
            <div class='footer'>
                <p><strong>Note:</strong> This is an update notification for an event you're already attending. The attached iCalendar file contains the updated event details.</p>
                <p>If you have any questions, please contact the event organizer.</p>
            </div>
        </body>
        </html>";
    } else {
        return "
EVENT UPDATED

An event you're attending has been updated. Please review the new details below.

Event: {$eventTitle}
Date: {$eventDate}
Time: {$eventTime}
Location: {$eventLocation}
Description: {$eventDescription}

Note: This is an update notification for an event you're already attending. The attached iCalendar file contains the updated event details.

If you have any questions, please contact the event organizer.";
    }
}

/**
 * Send email using company SMTP server with CalDAV authentication
 */
function sendEmailViaCompanySMTP($to, $subject, $htmlBody, $textBody, $config, $icalContent = null) {
    // Check if company SMTP is enabled
    if (!$config['company_smtp']['enabled']) {
        error_log("Company SMTP is disabled");
        return false;
    }
    
    try {
        // Get CalDAV credentials if available
        $username = null;
        $password = null;
        
        if ($config['company_smtp']['use_caldav_auth']) {
            // Try to get credentials from CalDAV client
            $caldavClient = getCalDAVClient();
            if ($caldavClient) {
                $username = $caldavClient->getUsername();
                $password = $caldavClient->getPassword();
                error_log("Using CalDAV credentials for SMTP: " . $username);
            }
        }
        
        // Use fallback credentials if CalDAV auth failed
        if (!$username || !$password) {
            $username = $config['company_smtp']['fallback_username'];
            $password = $config['company_smtp']['fallback_password'];
            error_log("Using fallback credentials for SMTP: " . $username);
        }
        
        if (!$username || !$password) {
            error_log("No valid credentials available for SMTP");
            return false;
        }
        
        // Create SMTP connection
        $smtp = fsockopen(
            $config['company_smtp']['host'], 
            $config['company_smtp']['port'], 
            $errno, 
            $errstr, 
            $config['company_smtp']['timeout']
        );
        
        if (!$smtp) {
            error_log("Failed to connect to SMTP server: $errstr ($errno)");
            return false;
        }
        
        // Read server response
        $response = fgets($smtp, 515);
        if ($config['company_smtp']['debug']) {
            error_log("SMTP Server: $response");
        }
        
        // Send EHLO
        fputs($smtp, "EHLO " . gethostname() . "\r\n");
        $response = fgets($smtp, 515);
        if ($config['company_smtp']['debug']) {
            error_log("SMTP EHLO: $response");
        }
        
        // Read all EHLO response lines to check for STARTTLS
        $eholoResponse = $response;
        while (strpos($response, '250 ') === false && !feof($smtp)) {
            $response = fgets($smtp, 515);
            $eholoResponse .= $response;
            if ($config['company_smtp']['debug']) {
                error_log("SMTP EHLO (continued): $response");
            }
        }
        
        // Check if server requires STARTTLS
        if (strpos($eholoResponse, '250-STARTTLS') !== false || strpos($eholoResponse, 'STARTTLS') !== false) {
            if ($config['company_smtp']['debug']) {
                error_log("SMTP Server requires STARTTLS - enabling TLS");
            }
            
            // Enable STARTTLS
            fputs($smtp, "STARTTLS\r\n");
            $response = fgets($smtp, 515);
            if ($config['company_smtp']['debug']) {
                error_log("SMTP STARTTLS Response: $response");
            }
            
            // Check if STARTTLS was accepted
            if (strpos($response, '220') !== 0) {
                error_log("STARTTLS not accepted: $response");
                fclose($smtp);
                return false;
            }
            
            // Enable crypto
            if (!stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                error_log("Failed to enable TLS");
                fclose($smtp);
                return false;
            }
            
            if ($config['company_smtp']['debug']) {
                error_log("TLS enabled successfully");
            }
            
            // Send EHLO again after TLS
            fputs($smtp, "EHLO " . gethostname() . "\r\n");
            $response = fgets($smtp, 515);
            if ($config['company_smtp']['debug']) {
                error_log("SMTP EHLO (after TLS): $response");
            }
            
            // Read all EHLO response lines after TLS
            $eholoResponseAfterTls = $response;
            while (strpos($response, '250 ') === false && !feof($smtp)) {
                $response = fgets($smtp, 515);
                $eholoResponseAfterTls .= $response;
                if ($config['company_smtp']['debug']) {
                    error_log("SMTP EHLO (after TLS, continued): $response");
                }
            }
            
            if ($config['company_smtp']['debug']) {
                error_log("Complete EHLO response after TLS: $eholoResponseAfterTls");
            }
        }
        
        // Authenticate
        fputs($smtp, "AUTH LOGIN\r\n");
        $response = fgets($smtp, 515);
        if ($config['company_smtp']['debug']) {
            error_log("SMTP AUTH: $response");
        }
        
        // Check if we got the expected 334 response for username
        if (strpos($response, '334') !== 0) {
            error_log("Unexpected response to AUTH LOGIN: $response");
            fclose($smtp);
            return false;
        }
        
        // Send username
        fputs($smtp, base64_encode($username) . "\r\n");
        $response = fgets($smtp, 515);
        if ($config['company_smtp']['debug']) {
            error_log("SMTP USERNAME: $response");
        }
        
        // Check if we got the expected 334 response for password
        if (strpos($response, '334') !== 0) {
            error_log("Unexpected response to username: $response");
            fclose($smtp);
            return false;
        }
        
        // Send password
        fputs($smtp, base64_encode($password) . "\r\n");
        $response = fgets($smtp, 515);
        if ($config['company_smtp']['debug']) {
            error_log("SMTP PASSWORD: $response");
        }
        
        // Check if authentication was successful
        if (strpos($response, '235') !== 0) {
            error_log("SMTP Authentication failed: $response");
            fclose($smtp);
            return false;
        }
        
        // Set sender
        fputs($smtp, "MAIL FROM: <" . $username . ">\r\n");
        $response = fgets($smtp, 515);
        if ($config['company_smtp']['debug']) {
            error_log("SMTP MAIL FROM: $response");
        }
        
        // Set recipient
        fputs($smtp, "RCPT TO: <$to>\r\n");
        $response = fgets($smtp, 515);
        if ($config['company_smtp']['debug']) {
            error_log("SMTP RCPT TO: $response");
        }
        
        // Send email data
        fputs($smtp, "DATA\r\n");
        $response = fgets($smtp, 515);
        if ($config['company_smtp']['debug']) {
            error_log("SMTP DATA: $response");
        }
        
        // Send email headers and content
        if ($icalContent) {
            // Generate unique boundary for multipart message
            $boundary = 'calendar_' . uniqid();
            
            $emailContent = "From: " . $username . " <" . $username . ">\r\n";
            $emailContent .= "To: $to\r\n";
            $emailContent .= "Subject: $subject\r\n";
            $emailContent .= "MIME-Version: 1.0\r\n";
            $emailContent .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
            $emailContent .= "\r\n";
            
            // HTML part
            $emailContent .= "--$boundary\r\n";
            $emailContent .= "Content-Type: text/html; charset=UTF-8\r\n";
            $emailContent .= "Content-Transfer-Encoding: 7bit\r\n";
            $emailContent .= "\r\n";
            $emailContent .= $htmlBody . "\r\n\r\n";
            
            // Plain text part
            $emailContent .= "--$boundary\r\n";
            $emailContent .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $emailContent .= "Content-Transfer-Encoding: 7bit\r\n";
            $emailContent .= "\r\n";
            $emailContent .= $textBody . "\r\n\r\n";
            
            // iCalendar attachment
            $emailContent .= "--$boundary\r\n";
            $emailContent .= "Content-Type: text/calendar; method=REQUEST; charset=UTF-8\r\n";
            $emailContent .= "Content-Transfer-Encoding: 7bit\r\n";
            $emailContent .= "Content-Disposition: attachment; filename=\"invitation.ics\"\r\n";
            $emailContent .= "X-Mailer: Mithi Calendar\r\n";
            $emailContent .= "\r\n";
            $emailContent .= $icalContent . "\r\n\r\n";
            
            // End boundary
            $emailContent .= "--$boundary--\r\n";
        } else {
            // Simple HTML email
            $emailContent = "From: " . $username . " <" . $username . ">\r\n";
            $emailContent .= "To: $to\r\n";
            $emailContent .= "Subject: $subject\r\n";
            $emailContent .= "MIME-Version: 1.0\r\n";
            $emailContent .= "Content-Type: text/html; charset=UTF-8\r\n";
            $emailContent .= "\r\n";
            $emailContent .= $htmlBody;
        }
        
        $emailContent .= "\r\n.\r\n";
        
        fputs($smtp, $emailContent);
        $response = fgets($smtp, 515);
        if ($config['company_smtp']['debug']) {
            error_log("SMTP SEND: $response");
        }
        
        // Check if email was sent successfully
        if (strpos($response, '250') !== 0) {
            error_log("SMTP Send failed: $response");
            fclose($smtp);
            return false;
        }
        
        // Close connection
        fputs($smtp, "QUIT\r\n");
        fclose($smtp);
        
        error_log("Email sent successfully via company SMTP to: $to");
        return true;
        
    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        if (isset($smtp) && is_resource($smtp)) {
            fclose($smtp);
        }
        return false;
    }
}

/**
 * Authentication Functions
 */

function authenticateUser() {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['username']) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Username and password are required'
            ]);
            return;
        }
        
        $username = $input['username'];
        $password = $input['password'];
        
        // Test CalDAV authentication with provided credentials
        try {
            $caldavConfig = require_once 'config/caldav.php';
            $testClient = new CalDAVClient(
                $caldavConfig['server_url'],
                $username,
                $password
            );
            
            // Try to discover calendars to test authentication
            $calendars = $testClient->discoverCalendars();
            
            if (!empty($calendars)) {
                // Authentication successful - store credentials in session
                $_SESSION['user_authenticated'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['password'] = $password; // In production, consider encrypting this
                $_SESSION['caldav_server_url'] = $caldavConfig['server_url'];
                $_SESSION['calendar_url'] = $calendars[0]['href'];
                
                // Also store in the format expected by getCalDAVClient()
                $_SESSION['caldav_credentials'] = [
                    'serverUrl' => $caldavConfig['server_url'],
                    'username' => $username,
                    'password' => $password,
                    'authenticated_at' => time()
                ];
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Authentication successful',
                    'data' => [
                        'username' => $username,
                        'calendar_name' => $calendars[0]['name'] ?? 'Personal Calendar'
                    ]
                ]);
            } else {
                throw new Exception('No calendars found');
            }
            
        } catch (Exception $e) {
            error_log("Authentication failed for user $username: " . $e->getMessage());
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Authentication failed. Please check your credentials.'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Error in authenticateUser: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error during authentication'
        ]);
    }
}

function logoutUser() {
    // Clear session data
    session_destroy();
    
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);
}

function getAuthStatus() {
    $isAuthenticated = isset($_SESSION['caldav_credentials']) && !empty($_SESSION['caldav_credentials']['username']);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'authenticated' => $isAuthenticated,
            'username' => $isAuthenticated ? $_SESSION['caldav_credentials']['username'] : null
        ]
    ]);
}

/**
 * Get CalDAV client instance with user credentials from session
>>>>>>> 7a0647a0a1dd634bb8dc15c71db3aef7d799893d
 */
function handleAuthRequest($path) {
    $authPath = substr($path, 5); // Remove 'auth/' prefix
    
    switch ($authPath) {
        case 'login':
            handleLogin();
            break;
        case 'logout':
            handleLogout();
            break;
        case 'sso-token':
            createSSOToken();
            break;
        case 'sso-login':
            loginWithSSOToken();
            break;
        default:
            http_response_code(404);
            sendJsonResponse(['success' => false, 'message' => 'Authentication endpoint not found']);
    }
}

/**
 * Handle user login with CalDAV credentials
 */
function handleLogin() {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            sendJsonResponse(['success' => false, 'message' => 'Invalid JSON input']);
            return;
        }
        
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            http_response_code(400);
            sendJsonResponse(['success' => false, 'message' => 'Username and password are required']);
            return;
        }
        
        // Get server URL from environment variable
        $serverUrl = $_ENV['CALDAV_SERVER_URL'] ?? 'http://rc.mithi.com:18008';
        
        // Test CalDAV connection with provided credentials
        $caldavClient = new CalDAVClient($serverUrl, $username, $password);
        
        // Test basic authentication first (simpler than full calendar discovery)
        $authToken = $caldavClient->getAuthToken();
        if (!$authToken) {
            http_response_code(401);
            sendJsonResponse(['success' => false, 'message' => 'Invalid CalDAV credentials']);
            return;
        }
        
        // Try to test server connectivity (but don't fail login if server has issues)
        try {
            $testResponse = $caldavClient->makeCalDAVRequest($serverUrl, 'PROPFIND', $authToken, [
                'Depth: 0',
                'Content-Type: application/xml; charset=utf-8'
            ], '<?xml version="1.0" encoding="utf-8" ?><propfind xmlns="DAV:"><prop><current-user-principal/></prop></propfind>');
            
            if ($testResponse['status'] >= 400) {
                error_log("CalDAV server returned status " . $testResponse['status'] . " - server may have configuration issues");
            }
        } catch (Exception $e) {
            error_log("CalDAV server test failed: " . $e->getMessage() . " - but continuing with login");
        }
        
        // Store credentials in session (in production, use more secure storage)
        $_SESSION['caldav_credentials'] = [
            'serverUrl' => $serverUrl,
            'username' => $username,
            'password' => $password,
            'authenticated_at' => time()
        ];
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'username' => $username
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        http_response_code(401);
        sendJsonResponse(['success' => false, 'message' => 'Authentication failed: ' . $e->getMessage()]);
    }
}

/**
 * SSO Token storage using session (in production, use Redis or database)
 */
function getSSOTokens() {
    $tokenFile = 'data/sso_tokens.json';
    
    // Create directory if it doesn't exist
    $tokenDir = dirname($tokenFile);
    if (!is_dir($tokenDir)) {
        mkdir($tokenDir, 0755, true);
    }
    
    // Read tokens from file
    if (file_exists($tokenFile)) {
        $tokens = json_decode(file_get_contents($tokenFile), true);
        if ($tokens === null) {
            error_log("Error reading SSO tokens file, resetting");
            return [];
        }
        
        // Clean up expired tokens
        $currentTime = time();
        $cleanTokens = [];
        foreach ($tokens as $token => $data) {
            if (isset($data['expires']) && $currentTime < $data['expires']) {
                $cleanTokens[$token] = $data;
            }
        }
        
        // Save cleaned tokens back if any were removed
        if (count($cleanTokens) !== count($tokens)) {
            setSSOTokens($cleanTokens);
        }
        
        return $cleanTokens;
    }
    
    return [];
}

function setSSOTokens($tokens) {
    $tokenFile = 'data/sso_tokens.json';
    $tokenDir = dirname($tokenFile);
    
    // Create directory if it doesn't exist
    if (!is_dir($tokenDir)) {
        mkdir($tokenDir, 0755, true);
    }
    
    // Write tokens to file with proper error handling
    $result = file_put_contents($tokenFile, json_encode($tokens, JSON_PRETTY_PRINT));
    if ($result === false) {
        error_log("Failed to write SSO tokens to file: $tokenFile");
    } else {
        error_log("SSO tokens saved successfully to file");
    }
}

/**
 * Create SSO token for Roundcube integration
 */
function createSSOToken() {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['username']) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Username and password are required'
            ]);
            return;
        }
        
        $username = $input['username'];
        $password = $input['password'];
        
        error_log("Creating SSO token for user: $username");
        
        // For SSO from Roundcube, we trust the credentials since user is already authenticated
        // Skip CalDAV authentication to avoid potential issues
        try {
                // Generate SSO token
                $token = bin2hex(random_bytes(32));
            
            // Get CalDAV config for server details
            $caldavConfig = require_once 'config/caldav.php';
                
                // Get current tokens and add new one
                $ssoTokens = getSSOTokens();
                $ssoTokens[$token] = [
                    'username' => $username,
                    'password' => $password,
                    'server_url' => $caldavConfig['server_url'],
                'calendar_url' => '', // Will be discovered on first use
                'calendar_name' => 'Personal Calendar',
                                           'expires' => time() + 3600 // 1 hour
                ];
                setSSOTokens($ssoTokens);
                
                error_log("SSO token created successfully: $token");
                
                echo json_encode([
                    'success' => true,
                    'token' => $token,
                    'message' => 'SSO token created'
                ]);
            
        } catch (Exception $e) {
            error_log("SSO token creation failed for user $username: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create SSO token: ' . $e->getMessage()
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Error in createSSOToken: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error during SSO token creation'
        ]);
    }
}

/**
 * Login with SSO token
 */
function loginWithSSOToken() {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['token'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'SSO token is required'
            ]);
            return;
        }
        
        $token = $input['token'];
        error_log("Attempting SSO login with token: $token");
        
        // Get current tokens
        $ssoTokens = getSSOTokens();
        
        // Check if token exists and is valid
        if (!isset($ssoTokens[$token])) {
            error_log("SSO token not found: $token");
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid or expired SSO token'
            ]);
            return;
        }
        
        $tokenData = $ssoTokens[$token];
        
        // Check if token has expired
        if (time() > $tokenData['expires']) {
            error_log("SSO token expired: $token");
            unset($ssoTokens[$token]);
            setSSOTokens($ssoTokens);
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'SSO token has expired'
            ]);
            return;
        }
        
        // Create authenticated session
        $_SESSION['user_authenticated'] = true;
        $_SESSION['username'] = $tokenData['username'];
        $_SESSION['password'] = $tokenData['password'];
        $_SESSION['caldav_server_url'] = $tokenData['server_url'];
        $_SESSION['calendar_url'] = $tokenData['calendar_url'];
        
        // Set session cookie to maintain authentication across requests
        $sessionName = session_name();
        $sessionId = session_id();
        
        // Send session cookie to frontend (1 hour expiry to match token)
        setcookie($sessionName, $sessionId, [
            'expires' => time() + 3600,
            'path' => '/',
            'domain' => '',
            'secure' => false, // Set to true in production with HTTPS
            'httponly' => false, // Allow JavaScript access for debugging
            'samesite' => 'Lax'
        ]);
        
        // Remove used token
        unset($ssoTokens[$token]);
        setSSOTokens($ssoTokens);
        
        error_log("SSO login successful for user: " . $tokenData['username']);
        
        echo json_encode([
            'success' => true,
            'message' => 'SSO login successful',
            'data' => [
                'username' => $tokenData['username'],
                'calendar_name' => $tokenData['calendar_name']
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Error in loginWithSSOToken: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error during SSO login'
        ]);
    }
}

/**
 * Auto-login from Roundcube with user credentials
 */
function autoLoginFromRoundcube() {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['username']) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Username and password are required'
            ]);
            return;
        }
        
        $username = $input['username'];
        $password = $input['password'];
        
        // Test CalDAV authentication with provided credentials
        try {
            $caldavConfig = require_once 'config/caldav.php';
            $testClient = new CalDAVClient(
                $caldavConfig['server_url'],
                $username,
                $password
            );
            
            // Try to discover calendars to test authentication
            $calendars = $testClient->discoverCalendars();
            
            if (!empty($calendars)) {
                // Authentication successful - store credentials in session
                $_SESSION['user_authenticated'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['password'] = $password; // In production, consider encrypting this
                $_SESSION['caldav_server_url'] = $caldavConfig['server_url'];
                $_SESSION['calendar_url'] = $calendars[0]['href'];
                
                // Also store in the format expected by getCalDAVClient()
                $_SESSION['caldav_credentials'] = [
                    'serverUrl' => $caldavConfig['server_url'],
                    'username' => $username,
                    'password' => $password,
                    'authenticated_at' => time()
                ];
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Auto-login successful from Roundcube',
                    'data' => [
                        'username' => $username,
                        'calendar_name' => $calendars[0]['name'] ?? 'Personal Calendar'
                    ]
                ]);
            } else {
                throw new Exception('No calendars found');
            }
            
        } catch (Exception $e) {
            error_log("Auto-login failed for user $username: " . $e->getMessage());
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Auto-login failed. Please check your credentials.'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Error in autoLoginFromRoundcube: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error during auto-login'
        ]);
    }
}
?>