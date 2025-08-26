<?php
// Start session for user event storage
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load environment variables
require_once 'config/database.php';
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
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetRequest($path) {
    switch ($path) {
        case 'health':
            echo json_encode(['status' => 'OK', 'message' => 'PHP CalDAV Calendar Backend is running']);
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
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
            }
    }
}

function handlePostRequest($path) {
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
        default:
            if (preg_match('/^calendars\/(\d+)\/sync$/', $path, $matches)) {
                syncCalendar($matches[1]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
            }
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
            
            // Only keep local events that still exist on the CalDAV server
            foreach ($storedEvents as $localEvent) {
                $stillExists = false;
                foreach ($allEvents as $caldavEvent) {
                    if ($localEvent['uid'] === $caldavEvent['uid']) {
                        $stillExists = true;
                        // Merge local reminder data with CalDAV event data
                        $caldavEvent['reminder'] = $localEvent['reminder'] ?? null;
                        $caldavEvent['valarm'] = $localEvent['valarm'] ?? null;
                        break;
                    }
                }
                
                // If event was deleted from server, remove it from local storage
                if (!$stillExists) {
                    error_log("Event {$localEvent['title']} (UID: {$localEvent['uid']}) was deleted from CalDAV server, removing from local storage");
                }
            }
            
            // Update local storage to only contain events that still exist on server
            $updatedLocalEvents = [];
            foreach ($storedEvents as $localEvent) {
                foreach ($allEvents as $caldavEvent) {
                    if ($localEvent['uid'] === $caldavEvent['uid']) {
                        $updatedLocalEvents[] = $localEvent;
                        break;
                    }
                }
            }
            
            // Save updated local events back to file
            file_put_contents($eventsFile, json_encode($updatedLocalEvents, JSON_PRETTY_PRINT));
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
            'updated_at' => date('c')
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
                
                // Generate iCalendar format for the event
                $icalEvent = generateICalEvent($event);
                
                // POST to CalDAV server
                $response = $caldavClient->createEvent($calendarUrl, $icalEvent, $event['uid']);
                
                if ($response['status'] >= 200 && $response['status'] < 300) {
                    echo json_encode([
                        'success' => true,
                        'data' => $event,
                        'message' => 'Event created successfully and synced to CalDAV server'
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'data' => $event,
                        'message' => 'Event created locally but CalDAV sync failed: ' . $response['body']
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => true,
                    'data' => $event,
                    'message' => 'Event created locally but no CalDAV calendar found'
                ]);
            }
        } catch (Exception $caldavError) {
            echo json_encode([
                'success' => true,
                'data' => $event,
                'message' => 'Event created locally but CalDAV sync error: ' . $caldavError->getMessage()
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
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
    // TODO: Implement
    echo json_encode(['error' => 'Not implemented yet']);
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
?>
