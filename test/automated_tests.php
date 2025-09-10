<?php
/**
 * Automated Test Suite for CalDAV Calendar Application
 * Tests all features marked as "Done" in the project tracking
 */

class CalendarAppTestSuite {
    private $baseUrl = 'http://localhost:8000';
    private $testResults = [];
    private $sessionCookie = '';
    
    public function __construct() {
        echo "🧪 Starting Automated Test Suite for CalDAV Calendar App\n";
        echo "======================================================\n\n";
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        $this->testUserLogin();
        $this->testListCalendars();
        $this->testAddCalendar();
        $this->testCalendarViews();
        $this->testBasicEventAddDelete();
        $this->testReminderPopup();
        $this->testReload();
        
        $this->printSummary();
    }
    
    /**
     * Test 1: Implement user login
     */
    public function testUserLogin() {
        echo "🔐 Testing: User Login\n";
        echo "----------------------\n";
        
        // Test 1.1: Login with valid credentials
        $loginData = [
            'username' => 'test',
            'password' => 'test'
        ];
        
        $response = $this->makeRequest('POST', '/auth/login', $loginData);
        
        if ($response['success'] && isset($response['data']['user']['username'])) {
            $this->testResults['login_valid'] = 'PASS';
            echo "✅ Login with valid credentials: PASS\n";
            
            // Store session cookie for subsequent requests
            $this->sessionCookie = $response['cookie'] ?? '';
        } else {
            $this->testResults['login_valid'] = 'FAIL';
            echo "❌ Login with valid credentials: FAIL\n";
        }
        
        // Test 1.2: Login with invalid credentials
        $invalidLoginData = [
            'username' => 'invalid',
            'password' => 'invalid'
        ];
        
        $response = $this->makeRequest('POST', '/auth/login', $invalidLoginData);
        
        if (!$response['success']) {
            $this->testResults['login_invalid'] = 'PASS';
            echo "✅ Login with invalid credentials (rejection): PASS\n";
        } else {
            $this->testResults['login_invalid'] = 'FAIL';
            echo "❌ Login with invalid credentials (rejection): FAIL\n";
        }
        
        // Test 1.3: Check authentication status
        $response = $this->makeRequest('GET', '/auth/status');
        
        if ($response['success'] && $response['data']['authenticated'] === true) {
            $this->testResults['auth_status'] = 'PASS';
            echo "✅ Authentication status check: PASS\n";
        } else {
            $this->testResults['auth_status'] = 'FAIL';
            echo "❌ Authentication status check: FAIL\n";
        }
        
        echo "\n";
    }
    
    /**
     * Test 2: Add feature: List calendars
     */
    public function testListCalendars() {
        echo "📅 Testing: List Calendars\n";
        echo "--------------------------\n";
        
        // Test 2.1: Get user calendars
        $response = $this->makeRequest('GET', '/calendars/user');
        
        if ($response['success'] && isset($response['data']['calendars']) && is_array($response['data']['calendars'])) {
            $this->testResults['list_calendars'] = 'PASS';
            echo "✅ List calendars: PASS (Found " . count($response['data']['calendars']) . " calendars)\n";
            
            // Log calendar details
            foreach ($response['data']['calendars'] as $calendar) {
                echo "   📋 Calendar: " . $calendar['name'] . " (" . $calendar['url'] . ")\n";
            }
        } else {
            $this->testResults['list_calendars'] = 'FAIL';
            echo "❌ List calendars: FAIL\n";
        }
        
        // Test 2.2: Get all calendars (discovery)
        $response = $this->makeRequest('GET', '/calendars');
        
        if ($response['success'] && isset($response['data']) && is_array($response['data'])) {
            $this->testResults['discover_calendars'] = 'PASS';
            echo "✅ Calendar discovery: PASS\n";
        } else {
            $this->testResults['discover_calendars'] = 'FAIL';
            echo "❌ Calendar discovery: FAIL\n";
        }
        
        echo "\n";
    }
    
    /**
     * Test 3: Add feature: Add calendar
     */
    public function testAddCalendar() {
        echo "➕ Testing: Add Calendar\n";
        echo "-----------------------\n";
        
        $calendarData = [
            'name' => 'Test Calendar ' . date('Y-m-d H:i:s'),
            'description' => 'Automated test calendar',
            'color' => '#ff6b6b'
        ];
        
        $response = $this->makeRequest('POST', '/calendars', $calendarData);
        
        if ($response['success'] && isset($response['data']['name'])) {
            $this->testResults['add_calendar'] = 'PASS';
            echo "✅ Add calendar: PASS\n";
            echo "   📋 Created: " . $response['data']['name'] . "\n";
            echo "   🔗 URL: " . $response['data']['url'] . "\n";
        } else {
            $this->testResults['add_calendar'] = 'FAIL';
            echo "❌ Add calendar: FAIL\n";
            if (isset($response['message'])) {
                echo "   Error: " . $response['message'] . "\n";
            }
        }
        
        echo "\n";
    }
    
    /**
     * Test 4: Add feature: Day, Week, Month, Agenda view
     */
    public function testCalendarViews() {
        echo "📊 Testing: Calendar Views\n";
        echo "--------------------------\n";
        
        // Test 4.1: Get events for different views
        $views = ['day', 'week', 'month', 'agenda'];
        
        foreach ($views as $view) {
            $response = $this->makeRequest('GET', '/events?view=' . $view);
            
            if ($response['success']) {
                $this->testResults['view_' . $view] = 'PASS';
                echo "✅ " . ucfirst($view) . " view: PASS\n";
            } else {
                $this->testResults['view_' . $view] = 'FAIL';
                echo "❌ " . ucfirst($view) . " view: FAIL\n";
            }
        }
        
        echo "\n";
    }
    
    /**
     * Test 5: Implement new calendar ui - basic event add/delete
     */
    public function testBasicEventAddDelete() {
        echo "📝 Testing: Basic Event Add/Delete\n";
        echo "----------------------------------\n";
        
        // Test 5.1: Add event
        $eventData = [
            'title' => 'Test Event ' . date('Y-m-d H:i:s'),
            'description' => 'Automated test event',
            'location' => 'Test Location',
            'start_time' => date('Y-m-d\TH:i:s', strtotime('+1 hour')),
            'end_time' => date('Y-m-d\TH:i:s', strtotime('+2 hours')),
            'all_day' => false,
            'attendees' => [],
            'reminder' => [
                'enabled' => false,
                'type' => 'message',
                'time' => 15,
                'unit' => 'minutes',
                'relativeTo' => 'start'
            ]
        ];
        
        $response = $this->makeRequest('POST', '/events', $eventData);
        
        if ($response['success'] && isset($response['data']['id'])) {
            $this->testResults['add_event'] = 'PASS';
            echo "✅ Add event: PASS\n";
            echo "   📝 Event ID: " . $response['data']['id'] . "\n";
            echo "   📝 Title: " . $response['data']['title'] . "\n";
            
            $eventId = $response['data']['id'];
            
            // Test 5.2: Delete event
            $deleteResponse = $this->makeRequest('DELETE', '/events/' . $eventId);
            
            if ($deleteResponse['success']) {
                $this->testResults['delete_event'] = 'PASS';
                echo "✅ Delete event: PASS\n";
            } else {
                $this->testResults['delete_event'] = 'FAIL';
                echo "❌ Delete event: FAIL\n";
            }
        } else {
            $this->testResults['add_event'] = 'FAIL';
            echo "❌ Add event: FAIL\n";
            if (isset($response['message'])) {
                echo "   Error: " . $response['message'] . "\n";
            }
        }
        
        echo "\n";
    }
    
    /**
     * Test 6: Add feature: Reminder popup
     */
    public function testReminderPopup() {
        echo "⏰ Testing: Reminder Popup\n";
        echo "-------------------------\n";
        
        // Test 6.1: Create event with reminder
        $eventData = [
            'title' => 'Reminder Test Event',
            'description' => 'Event with reminder for testing',
            'start_time' => date('Y-m-d\TH:i:s', strtotime('+5 minutes')),
            'end_time' => date('Y-m-d\TH:i:s', strtotime('+6 minutes')),
            'all_day' => false,
            'reminder' => [
                'enabled' => true,
                'type' => 'message',
                'time' => 1,
                'unit' => 'minutes',
                'relativeTo' => 'start'
            ]
        ];
        
        $response = $this->makeRequest('POST', '/events', $eventData);
        
        if ($response['success'] && isset($response['data']['reminder']) && $response['data']['reminder']['enabled']) {
            $this->testResults['reminder_popup'] = 'PASS';
            echo "✅ Reminder popup (event with reminder): PASS\n";
            echo "   ⏰ Reminder: " . $response['data']['reminder']['time'] . " " . $response['data']['reminder']['unit'] . " before\n";
        } else {
            $this->testResults['reminder_popup'] = 'FAIL';
            echo "❌ Reminder popup: FAIL\n";
        }
        
        echo "\n";
    }
    
    /**
     * Test 7: Add feature: Reload
     */
    public function testReload() {
        echo "🔄 Testing: Reload\n";
        echo "------------------\n";
        
        // Test 7.1: Test calendar reload
        $response = $this->makeRequest('GET', '/calendars');
        
        if ($response['success']) {
            $this->testResults['reload_calendars'] = 'PASS';
            echo "✅ Calendar reload: PASS\n";
        } else {
            $this->testResults['reload_calendars'] = 'FAIL';
            echo "❌ Calendar reload: FAIL\n";
        }
        
        // Test 7.2: Test events reload
        $response = $this->makeRequest('GET', '/events');
        
        if ($response['success']) {
            $this->testResults['reload_events'] = 'PASS';
            echo "✅ Events reload: PASS\n";
        } else {
            $this->testResults['reload_events'] = 'FAIL';
            echo "❌ Events reload: FAIL\n";
        }
        
        echo "\n";
    }
    
    /**
     * Make HTTP request
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, '');
        curl_setopt($ch, CURLOPT_COOKIEFILE, '');
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ]);
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => $error];
        }
        
        $decodedResponse = json_decode($response, true);
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'data' => $decodedResponse,
            'http_code' => $httpCode,
            'raw_response' => $response
        ];
    }
    
    /**
     * Print test summary
     */
    private function printSummary() {
        echo "📊 Test Summary\n";
        echo "===============\n";
        
        $totalTests = count($this->testResults);
        $passedTests = count(array_filter($this->testResults, function($result) {
            return $result === 'PASS';
        }));
        $failedTests = $totalTests - $passedTests;
        
        echo "Total Tests: $totalTests\n";
        echo "Passed: $passedTests ✅\n";
        echo "Failed: $failedTests ❌\n";
        echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";
        
        echo "Detailed Results:\n";
        echo "-----------------\n";
        
        foreach ($this->testResults as $test => $result) {
            $status = $result === 'PASS' ? '✅' : '❌';
            echo "$status " . ucwords(str_replace('_', ' ', $test)) . "\n";
        }
        
        echo "\n";
        
        if ($failedTests > 0) {
            echo "⚠️  Some tests failed. Check the logs above for details.\n";
        } else {
            echo "🎉 All tests passed! Your CalDAV Calendar App is working perfectly!\n";
        }
    }
}

// Run the tests
if (php_sapi_name() === 'cli') {
    $testSuite = new CalendarAppTestSuite();
    $testSuite->runAllTests();
} else {
    echo "This test suite should be run from the command line.\n";
    echo "Usage: php test/automated_tests.php\n";
}
?>
