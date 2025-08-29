<?php
// Enable CORS for cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400'); // 24 hours

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session for user event storage
session_start();

// Disable error output to prevent JSON corruption
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

// Load environment variables
require_once 'config/database.php';

// Load email configuration with error handling
try {
    error_log("=== Loading email configuration ===");
    error_log("Current working directory: " . getcwd());
    error_log("Config file path: " . __DIR__ . '/config/email.php');
    error_log("File exists: " . (file_exists('config/email.php') ? 'Yes' : 'No'));
    error_log("File readable: " . (is_readable('config/email.php') ? 'Yes' : 'No'));
    
    $emailConfig = require_once 'config/email.php';
    error_log("Email config loaded, type: " . gettype($emailConfig));
    if (is_array($emailConfig)) {
        error_log("Email config is array with keys: " . implode(', ', array_keys($emailConfig)));
        if (isset($emailConfig['company_smtp'])) {
            error_log("Company SMTP enabled: " . ($emailConfig['company_smtp']['enabled'] ? 'Yes' : 'No'));
        }
    }
    
    if (!$emailConfig) {
        error_log("ERROR: Failed to load email configuration - config returned false");
        $emailConfig = []; // Set empty config to prevent errors
    }
    error_log("Email configuration loaded successfully");
} catch (Exception $e) {
    error_log("ERROR: Failed to load email configuration: " . $e->getMessage());
    $emailConfig = []; // Set empty config to prevent errors
}

require_once 'classes/CalDAVClient.php';
// OAuth2Client removed - using Basic Auth only

// Get the request path
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

// Debug logging
error_log("=== Request Debug ===");
error_log("REQUEST_URI: " . $request_uri);
error_log("Parsed path: " . $path);
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);

// Remove 'backend' from path if present
if (strpos($path, 'backend/') === 0) {
    $path = substr($path, 8);
    error_log("Path after removing 'backend': " . $path);
}

// Route the request
try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGetRequest($path);
            break;
        case 'POST':
            handlePostRequest($path);
            break;
        case 'PUT':
            handlePutRequest($path);
            break;
        case 'DELETE':
            handleDeleteRequest($path);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("ERROR in main request handler: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
} catch (Error $e) {
    error_log("FATAL ERROR in main request handler: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Fatal error: ' . $e->getMessage()]);
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
        case 'calendars':
            getCalendars();
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
        // OAuth endpoints removed - using Basic Auth only
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
    if (preg_match('/^calendars\/(\d+)$/', $path, $matches)) {
        updateCalendar($matches[1]);
    } elseif (preg_match('/^events\/([a-zA-Z0-9_-]+)$/', $path, $matches)) {
        updateEvent($matches[1]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
}

function handleDeleteRequest($path) {
    error_log("=== handleDeleteRequest Debug ===");
    error_log("Path received: " . $path);
    
    if (preg_match('/^calendars\/(\d+)$/', $path, $matches)) {
        error_log("Calendar pattern matched: " . $matches[1]);
        deleteCalendar($matches[1]);
    } elseif (preg_match('/^events\/([a-zA-Z0-9_-]+)$/', $path, $matches)) {
        error_log("Events pattern matched: " . $matches[1]);
        deleteEvent($matches[1]);
    } else {
        error_log("No pattern matched. Path: " . $path);
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
}

// Mock data functions (replace with database calls later)
function getCalendars() {
    $calendars = [
        [
            'id' => 1,
            'name' => 'Personal',
            'color' => '#3B82F6',
            'url' => 'https://caldav.example.com/personal',
            'userId' => 1,
            'isActive' => true,
            'syncToken' => 'sync-token-1',
            'createdAt' => date('c'),
            'updatedAt' => date('c')
        ],
        [
            'id' => 2,
            'name' => 'Work',
            'color' => '#EF4444',
            'url' => 'https://caldav.example.com/work',
            'userId' => 1,
            'isActive' => true,
            'syncToken' => 'sync-token-2',
            'createdAt' => date('c'),
            'updatedAt' => date('c')
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $calendars,
        'message' => 'Calendars retrieved successfully'
    ]);
}

function getEvents() {
    try {
        $allEvents = [];
        
        // Get events from CalDAV server first
        try {
            $caldavClient = new CalDAVClient();
            $calendars = $caldavClient->discoverCalendars();
            
            if (!empty($calendars['calendars'])) {
                $calendarUrl = $calendars['calendars'][0]['url'];
                $caldavEvents = $caldavClient->getEvents($calendarUrl);
                
                // Add CalDAV events to the list
                if (is_array($caldavEvents)) {
                    $allEvents = array_merge($allEvents, $caldavEvents);
                }
            }
        } catch (Exception $caldavError) {
            error_log("CalDAV error in getEvents: " . $caldavError->getMessage());
        }
        
        // Now sync with local storage - only keep events that still exist on CalDAV server
        $eventsFile = 'data/events.json';
        $localEvents = [];
        
        if (file_exists($eventsFile)) {
            $storedEvents = json_decode(file_get_contents($eventsFile), true) ?? [];
            
            // Create a map of local events by UID for quick lookup
            $localEventsByUid = [];
            foreach ($storedEvents as $localEvent) {
                $localEventsByUid[$localEvent['uid']] = $localEvent;
            }
            
            // Process CalDAV events and sync them to local storage
            $syncedEvents = [];
            foreach ($allEvents as $caldavEvent) {
                $uid = $caldavEvent['uid'];
                
                if (isset($localEventsByUid[$uid])) {
                    // Event exists locally - merge local data with CalDAV data
                    $localEvent = $localEventsByUid[$uid];
                    $mergedEvent = array_merge($caldavEvent, [
                        'reminder' => $localEvent['reminder'] ?? null,
                        'valarm' => $localEvent['valarm'] ?? null,
                        'attendees' => $localEvent['attendees'] ?? $caldavEvent['attendees'] ?? []
                    ]);
                    $syncedEvents[] = $mergedEvent;
                    error_log("Synced existing event: " . $caldavEvent['title'] . " (UID: " . $uid . ")");
                } else {
                    // New CalDAV event - add it to local storage
                    $syncedEvents[] = $caldavEvent;
                    error_log("Added new CalDAV event to local storage: " . $caldavEvent['title'] . " (UID: " . $uid . ")");
                }
            }
            
            // Save synced events back to local storage
            file_put_contents($eventsFile, json_encode($syncedEvents, JSON_PRETTY_PRINT));
            error_log("Updated local storage with " . count($syncedEvents) . " synced events");
            
            // Update the events list to use synced events
            $allEvents = $syncedEvents;
        }
        
        // Filter out deleted events
        $deletedEventsFile = 'data/deleted_events.json';
        $deletedEvents = [];
        
        if (file_exists($deletedEventsFile)) {
            $deletedEvents = json_decode(file_get_contents($deletedEventsFile), true) ?? [];
            error_log("Found " . count($deletedEvents) . " deleted events to filter out");
        }
        
        // Remove deleted events from the final list
        $filteredEvents = [];
        foreach ($allEvents as $event) {
            $isDeleted = false;
            foreach ($deletedEvents as $deletedEvent) {
                if ($deletedEvent['uid'] === $event['uid']) {
                    $isDeleted = true;
                    error_log("Filtering out deleted event: " . $event['title'] . " (UID: " . $event['uid'] . ")");
                    break;
                }
            }
            
            if (!$isDeleted) {
                $filteredEvents[] = $event;
            }
        }
        
        $allEvents = $filteredEvents;
        
        // If no events at all, return a sample event
        if (empty($allEvents)) {
            $allEvents = [
                [
                    'id' => 'sample-event-1',
                    'title' => 'Sample Event',
                    'description' => 'This is a sample event to get you started',
                    'start_time' => date('c', strtotime('today 10:00:00')),
                    'end_time' => date('c', strtotime('today 11:00:00')),
                    'all_day' => false,
                    'location' => 'Sample Location',
                    'calendar_id' => 1,
                    'uid' => 'sample-uid-1',
                    'etag' => 'sample-etag-1',
                    'created_at' => date('c'),
                    'updated_at' => date('c')
                ]
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $allEvents,
            'message' => 'Events retrieved successfully (CalDAV + Local reminders synced)'
        ]);
        
    } catch (Exception $e) {
        error_log("Error in getEvents: " . $e->getMessage());
        
        // Return fallback events if everything fails
        $fallbackEvents = [
            [
                'id' => 'fallback-event-1',
                'title' => 'System Error',
                'description' => 'Could not load events: ' . $e->getMessage(),
                'start_time' => date('c'),
                'end_time' => date('c', time() + 3600),
                'all_day' => false,
                'location' => 'System',
                'calendar_id' => 1,
                'uid' => 'fallback-uid-1',
                'etag' => 'fallback-etag-1',
                'created_at' => date('c'),
                'updated_at' => date('c')
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $fallbackEvents,
            'message' => 'Fallback events (system error: ' . $e->getMessage() . ')'
        ]);
    }
}

function getCalDAVStatus() {
    echo json_encode([
        'success' => true,
        'data' => [
            'connected' => true,
            'serverUrl' => $_ENV['CALDAV_SERVER_URL'] ?? 'https://apidata.googleusercontent.com/caldav/v2/',
            'username' => $_ENV['CALDAV_USERNAME'] ?? 'your-email@gmail.com',
            'calendarPath' => $_ENV['CALDAV_CALENDAR_PATH'] ?? 'calid/events',
            'message' => 'PHP CalDAV client is configured and ready'
        ]
    ]);
}

function discoverCalDAVCalendars() {
    try {
        $caldavClient = new CalDAVClient();
        $calendars = $caldavClient->discoverCalendars();
        
        echo json_encode([
            'success' => true,
            'data' => $calendars,
            'message' => 'CalDAV calendars discovered successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error discovering CalDAV calendars',
            'error' => $e->getMessage()
        ]);
    }
}

function getRawCalDAVData() {
    try {
        $caldavClient = new CalDAVClient();
        $calendars = $caldavClient->discoverCalendars();
        
        if (empty($calendars['calendars'])) {
            throw new Exception('No calendars found');
        }
        
        $calendarUrl = $calendars['calendars'][0]['url'];
        
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
        
        echo json_encode([
            'success' => true,
            'data' => [
                'status' => $response['status'],
                'body' => $response['body'],
                'bodyLength' => strlen($response['body'])
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
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
    // TODO: Implement
    echo json_encode(['error' => 'Not implemented yet']);
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
        
        // Create event object
        $event = [
            'id' => uniqid('event_'),
            'title' => $input['title'],
            'description' => $input['description'] ?? '',
            'location' => $input['location'] ?? '',
            'start_time' => $input['start_time'],
            'end_time' => $input['end_time'],
            'all_day' => $input['all_day'] ?? false,
            'calendar_id' => $input['calendar_id'] ?? 1,
            'uid' => uniqid('uid_'),
            'etag' => uniqid('etag_'),
            'created_at' => date('c'),
            'updated_at' => date('c'),
            'attendees' => $input['attendees'] ?? [],
            'reminder' => $input['reminder'] ?? null,
            'valarm' => null
        ];
        
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
        
        // Store the event in session storage (persists during PHP session)
        if (!isset($_SESSION['user_events'])) {
            $_SESSION['user_events'] = [];
        }
        $_SESSION['user_events'][] = $event;
        
        // Also store in a simple file for persistence across sessions
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
        
        // Now POST the event to the actual CalDAV server
        try {
            $caldavClient = new CalDAVClient();
            $calendars = $caldavClient->discoverCalendars();
            
            if (!empty($calendars['calendars'])) {
                $calendarUrl = $calendars['calendars'][0]['url'];
                
                // Use the proper generateICalEvent function for CalDAV storage
                $icalContent = generateICalEvent($event);
                
                // POST to CalDAV server
                $response = $caldavClient->createEvent($calendarUrl, $icalContent, $event['uid']);
                
                if ($response['status'] >= 200 && $response['status'] < 300) {
                    // Event created successfully, now send invitations if attendees exist
                    if (!empty($input['attendees']) && is_array($input['attendees'])) {
                        $invitationResult = sendEventInvitations($event, $input['attendees']);
                        if ($invitationResult['success']) {
                            $successResponse = [
                                'success' => true,
                                'data' => $event,
                                'message' => 'Event created successfully, synced to CalDAV server, and invitations sent to ' . $invitationResult['data']['successfulSends'] . ' attendees'
                            ];
                            error_log("Sending success response with invitations: " . json_encode($successResponse));
                            echo json_encode($successResponse);
                        } else {
                            $partialResponse = [
                                'success' => true,
                                'data' => $event,
                                'message' => 'Event created successfully and synced to CalDAV server, but failed to send invitations: ' . $invitationResult['message']
                            ];
                            error_log("Sending partial success response: " . json_encode($partialResponse));
                            echo json_encode($partialResponse);
                        }
                    } else {
                        $successResponse = [
                            'success' => true,
                            'data' => $event,
                            'message' => 'Event created successfully and synced to CalDAV server'
                        ];
                        error_log("Sending success response without invitations: " . json_encode($successResponse));
                        echo json_encode($successResponse);
                    }
                } else {
                    $partialResponse = [
                        'success' => true,
                        'data' => $event,
                        'message' => 'Event created locally but CalDAV sync failed: ' . $response['body']
                    ];
                    error_log("Sending partial success response (CalDAV failed): " . json_encode($partialResponse));
                    echo json_encode($partialResponse);
                }
            } else {
                $noCalendarResponse = [
                    'success' => true,
                    'data' => $event,
                    'message' => 'Event created locally but no CalDAV calendar found'
                ];
                error_log("Sending no calendar response: " . json_encode($noCalendarResponse));
                echo json_encode($noCalendarResponse);
            }
        } catch (Exception $caldavError) {
            error_log("CalDAV error in createEvent: " . $caldavError->getMessage());
            $response = [
                'success' => true,
                'data' => $event,
                'message' => 'Event created locally but CalDAV sync error: ' . $caldavError->getMessage()
            ];
            error_log("Sending response: " . json_encode($response));
            echo json_encode($response);
        }
        
    } catch (Exception $e) {
        error_log("Exception in createEvent: " . $e->getMessage());
        http_response_code(400);
        $errorResponse = [
            'success' => false,
            'message' => $e->getMessage()
        ];
        error_log("Sending error response: " . json_encode($errorResponse));
        echo json_encode($errorResponse);
    }
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
    
    $ical .= "END:VEVENT\r\n";
    $ical .= "END:VCALENDAR\r\n";
    
    return $ical;
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
        error_log("Raw input received: " . file_get_contents('php://input'));
        error_log("Parsed input: " . json_encode($input));
        
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
                    error_log("  Event " . $index . ": ID='" . ($event['id'] ?? 'null') . "', UID='" . ($event['uid'] ?? 'null') . "', Title='" . ($event['title'] ?? 'null') . "'");
                }
                
                // Try to find the event in CalDAV server
                error_log("Event not found in local storage, checking CalDAV server...");
                try {
                    $caldavClient = new CalDAVClient();
                    $calendars = $caldavClient->discoverCalendars();
                    
                    if (!empty($calendars['calendars'])) {
                        $calendarUrl = $calendars['calendars'][0]['url'];
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
            
            // Update event properties
            $eventToUpdate['title'] = $input['title'];
            $eventToUpdate['description'] = $input['description'] ?? $eventToUpdate['description'];
            $eventToUpdate['location'] = $input['location'] ?? $eventToUpdate['location'];
            $eventToUpdate['start_time'] = $input['start_time'];
            $eventToUpdate['end_time'] = $input['end_time'];
            $eventToUpdate['all_day'] = $input['all_day'] ?? $eventToUpdate['all_day'];
            $eventToUpdate['attendees'] = $input['attendees'] ?? $eventToUpdate['attendees'];
            
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
            
            // Find the event in CalDAV server to update it
            try {
                error_log("Attempting to update event on CalDAV server...");
                $caldavClient = new CalDAVClient();
                $calendars = $caldavClient->discoverCalendars();
                
                if (!empty($calendars['calendars'])) {
                    $calendarUrl = $calendars['calendars'][0]['url'];
                    
                    // Generate updated iCalendar content
                    $updatedICal = generateICalEvent($eventToUpdate);
                    error_log("Generated updated iCalendar content for CalDAV update");
                    
                    // Update the event on CalDAV server
                    $response = $caldavClient->updateEvent($calendarUrl, $eventToUpdate['uid'], $updatedICal);
                    error_log("CalDAV update response: " . json_encode($response));
                    
                    if ($response['success']) {
                        error_log("Event updated successfully on CalDAV server");
                    } else {
                        error_log("CalDAV update failed: " . $response['body']);
                    }
                } else {
                    error_log("No CalDAV calendars found for update");
                }
            } catch (Exception $caldavError) {
                error_log("CalDAV error in updateEvent: " . $caldavError->getMessage());
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
            
            // Return success response
            $successResponse = [
                'success' => true,
                'data' => $eventToUpdate,
                'message' => 'Event updated successfully'
            ];
            
            error_log("Sending success response: " . json_encode($successResponse));
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
    // TODO: Implement
    echo json_encode(['error' => 'Not implemented yet']);
}

function deleteEvent($id) {
    try {
        error_log("=== deleteEvent called with ID: " . $id . " ===");
        
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
            $caldavClient = new CalDAVClient();
            $calendars = $caldavClient->discoverCalendars();
            
            if (!empty($calendars['calendars'])) {
                $calendarUrl = $calendars['calendars'][0]['url'];
                error_log("Calendar URL: " . $calendarUrl);
                
                // Get all events to find the one we want to delete
                $allEvents = $caldavClient->getEvents($calendarUrl);
                error_log("Found " . count($allEvents) . " events from CalDAV server");
                
                // Find the event with matching ID or UID
                $eventToDelete = null;
                error_log("Looking for event with ID/UID: " . $id);
                foreach ($allEvents as $event) {
                    error_log("Checking event - ID: " . ($event['id'] ?? 'null') . ", UID: " . ($event['uid'] ?? 'null') . ", Title: " . ($event['title'] ?? 'null'));
                    
                    // Try to match by UID first (more reliable), then by ID
                    if (($event['uid'] ?? null) == $id || ($event['id'] ?? null) == $id) {
                        $eventToDelete = $event;
                        error_log("Found matching event: " . $event['title']);
                        break;
                    }
                }
                
                if (!$eventToDelete) {
                    error_log("No matching event found for ID/UID: " . $id);
                }
                
                // If we found the event, try to delete it from the server
                if ($eventToDelete && !empty($eventToDelete['uid'])) {
                    // Construct the event URL (this is the standard CalDAV format)
                    $eventUrl = rtrim($calendarUrl, '/') . '/' . $eventToDelete['uid'] . '.ics';
                    
                    error_log("Attempting to delete event from CalDAV server: " . $eventUrl);
                    
                    // Delete from CalDAV server
                    $deleteResult = $caldavClient->deleteEvent($eventUrl, $caldavClient->getAuthToken());
                    
                    if ($deleteResult) {
                        $deletedFromServer = true;
                        error_log("Successfully deleted event from CalDAV server");
                    } else {
                        error_log("Failed to delete event from CalDAV server");
                    }
                } else {
                    error_log("Event not found in CalDAV server or missing UID. EventToDelete: " . ($eventToDelete ? 'found' : 'null'));
                }
            } else {
                error_log("No calendars found");
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
        
        $deletedEventInfo = [
            'uid' => $uidToTrack,
            'title' => 'Event marked for deletion',
            'deleted_at' => date('c'),
            'deleted_by' => 'user'
        ];
        
        // Check if already in deleted list
        $alreadyDeleted = false;
        foreach ($deletedEvents as $deletedEvent) {
            if ($deletedEvent['uid'] === $deletedEventInfo['uid']) {
                $alreadyDeleted = true;
                break;
            }
        }
        
        if (!$alreadyDeleted) {
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

function syncCalendar($id = null) {
    // TODO: Implement
    echo json_encode(['error' => 'Not implemented yet']);
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
        $icalContent = generateICalInvitation([
            'title' => $event['title'],
            'description' => $event['description'] ?? '',
            'location' => $event['location'] ?? '',
            'startTime' => $event['start_time'],
            'endTime' => $event['end_time'],
            'allDay' => $event['all_day'] ?? false,
            'organizer' => $caldavClient ? $caldavClient->getUsername() : 'dheeraj.sharma@mithi.com'
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
 * Get CalDAV client instance to access user credentials
 */
function getCalDAVClient() {
    try {
        // Check if CalDAV client class exists
        if (!class_exists('CalDAVClient')) {
            require_once 'classes/CalDAVClient.php';
        }
        
        // Get CalDAV configuration
        // NOTE: For GitHub users: Copy caldav_demo.php to caldav.php and update with your credentials
        // The actual caldav.php file is gitignored to protect your credentials
        $caldavConfig = require_once 'config/caldav.php';
        
        // Create CalDAV client instance
        $caldavClient = new CalDAVClient(
            $caldavConfig['server_url'],
            $caldavConfig['username'],
            $caldavConfig['password']
        );
        
        return $caldavClient;
        
    } catch (Exception $e) {
        error_log("Failed to create CalDAV client: " . $e->getMessage());
        return null;
    }
}

?>
