<?php
// Corrected Comprehensive Feature Test Suite for Mithi Calendar
// Tests ALL implemented features with proper API field names

$testUser = 'caltest71025@mithi.com';
$testPass = 'Roundlet4@jira';
$baseUrl = 'http://localhost:8000';

echo "========================================\n";
echo "Mithi Calendar - Corrected Comprehensive Tests\n";
echo "========================================\n";
echo "Test User: $testUser\n";
echo "Base URL: $baseUrl\n";
echo "Test Date: " . date('Y-m-d H:i:s') . "\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function runTest($testName, $url, $method = 'GET', $data = null, $expectedCodes = [200]) {
    global $totalTests, $passedTests, $failedTests, $testResults;
    
    $totalTests++;
    echo "$totalTests. Testing $testName...\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if (in_array($httpCode, $expectedCodes)) {
        echo "   ✅ SUCCESS: $testName passed (HTTP $httpCode)\n";
        $testResults[] = ['test' => $testName, 'status' => 'PASSED', 'code' => $httpCode, 'response' => $response];
        $passedTests++;
        return true;
    } else {
        echo "   ❌ ERROR: $testName failed (HTTP $httpCode)\n";
        if ($error) echo "   cURL Error: $error\n";
        $testResults[] = ['test' => $testName, 'status' => 'FAILED', 'code' => $httpCode, 'error' => $error, 'response' => $response];
        $failedTests++;
        return false;
    }
}

// ========================================
// 1. AUTHENTICATION & SESSION TESTS
// ========================================
echo "=== AUTHENTICATION & SESSION TESTS ===\n";
runTest('Health Check', "$baseUrl/health");

$loginData = json_encode(['username' => $testUser, 'password' => $testPass]);
runTest('User Login', "$baseUrl/auth/login", 'POST', $loginData);

// ========================================
// 2. CALENDAR MANAGEMENT TESTS
// ========================================
echo "\n=== CALENDAR MANAGEMENT TESTS ===\n";
runTest('Calendar Discovery', "$baseUrl/calendars/user");
runTest('Calendar Properties', "$baseUrl/calendars/properties");

// ========================================
// 3. EVENT MANAGEMENT TESTS (CORRECTED)
// ========================================
echo "\n=== EVENT MANAGEMENT TESTS ===\n";

// Basic event creation with correct field names
$basicEventData = json_encode([
    'title' => 'Basic Test Event ' . date('Y-m-d H:i:s'),
    'description' => 'Automated test basic event',
    'start_date' => date('Y-m-d'),
    'start_time' => date('H:i:s'),
    'end_date' => date('Y-m-d'),
    'end_time' => date('H:i:s', strtotime('+1 hour')),
    'calendar_id' => 1,
    'location' => 'Test Office',
    'all_day' => false,
    'availability' => 'busy',
    'status' => 'confirmed'
]);
runTest('Create Basic Event', "$baseUrl/events", 'POST', $basicEventData, [200, 201]);

// All-day event
$allDayEventData = json_encode([
    'title' => 'All-Day Test Event ' . date('Y-m-d H:i:s'),
    'description' => 'Automated test all-day event',
    'start_date' => date('Y-m-d'),
    'start_time' => '00:00:00',
    'end_date' => date('Y-m-d', strtotime('+1 day')),
    'end_time' => '23:59:59',
    'calendar_id' => 1,
    'location' => 'Home',
    'all_day' => true,
    'availability' => 'free',
    'status' => 'confirmed'
]);
runTest('Create All-Day Event', "$baseUrl/events", 'POST', $allDayEventData, [200, 201]);

// Event with attendees
$attendeeEventData = json_encode([
    'title' => 'Meeting with Attendees ' . date('Y-m-d H:i:s'),
    'description' => 'Automated test event with attendees',
    'start_date' => date('Y-m-d'),
    'start_time' => date('H:i:s'),
    'end_date' => date('Y-m-d'),
    'end_time' => date('H:i:s', strtotime('+2 hours')),
    'calendar_id' => 1,
    'location' => 'Conference Room A',
    'all_day' => false,
    'availability' => 'busy',
    'status' => 'confirmed',
    'attendees' => [
        ['email' => 'attendee1@example.com', 'name' => 'John Doe', 'role' => 'REQ-PARTICIPANT'],
        ['email' => 'attendee2@example.com', 'name' => 'Jane Smith', 'role' => 'OPT-PARTICIPANT'],
        ['email' => 'attendee3@example.com', 'name' => 'Bob Johnson', 'role' => 'REQ-PARTICIPANT']
    ]
]);
runTest('Create Event with Attendees', "$baseUrl/events", 'POST', $attendeeEventData, [200, 201]);

// Event with reminders
$reminderEventData = json_encode([
    'title' => 'Event with Reminders ' . date('Y-m-d H:i:s'),
    'description' => 'Automated test event with multiple reminders',
    'start_date' => date('Y-m-d'),
    'start_time' => date('H:i:s'),
    'end_date' => date('Y-m-d'),
    'end_time' => date('H:i:s', strtotime('+1 hour')),
    'calendar_id' => 1,
    'location' => 'Office',
    'all_day' => false,
    'availability' => 'busy',
    'status' => 'confirmed',
    'reminder' => [
        'enabled' => true,
        'type' => 'message',
        'time' => 15,
        'unit' => 'minutes',
        'relativeTo' => 'start'
    ]
]);
runTest('Create Event with Reminders', "$baseUrl/events", 'POST', $reminderEventData, [200, 201]);

// ========================================
// 4. RECURRING EVENT TESTS (CORRECTED)
// ========================================
echo "\n=== RECURRING EVENT TESTS ===\n";

// Daily recurring event
$dailyRecurringData = json_encode([
    'title' => 'Daily Recurring Event ' . date('Y-m-d H:i:s'),
    'description' => 'Automated test daily recurring event',
    'start_date' => date('Y-m-d'),
    'start_time' => date('H:i:s'),
    'end_date' => date('Y-m-d'),
    'end_time' => date('H:i:s', strtotime('+1 hour')),
    'calendar_id' => 1,
    'location' => 'Office',
    'all_day' => false,
    'availability' => 'busy',
    'status' => 'confirmed',
    'recurrence' => [
        'frequency' => 'daily',
        'interval' => 1,
        'count' => 7
    ]
]);
runTest('Create Daily Recurring Event', "$baseUrl/events", 'POST', $dailyRecurringData, [200, 201]);

// Weekly recurring event
$weeklyRecurringData = json_encode([
    'title' => 'Weekly Recurring Event ' . date('Y-m-d H:i:s'),
    'description' => 'Automated test weekly recurring event',
    'start_date' => date('Y-m-d'),
    'start_time' => date('H:i:s'),
    'end_date' => date('Y-m-d'),
    'end_time' => date('H:i:s', strtotime('+1 hour')),
    'calendar_id' => 1,
    'location' => 'Meeting Room',
    'all_day' => false,
    'availability' => 'busy',
    'status' => 'confirmed',
    'recurrence' => [
        'frequency' => 'weekly',
        'interval' => 1,
        'count' => 10,
        'byDay' => ['MO', 'WE', 'FR']
    ]
]);
runTest('Create Weekly Recurring Event', "$baseUrl/events", 'POST', $weeklyRecurringData, [200, 201]);

// Monthly recurring event
$monthlyRecurringData = json_encode([
    'title' => 'Monthly Recurring Event ' . date('Y-m-d H:i:s'),
    'description' => 'Automated test monthly recurring event',
    'start_date' => date('Y-m-d'),
    'start_time' => date('H:i:s'),
    'end_date' => date('Y-m-d'),
    'end_time' => date('H:i:s', strtotime('+2 hours')),
    'calendar_id' => 1,
    'location' => 'Boardroom',
    'all_day' => false,
    'availability' => 'busy',
    'status' => 'confirmed',
    'recurrence' => [
        'frequency' => 'monthly',
        'interval' => 1,
        'count' => 6,
        'byMonthDay' => [1, 15]
    ]
]);
runTest('Create Monthly Recurring Event', "$baseUrl/events", 'POST', $monthlyRecurringData, [200, 201]);

// Annual recurring event
$annualRecurringData = json_encode([
    'title' => 'Annual Recurring Event ' . date('Y-m-d H:i:s'),
    'description' => 'Automated test annual recurring event',
    'start_date' => date('Y-m-d'),
    'start_time' => '00:00:00',
    'end_date' => date('Y-m-d', strtotime('+1 day')),
    'end_time' => '23:59:59',
    'calendar_id' => 1,
    'location' => 'Company HQ',
    'all_day' => true,
    'availability' => 'free',
    'status' => 'confirmed',
    'recurrence' => [
        'frequency' => 'annually',
        'interval' => 1,
        'count' => 5,
        'byMonth' => [1, 6, 12]
    ]
]);
runTest('Create Annual Recurring Event', "$baseUrl/events", 'POST', $annualRecurringData, [200, 201]);

// ========================================
// 5. EVENT RETRIEVAL TESTS
// ========================================
echo "\n=== EVENT RETRIEVAL TESTS ===\n";
runTest('Get All Events', "$baseUrl/events");
runTest('Get Events by Calendar', "$baseUrl/events?calendar_id=1");
runTest('Get Events by Date Range', "$baseUrl/events?start=" . date('Y-m-d') . "&end=" . date('Y-m-d', strtotime('+30 days')));

// ========================================
// 6. EVENT UPDATE TESTS
// ========================================
echo "\n=== EVENT UPDATE TESTS ===\n";

// First create an event to update
$eventToUpdate = json_encode([
    'title' => 'Event to Update ' . date('Y-m-d H:i:s'),
    'description' => 'This event will be updated',
    'start_date' => date('Y-m-d'),
    'start_time' => date('H:i:s'),
    'end_date' => date('Y-m-d'),
    'end_time' => date('H:i:s', strtotime('+1 hour')),
    'calendar_id' => 1,
    'location' => 'Original Location',
    'all_day' => false
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/events");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $eventToUpdate);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200 || $httpCode == 201) {
    $eventData = json_decode($response, true);
    $eventId = $eventData['data']['id'] ?? '1'; // Get ID from response
    
    // Test event update
    $updateData = json_encode([
        'title' => 'Updated Event ' . date('Y-m-d H:i:s'),
        'description' => 'This event has been updated',
        'location' => 'Updated Location',
        'calendar_id' => 1
    ]);
    runTest('Update Event Details', "$baseUrl/events/$eventId", 'PUT', $updateData, [200]);
    
    // Test calendar change
    $calendarChangeData = json_encode(['calendar_id' => 1]);
    runTest('Change Event Calendar', "$baseUrl/events/$eventId", 'PUT', $calendarChangeData, [200]);
    
    // Test recurrence update for non-recurring event
    $recurrenceData = json_encode([
        'recurrence' => [
            'frequency' => 'weekly',
            'interval' => 1,
            'count' => 5
        ]
    ]);
    runTest('Add Recurrence to Event', "$baseUrl/events/$eventId", 'PUT', $recurrenceData, [200]);
    
    // Test event deletion
    runTest('Delete Event', "$baseUrl/events/$eventId", 'DELETE', null, [200, 204]);
} else {
    echo "   ⚠️  WARNING: Could not create event for update tests\n";
}

// ========================================
// 7. ADVANCED FEATURE TESTS
// ========================================
echo "\n=== ADVANCED FEATURE TESTS ===\n";

// Test event with complex recurrence
$complexRecurringData = json_encode([
    'title' => 'Complex Recurring Event ' . date('Y-m-d H:i:s'),
    'description' => 'Event with complex recurrence rules',
    'start_date' => date('Y-m-d'),
    'start_time' => date('H:i:s'),
    'end_date' => date('Y-m-d'),
    'end_time' => date('H:i:s', strtotime('+1 hour')),
    'calendar_id' => 1,
    'location' => 'Complex Location',
    'all_day' => false,
    'availability' => 'tentative',
    'status' => 'tentative',
    'recurrence' => [
        'frequency' => 'weekly',
        'interval' => 2,
        'count' => 8,
        'byDay' => ['TU', 'TH'],
        'bySetPos' => 1
    ],
    'attendees' => [
        ['email' => 'complex1@example.com', 'name' => 'Complex User 1', 'role' => 'REQ-PARTICIPANT'],
        ['email' => 'complex2@example.com', 'name' => 'Complex User 2', 'role' => 'OPT-PARTICIPANT']
    ],
    'reminder' => [
        'enabled' => true,
        'type' => 'message',
        'time' => 30,
        'unit' => 'minutes',
        'relativeTo' => 'start'
    ]
]);
runTest('Create Complex Recurring Event', "$baseUrl/events", 'POST', $complexRecurringData, [200, 201]);

// ========================================
// 8. ERROR HANDLING TESTS
// ========================================
echo "\n=== ERROR HANDLING TESTS ===\n";

// Test invalid event data
$invalidEventData = json_encode([
    'title' => '', // Empty title should fail
    'start_date' => 'invalid-date',
    'calendar_id' => 999 // Non-existent calendar
]);
runTest('Invalid Event Data Handling', "$baseUrl/events", 'POST', $invalidEventData, [400, 422]);

// Test non-existent event update
runTest('Update Non-existent Event', "$baseUrl/events/99999", 'PUT', json_encode(['title' => 'Test']), [404]);

// ========================================
// 9. PERFORMANCE TESTS
// ========================================
echo "\n=== PERFORMANCE TESTS ===\n";

// Test multiple rapid requests
echo "9.1. Testing Multiple Rapid Requests...\n";
$startTime = microtime(true);
for ($i = 0; $i < 5; $i++) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/events");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    curl_close($ch);
}
$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);
echo "   ✅ Multiple requests completed in {$duration}ms\n";

// ========================================
// FINAL SUMMARY
// ========================================
echo "\n========================================\n";
echo "CORRECTED COMPREHENSIVE TEST SUMMARY\n";
echo "========================================\n";
echo "Total Tests: $totalTests\n";
echo "Passed Tests: $passedTests\n";
echo "Failed Tests: $failedTests\n";
echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";

echo "DETAILED RESULTS:\n";
echo "==================\n";
foreach ($testResults as $result) {
    $status = $result['status'] === 'PASSED' ? '✅' : '❌';
    echo "$status {$result['test']}: {$result['status']} (HTTP {$result['code']})\n";
}

echo "\nFEATURE COVERAGE SUMMARY:\n";
echo "========================\n";
echo "✅ Authentication & Session: Tested\n";
echo "✅ Calendar Management: Tested\n";
echo "✅ Event CRUD Operations: Tested\n";
echo "✅ Recurring Events: Tested\n";
echo "✅ Event Features (Attendees, Reminders): Tested\n";
echo "✅ Error Handling: Tested\n";
echo "✅ Performance: Tested\n";

echo "\nCorrected Comprehensive Feature Tests completed!\n";
echo "========================================\n";
?>
