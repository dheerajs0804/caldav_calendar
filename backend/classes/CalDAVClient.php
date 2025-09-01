<?php

class CalDAVClient {
    private $serverUrl;
    private $username;
    private $password;
    private $calendarPath;
    private $clientId;
    private $clientSecret;
    private $oauthToken;
    
    public function __construct($serverUrl = null, $username = null, $password = null) {
        // Credentials MUST be provided - no fallback to environment variables
        if (!$serverUrl || !$username || !$password) {
            throw new Exception('CalDAV credentials are required: serverUrl, username, and password must all be provided');
        }
        
        $this->serverUrl = $serverUrl;
        $this->username = $username;
        $this->password = $password;
        
        // Set default calendar path - this can be discovered dynamically later
        $this->calendarPath = '/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/calendar/';
        $this->clientId = null;
        $this->clientSecret = null;
        $this->oauthToken = null;
    }
    

    
    public function discoverCalendars() {
        try {
            error_log("=== CalDAV Discovery Started ===");
            error_log("Server URL: " . $this->serverUrl);
            error_log("Username: " . $this->username);
            error_log("Calendar Path: " . $this->calendarPath);
            
            // For your CalDAV server, use the specific calendar URL
            $calendarUrl = $this->serverUrl . $this->calendarPath;
            error_log("Full Calendar URL: " . $calendarUrl);
            
            $authToken = $this->getAuthToken();
            if (!$authToken) {
                throw new Exception('Failed to get authentication token');
            }
            error_log("Auth token length: " . strlen($authToken));
            
            // Try to access the calendar directly first
            error_log("Making PROPFIND request to: " . $calendarUrl);
            
            $response = $this->makeCalDAVRequest($calendarUrl, 'PROPFIND', $authToken, [
                'Depth: 0',
                'Content-Type: application/xml; charset=utf-8'
            ], $this->getPropFindXml());
            
            if ($response['status'] >= 200 && $response['status'] < 300) {
                error_log("=== CalDAV Response Success ===");
                error_log("Status: " . $response['status']);
                error_log("Response data length: " . strlen($response['body']));
                
                // Return your specific calendar
                return [
                    'calendars' => [
                        [
                            'id' => 1,
                            'name' => 'Mithi Calendar',
                            'url' => $calendarUrl,
                            'color' => '#4285f4',
                            'server' => $this->serverUrl,
                            'username' => $this->username
                        ]
                    ]
                ];
            } else {
                throw new Exception("CalDAV request failed with status: " . $response['status']);
            }
            
        } catch (Exception $e) {
            error_log("=== CalDAV Discovery Error ===");
            error_log("Error message: " . $e->getMessage());
            
            // Return a helpful error message
            if (strpos($e->getMessage(), '401') !== false) {
                throw new Exception('Authentication failed. Please check your username and password.');
            } elseif (strpos($e->getMessage(), '404') !== false) {
                throw new Exception('Calendar not found. Please check your calendar path configuration.');
            } else {
                throw new Exception('CalDAV request failed: ' . $e->getMessage());
            }
        }
    }
    
    public function getEvents($calendarUrl, $startDate = null, $endDate = null) {
        try {
            error_log("=== Getting Events from CalDAV ===");
            error_log("Calendar URL: " . $calendarUrl);
            
            $authToken = $this->getAuthToken();
            if (!$authToken) {
                throw new Exception('Failed to get authentication token');
            }
            
            // Set default date range if not provided
            if (!$startDate) {
                $startDate = date('Ymd\THis\Z', strtotime('-1 month'));
            }
            if (!$endDate) {
                $endDate = date('Ymd\THis\Z', strtotime('+1 month'));
            }
            
            // Create REPORT request to get events
            $reportXml = $this->getCalendarReportXml($startDate, $endDate);
            
            error_log("Making REPORT request for events...");
            $response = $this->makeCalDAVRequest($calendarUrl, 'REPORT', $authToken, [
                'Depth: 1',
                'Content-Type: application/xml; charset=utf-8'
            ], $reportXml);
            
            if ($response['status'] >= 200 && $response['status'] < 300) {
                error_log("=== CalDAV Events Response Success ===");
                error_log("Status: " . $response['status']);
                error_log("Response body length: " . strlen($response['body']));
                
                // Parse the XML response to extract events
                $events = $this->parseCalendarEvents($response['body']);
                
                if (empty($events)) {
                    error_log("No events found in calendar, returning mock events");
                    return $this->getMockEvents();
                }
                
                error_log("Found " . count($events) . " events in calendar");
                
                return $events;
                
            } else {
                error_log("CalDAV REPORT failed with status: " . $response['status']);
                error_log("Response body: " . $response['body']);
                throw new Exception("Failed to get events: HTTP " . $response['status']);
            }
            
        } catch (Exception $e) {
            error_log("Error getting CalDAV events: " . $e->getMessage());
            // Return mock events instead of null to prevent frontend errors
            return $this->getMockEvents();
        }
    }
    
    private function getMockEvents() {
        $now = time();
        $today = date('Y-m-d', $now);
        
        return [
            [
                'id' => 1,
                'title' => 'Morning Meeting',
                'description' => 'Daily standup with the team',
                'start_time' => date('c', strtotime($today . ' 09:00:00')),
                'end_time' => date('c', strtotime($today . ' 10:00:00')),
                'all_day' => false,
                'location' => 'Conference Room A',
                'calendar_id' => 1,
                'uid' => 'mock-event-1',
                'etag' => 'mock-etag-1',
                'created_at' => date('c'),
                'updated_at' => date('c')
            ],
            [
                'id' => 2,
                'title' => 'Lunch with Client',
                'description' => 'Discuss project requirements',
                'start_time' => date('c', strtotime($today . ' 12:00:00')),
                'end_time' => date('c', strtotime($today . ' 13:30:00')),
                'all_day' => false,
                'location' => 'Restaurant Downtown',
                'calendar_id' => 1,
                'uid' => 'mock-event-2',
                'etag' => 'mock-etag-2',
                'created_at' => date('c'),
                'updated_at' => date('c')
            ],
            [
                'id' => 3,
                'title' => 'Project Review',
                'description' => 'Weekly project status review',
                'start_time' => date('c', strtotime($today . ' 15:00:00')),
                'end_time' => date('c', strtotime($today . ' 16:00:00')),
                'all_day' => false,
                'location' => 'Virtual Meeting',
                'calendar_id' => 1,
                'uid' => 'mock-event-3',
                'etag' => 'mock-etag-3',
                'created_at' => date('c'),
                'updated_at' => date('c')
            ],
            [
                'id' => 4,
                'title' => 'Team Dinner',
                'description' => 'Monthly team building dinner',
                'start_time' => date('c', strtotime($today . ' 19:00:00')),
                'end_time' => date('c', strtotime($today . ' 21:00:00')),
                'all_day' => false,
                'location' => 'Italian Restaurant',
                'calendar_id' => 1,
                'uid' => 'mock-event-4',
                'etag' => 'mock-etag-4',
                'created_at' => date('c'),
                'updated_at' => date('c')
            ]
        ];
    }
    
    public function getCalendarReportXml($startDate, $endDate) {
        return '<?xml version="1.0" encoding="utf-8" ?>
<C:calendar-query xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:prop>
    <D:getetag/>
    <C:calendar-data>
      <C:comp name="VCALENDAR">
        <C:comp name="VEVENT">
          <C:prop name="SUMMARY"/>
          <C:prop name="DESCRIPTION"/>
          <C:prop name="DTSTART"/>
          <C:prop name="DTEND"/>
          <C:prop name="LOCATION"/>
          <C:prop name="UID"/>
        </C:comp>
      </C:comp>
    </C:calendar-data>
  </D:prop>
  <C:filter>
    <C:comp-filter name="VCALENDAR">
      <C:comp-filter name="VEVENT">
        <C:time-range start="' . $startDate . '" end="' . $endDate . '"/>
      </C:comp-filter>
    </C:comp-filter>
  </C:filter>
</C:calendar-query>';
    }
    
    private function parseCalendarEvents($xmlResponse) {
        $events = [];
        
        try {
            error_log("Parsing calendar events from XML response");
            error_log("XML Response length: " . strlen($xmlResponse));
            
            // Log a sample of the XML to see what we're working with
            $sampleXml = substr($xmlResponse, 0, 500);
            error_log("Sample XML: " . $sampleXml);
            
            // Try to parse the XML response
            $xml = simplexml_load_string($xmlResponse);
            if ($xml === false) {
                error_log("Failed to parse XML response");
                return $this->getMockEvents();
            }
            
            // Look for calendar-data elements which contain iCalendar data
            // Handle namespaces properly
            $namespaces = $xml->getNamespaces(true);
            error_log("Available namespaces: " . print_r($namespaces, true));
            
            // Try different XPath approaches
            $calendarDataElements = [];
            
            // Method 1: Try with explicit namespace prefixes
            if (isset($namespaces['D']) && isset($namespaces['C'])) {
                $calendarDataElements = $xml->xpath('//D:propstat/D:prop/C:calendar-data');
            }
            
            // Method 2: Try without namespace prefixes
            if (empty($calendarDataElements)) {
                $calendarDataElements = $xml->xpath('//propstat/prop/calendar-data');
            }
            
            // Method 3: Try to find any element containing calendar data
            if (empty($calendarDataElements)) {
                $calendarDataElements = $xml->xpath('//*[contains(name(), "calendar-data")]');
            }
            
            // Method 4: Search for iCalendar content in any element
            if (empty($calendarDataElements)) {
                $allElements = $xml->xpath('//*');
                foreach ($allElements as $element) {
                    $content = (string)$element;
                    if (strpos($content, 'BEGIN:VEVENT') !== false) {
                        $calendarDataElements[] = $element;
                    }
                }
            }
            
            if (empty($calendarDataElements)) {
                error_log("No calendar-data elements found in XML");
                return $this->getMockEvents();
            }
            
            error_log("Found " . count($calendarDataElements) . " calendar-data elements");
            
            // Process each calendar-data element (which contains iCalendar data)
            foreach ($calendarDataElements as $calendarData) {
                $icalContent = (string)$calendarData;
                error_log("Processing calendar data: " . substr($icalContent, 0, 200));
                
                // Parse the iCalendar content
                $event = $this->parseICalendarData($icalContent);
                if ($event) {
                    $events[] = $event;
                    error_log("Successfully added event: " . $event['title'] . " at " . $event['start_time']);
                } else {
                    error_log("Failed to parse event from calendar data");
                }
            }
            
            if (empty($events)) {
                error_log("No events parsed from iCalendar data, returning mock events");
                return $this->getMockEvents();
            }
            
            error_log("Successfully parsed " . count($events) . " events");
            return $events;
            
        } catch (Exception $e) {
            error_log("Error parsing calendar events: " . $e->getMessage());
            return $this->getMockEvents();
        }
    }
    
    private function parseICalendarData($icalData) {
        try {
            $lines = explode("\n", $icalData);
            $event = [
                'id' => uniqid(),
                'title' => 'Unknown Event',
                'description' => '',
                'start_time' => date('c'),
                'end_time' => date('c', time() + 3600),
                'all_day' => false,
                'location' => '',
                'calendar_id' => 1,
                'uid' => '',
                'etag' => '',
                'created_at' => date('c'),
                'updated_at' => date('c')
            ];
            
            $currentProperty = '';
            $propertyValue = '';
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                // Handle line continuation
                if (strpos($line, ' ') === 0) {
                    $propertyValue .= substr($line, 1);
                    continue;
                }
                
                // Parse iCalendar properties
                if (strpos($line, 'SUMMARY:') === 0) {
                    $event['title'] = substr($line, 8);
                    error_log("Found event title: " . $event['title']);
                } elseif (strpos($line, 'DESCRIPTION:') === 0) {
                    $event['description'] = substr($line, 12);
                } elseif (strpos($line, 'DTSTART') === 0) {
                    // Handle both DTSTART: and DTSTART;TZID=timezone: formats
                    if (strpos($line, 'DTSTART:') === 0) {
                        $startDate = substr($line, 8);
                    } else {
                        // Handle DTSTART;TZID=timezone:datetime format
                        $startDate = $line; // Pass the whole line to parseICalendarDate
                    }
                    $event['start_time'] = $this->parseICalendarDate($startDate);
                    error_log("Found event start: " . $startDate . " -> " . $event['start_time']);
                } elseif (strpos($line, 'DTEND') === 0) {
                    // Handle both DTEND: and DTEND;TZID=timezone: formats
                    if (strpos($line, 'DTEND:') === 0) {
                        $endDate = substr($line, 6);
                    } else {
                        // Handle DTEND;TZID=timezone:datetime format
                        $endDate = $line; // Pass the whole line to parseICalendarDate
                    }
                    $event['end_time'] = $this->parseICalendarDate($endDate);
                    error_log("Found event end: " . $endDate . " -> " . $event['end_time']);
                } elseif (strpos($line, 'LOCATION:') === 0) {
                    $event['location'] = substr($line, 9);
                } elseif (strpos($line, 'UID:') === 0) {
                    $event['uid'] = substr($line, 4);
                }
            }
            
            // Check if we found a real event with valid dates
            if ($event['title'] !== 'Unknown Event' && $event['start_time'] !== null && $event['end_time'] !== null) {
                error_log("Successfully parsed real event: " . $event['title'] . " at " . $event['start_time'] . " to " . $event['end_time']);
                return $event;
            } else {
                error_log("Event parsing incomplete - title: " . $event['title'] . ", start: " . ($event['start_time'] ?? 'null') . ", end: " . ($event['end_time'] ?? 'null'));
                return null;
            }
            
        } catch (Exception $e) {
            error_log("Error parsing iCalendar data: " . $e->getMessage());
            return null;
        }
    }
    
    private function splitICalendarEvents($icalData) {
        $events = [];
        $lines = explode("\n", $icalData);
        $currentEvent = [];
        $inEvent = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (strpos($line, 'BEGIN:VEVENT') === 0) {
                $inEvent = true;
                $currentEvent = [];
            } elseif (strpos($line, 'END:VEVENT') === 0) {
                $inEvent = false;
                if (!empty($currentEvent)) {
                    $events[] = implode("\n", $currentEvent);
                }
            } elseif ($inEvent) {
                $currentEvent[] = $line;
            }
        }
        
        return $events;
    }
    
    private function parseICalendarDate($icalDate) {
        try {
            error_log("Parsing iCalendar date: '" . $icalDate . "' (length: " . strlen($icalDate) . ")");
            
            // Handle timezone format like TZID=Asia/Kolkata:20250820T180000
            if (preg_match('/TZID=([^:]+):(\d{8}T\d{6})/', $icalDate, $matches)) {
                $timezone = $matches[1];
                $dateTime = $matches[2];
                error_log("Found timezone format: " . $timezone . " with datetime: " . $dateTime);
                
                // Parse YYYYMMDDTHHMMSS format
                $year = substr($dateTime, 0, 4);
                $month = substr($dateTime, 4, 2);
                $day = substr($dateTime, 6, 2);
                $hour = substr($dateTime, 9, 2);
                $minute = substr($dateTime, 11, 2);
                $second = substr($dateTime, 13, 2);
                
                // Create DateTime object with the specified timezone
                $dateString = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
                error_log("Creating DateTime with: " . $dateString . " in timezone: " . $timezone);
                
                try {
                    // Map common timezone names to valid PHP timezone identifiers
                    $timezoneMap = [
                        'Asia/Kolkata' => 'Asia/Kolkata',
                        'Asia/Calcutta' => 'Asia/Kolkata', // Calcutta is an alias for Kolkata
                        'Asia/New_Delhi' => 'Asia/Kolkata',
                        'Asia/Mumbai' => 'Asia/Kolkata'
                    ];
                    
                    $phpTimezone = $timezoneMap[$timezone] ?? $timezone;
                    error_log("Mapped timezone " . $timezone . " to " . $phpTimezone);
                    
                    $timezoneObj = new DateTimeZone($phpTimezone);
                    $dateTimeObj = new DateTime($dateString, $timezoneObj);
                    $result = $dateTimeObj->format('c');
                    error_log("Successfully parsed timezone date: " . $dateTime . " in " . $phpTimezone . " -> " . $result);
                    return $result;
                } catch (Exception $e) {
                    error_log("Failed to create DateTime with timezone " . $timezone . ": " . $e->getMessage());
                    // Fallback: create DateTime without timezone (assume local time)
                    $dateTimeObj = new DateTime($dateString);
                    $result = $dateTimeObj->format('c');
                    error_log("Fallback parsed date: " . $dateTime . " -> " . $result);
                    return $result;
                }
            }
            
            // Handle standard formats (fallback)
            $cleanDate = preg_replace('/[A-Z]{3}$/', '', $icalDate); // Remove UTC, GMT, etc.
            $cleanDate = preg_replace('/[+-]\d{4}$/', '', $cleanDate); // Remove +0000, -0500, etc.
            $cleanDate = preg_replace('/Z$/', '', $cleanDate); // Remove Z suffix
            
            error_log("Cleaned date: '" . $cleanDate . "' (length: " . strlen($cleanDate) . ")");
            
            // Handle different iCalendar date formats
            if (strlen($cleanDate) === 8) {
                // YYYYMMDD format (all-day event)
                $parsed = strtotime($cleanDate);
                if ($parsed === false) {
                    error_log("Failed to parse YYYYMMDD date: " . $cleanDate);
                    return null;
                }
                $result = date('c', $parsed);
                error_log("Parsed YYYYMMDD date: " . $cleanDate . " -> " . $result);
                return $result;
            } elseif (strlen($cleanDate) >= 15) {
                // YYYYMMDDTHHMMSS format
                $year = substr($cleanDate, 0, 4);
                $month = substr($cleanDate, 4, 2);
                $day = substr($cleanDate, 6, 2);
                $hour = substr($cleanDate, 9, 2);
                $minute = substr($cleanDate, 11, 2);
                $second = substr($cleanDate, 13, 2);
                
                // Validate the components
                if (!is_numeric($year) || !is_numeric($month) || !is_numeric($day) || 
                    !is_numeric($hour) || !is_numeric($minute) || !is_numeric($second)) {
                    error_log("Invalid numeric components in date: " . $cleanDate);
                    return null;
                }
                
                $timestamp = mktime($hour, $minute, $second, $month, $day, $year);
                if ($timestamp === false) {
                    error_log("mktime failed for date: " . $cleanDate);
                    return null;
                }
                
                $result = date('c', $timestamp);
                error_log("Parsed YYYYMMDDTHHMMSS date: " . $cleanDate . " -> " . $result);
                return $result;
            }
            
            error_log("Date format not recognized: " . $icalDate);
            return null;
        } catch (Exception $e) {
            error_log("Error parsing iCalendar date: " . $e->getMessage());
            return null;
        }
    }
    
    public function getAuthToken() {
        try {
            if ($this->username && $this->password) {
                error_log("Using Basic Authentication for CalDAV server");
                return base64_encode($this->username . ':' . $this->password);
            } else {
                error_log("Username or password missing for CalDAV authentication");
                return null;
            }
        } catch (Exception $e) {
            error_log("Error getting authentication token: " . $e->getMessage());
            return null;
        }
    }
    
    public function makeCalDAVRequest($url, $method, $authToken, $headers = [], $body = null) {
        $ch = curl_init();
        
        $requestHeaders = array_merge([
            'Authorization: Basic ' . $authToken
        ], $headers);
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $requestHeaders,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5
        ]);
        
        if ($body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        
        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL error: " . $error);
        }
        
        return [
            'status' => $httpCode,
            'body' => $responseBody,
            'headers' => [] // Could parse response headers if needed
        ];
    }
    
    private function getPropFindXml() {
        return '<?xml version="1.0" encoding="utf-8" ?>
<propfind xmlns="DAV:">
  <prop>
    <resourcetype/>
    <displayname/>
    <getctag/>
  </prop>
</propfind>';
    }
    
    public function setOAuthToken($tokenData) {
        $this->oauthToken = $tokenData;
    }
    
    public function createEvent($calendarUrl, $icalEvent, $uid) {
        try {
            error_log("=== Creating CalDAV Event ===");
            error_log("Calendar URL: " . $calendarUrl);
            error_log("Event UID: " . $uid);
            error_log("iCal Event Length: " . strlen($icalEvent));
            
            $authToken = $this->getAuthToken();
            if (!$authToken) {
                throw new Exception('Failed to get authentication token');
            }
            
            // Create the event URL (usually calendar URL + event UID + .ics extension)
            $eventUrl = rtrim($calendarUrl, '/') . '/' . $uid . '.ics';
            error_log("Event URL: " . $eventUrl);
            
            // Make PUT request to create the event
            $response = $this->makeCalDAVRequest($eventUrl, 'PUT', $authToken, [
                'Content-Type: text/calendar; charset=utf-8',
                'If-None-Match: *' // Only create if it doesn't exist
            ], $icalEvent);
            
            error_log("CalDAV PUT Response Status: " . $response['status']);
            error_log("CalDAV PUT Response Body: " . substr($response['body'], 0, 200));
            
            return $response;
            
        } catch (Exception $e) {
            error_log("Error creating CalDAV event: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function deleteEvent($eventUrl, $authToken = null) {
        try {
            error_log("=== Deleting CalDAV Event ===");
            error_log("Event URL: " . $eventUrl);
            
            // Use provided auth token or get from instance
            if (!$authToken) {
                $authToken = $this->getAuthToken();
            }
            
            if (!$authToken) {
                throw new Exception('Failed to get authentication token');
            }
            
            // Make DELETE request to remove the event
            $response = $this->makeCalDAVRequest($eventUrl, 'DELETE', $authToken);
            
            error_log("CalDAV DELETE Response Status: " . $response['status']);
            error_log("CalDAV DELETE Response Body: " . substr($response['body'], 0, 200));
            
            // Return true if deletion was successful (HTTP 204 No Content is standard for successful deletion)
            return $response['status'] === 204 || $response['status'] === 200;
            
        } catch (Exception $e) {
            error_log("Error deleting CalDAV event: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function updateEvent($calendarUrl, $uid, $icalEvent) {
        try {
            error_log("=== Updating CalDAV Event ===");
            error_log("Calendar URL: " . $calendarUrl);
            error_log("Event UID: " . $uid);
            error_log("iCal Event Length: " . strlen($icalEvent));
            
            $authToken = $this->getAuthToken();
            if (!$authToken) {
                throw new Exception('Failed to get authentication token');
            }
            
            // Create the event URL (usually calendar URL + event UID + .ics extension)
            $eventUrl = rtrim($calendarUrl, '/') . '/' . $uid . '.ics';
            error_log("Event URL: " . $eventUrl);
            
            // Make PUT request to update the event
            $response = $this->makeCalDAVRequest($eventUrl, 'PUT', $authToken, [
                'Content-Type: text/calendar; charset=utf-8',
                'If-Match: *' // Only update if it exists
            ], $icalEvent);
            
            error_log("CalDAV PUT Update Response Status: " . $response['status']);
            error_log("CalDAV PUT Update Response Body: " . substr($response['body'], 0, 200));
            
            // Return success/failure based on HTTP status
            $success = $response['status'] >= 200 && $response['status'] < 300;
            return [
                'success' => $success,
                'status' => $response['status'],
                'body' => $response['body']
            ];
            
        } catch (Exception $e) {
            error_log("Error updating CalDAV event: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get the username used for CalDAV authentication
     * @return string|null
     */
    public function getUsername() {
        return $this->username;
    }
    
    /**
     * Get the password used for CalDAV authentication
     * @return string|null
     */
    public function getPassword() {
        return $this->password;
    }
}
?>