<?php
// Enhanced CalDAV Client based on Roundcube's proven implementation
// Reference: roundcube/rc-code/plugins/calendar/drivers/caldav/caldav_driver.php

class EnhancedCalDAVClient {
    private $serverUrl;
    private $username;
    private $password;
    private $baseUri;
    private $debug = false;

    public function __construct($serverUrl, $username, $password) {
        $this->serverUrl = $serverUrl;
        $this->username = $username;
        $this->password = $password;
        
        $tokens = parse_url($serverUrl);
        $this->baseUri = $tokens['scheme'] . "://" . $tokens['host'] . 
                        ($tokens['port'] ? ":" . $tokens['port'] : "");
    }

    /**
     * Discover calendars for a specific user using Roundcube's proven approach
     * Based on: _autodiscover_calendars() method from Roundcube's caldav_driver.php
     */
    public function discoverUserCalendars() {
        $calendars = array();
        
        // Step 1: Get current user principal
        $currentUserPrincipal = array('{DAV:}current-user-principal');
        $calendarHomeSet = array('{urn:ietf:params:xml:ns:caldav}calendar-home-set');
        $calAttribs = array('{DAV:}resourcetype', '{DAV:}displayname');
        
        // First, try to get current-user-principal from the server root
        $response = $this->propFind($this->serverUrl, array_merge($currentUserPrincipal, $calAttribs), 0);
        
        if (!$response) {
            $this->debugLog("Resource \"{$this->serverUrl}\" has no collections");
            return false;
        }
        
        // Check if the URL itself is a calendar
        if (isset($response['{DAV:}resourcetype']) && 
            $this->isCalendarResource($response['{DAV:}resourcetype'])) {
            
            $name = isset($response['{DAV:}displayname']) ? $response['{DAV:}displayname'] : '';
            
            $calendars[] = array(
                'name' => $name,
                'href' => $this->serverUrl,
            );
            return $calendars;
        }
        
        // Step 2: Get the user principal URL
        if (!isset($response[$currentUserPrincipal[0]])) {
            $this->debugLog("No current-user-principal found in response");
            return false;
        }
        
        $principalUrl = $this->baseUri . $response[$currentUserPrincipal[0]];
        $this->debugLog("Found principal URL: $principalUrl");
        
        // Step 3: Get calendar home set from principal
        $response = $this->propFind($principalUrl, $calendarHomeSet, 0);
        if (!$response) {
            $this->debugLog("Resource \"$principalUrl\" contains no calendars");
            return false;
        }
        
        if (!isset($response[$calendarHomeSet[0]])) {
            $this->debugLog("No calendar-home-set found in principal response");
            return false;
        }
        
        $calendarHomeUrl = $this->baseUri . $response[$calendarHomeSet[0]];
        $this->debugLog("Found calendar home URL: $calendarHomeUrl");
        
        // Step 4: Get all calendars from the calendar home
        $response = $this->propFind($calendarHomeUrl, $calAttribs, 1);
        
        foreach ($response as $collection => $attribs) {
            $found = false;
            $name = '';
            
            foreach ($attribs as $key => $value) {
                if ($key == '{DAV:}resourcetype' && $this->isCalendarResource($value)) {
                    $found = true;
                } else if ($key == '{DAV:}displayname') {
                    $name = $value;
                }
            }
            
            if ($found) {
                $calendars[] = array(
                    'name' => $name,
                    'href' => $this->baseUri . $collection,
                );
            }
        }
        
        return $calendars;
    }

    /**
     * Make a PROPFIND request (based on Roundcube's prop_find method)
     */
    private function propFind($path, $props, $depth) {
        $xml = '<?xml version="1.0" encoding="utf-8" ?>' . "\n";
        $xml .= '<propfind xmlns="DAV:">' . "\n";
        $xml .= '    <prop>' . "\n";
        
        foreach ($props as $prop) {
            $xml .= '        <' . $this->clarkToXml($prop) . '/>' . "\n";
        }
        
        $xml .= '    </prop>' . "\n";
        $xml .= '</propfind>';
        
        $headers = array(
            'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password),
            'Content-Type: application/xml; charset=utf-8',
            'Depth: ' . $depth,
            'User-Agent: EnhancedCalDAVClient/1.0'
        );
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PROPFIND',
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            $this->debugLog("cURL error: $error");
            return false;
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return $this->parsePropFindResponse($response);
        } else {
            $this->debugLog("HTTP error: $httpCode - Response: " . substr($response, 0, 200));
            return false;
        }
    }

    /**
     * Parse PROPFIND response into a structured array
     */
    private function parsePropFindResponse($xmlResponse) {
        $result = array();
        
        try {
            $xml = new SimpleXMLElement($xmlResponse);
            $xml->registerXPathNamespace('D', 'DAV:');
            $xml->registerXPathNamespace('C', 'urn:ietf:params:xml:ns:caldav');
            
            $responses = $xml->xpath('//D:response');
            
            foreach ($responses as $response) {
                $href = (string)$response->xpath('D:href')[0];
                
                $propstat = $response->xpath('D:propstat');
                if (empty($propstat)) continue;
                
                $prop = $propstat[0]->xpath('D:prop')[0];
                $status = (string)$propstat[0]->xpath('D:status')[0];
                
                if (strpos($status, '200') !== false) {
                    $result[$href] = array();
                    
                    // Parse properties
                    foreach ($prop->children() as $child) {
                        $name = $child->getName();
                        $namespace = $child->getNamespaces(true);
                        
                        if ($name === 'resourcetype') {
                            $result[$href]['{DAV:}resourcetype'] = $this->parseResourceType($child);
                        } else if ($name === 'displayname') {
                            $result[$href]['{DAV:}displayname'] = (string)$child;
                        } else if ($name === 'current-user-principal') {
                            $result[$href]['{DAV:}current-user-principal'] = (string)$child->xpath('D:href')[0];
                        } else if ($name === 'calendar-home-set') {
                            $result[$href]['{urn:ietf:params:xml:ns:caldav}calendar-home-set'] = (string)$child->xpath('D:href')[0];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->debugLog("XML parsing error: " . $e->getMessage());
            return false;
        }
        
        return $result;
    }

    /**
     * Parse resource type from XML
     */
    private function parseResourceType($element) {
        $types = array();
        foreach ($element->children() as $child) {
            $types[] = '{' . $child->getNamespaces(true)[''] . '}' . $child->getName();
        }
        return $types;
    }

    /**
     * Check if a resource is a calendar
     */
    private function isCalendarResource($resourceType) {
        if (is_array($resourceType)) {
            return in_array('{urn:ietf:params:xml:ns:caldav}calendar', $resourceType);
        }
        return false;
    }

    /**
     * Convert Clark notation to XML element name
     */
    private function clarkToXml($clark) {
        if (preg_match('/^\{([^}]+)\}(.+)$/', $clark, $matches)) {
            $namespace = $matches[1];
            $name = $matches[2];
            
            if ($namespace === 'DAV:') {
                return 'D:' . $name;
            } else if ($namespace === 'urn:ietf:params:xml:ns:caldav') {
                return 'C:' . $name;
            }
        }
        return $clark;
    }

    /**
     * Debug logging
     */
    private function debugLog($message) {
        if ($this->debug) {
            echo "[DEBUG] $message\n";
        }
    }

    public function setDebug($enabled) {
        $this->debug = $enabled;
    }
}

// Test the enhanced client
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "=== Enhanced CalDAV Client Test ===\n";
    echo "Based on Roundcube's proven implementation\n\n";
    
    $client = new EnhancedCalDAVClient(
        'http://rc.mithi.com:8008',
        'dheeraj.sharma@mithi.com',
        'M!th!#567'
    );
    
    $client->setDebug(true);
    
    $calendars = $client->discoverUserCalendars();
    
    if ($calendars) {
        echo "\n✅ Found " . count($calendars) . " calendars:\n";
        foreach ($calendars as $calendar) {
            echo "- {$calendar['name']}: {$calendar['href']}\n";
        }
    } else {
        echo "\n❌ No calendars found or error occurred\n";
    }
}
?>
