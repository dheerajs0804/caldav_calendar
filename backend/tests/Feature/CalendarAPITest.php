<?php
declare(strict_types=1);

namespace CalDev\Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Calendar API Feature Tests
 * 
 * Tests the complete API functionality including:
 * - HTTP endpoints
 * - Request/response handling
 * - Authentication
 * - Error handling
 * - Data validation
 */
class CalendarAPITest extends TestCase
{
    private string $baseUrl;
    private array $testSession;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->baseUrl = 'http://localhost:8000';
        $this->testSession = [];
        
        // Start session for testing
        session_start();
    }

    public function testHealthEndpoint(): void
    {
        // Act
        $response = $this->makeRequest('GET', '/health');
        
        // Assert
        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('status', $response['data']);
        $this->assertEquals('OK', $response['data']['status']);
    }

    public function testAuthenticationFlow(): void
    {
        // Test login endpoint
        $loginData = [
            'username' => 'test_user',
            'password' => 'test_password'
        ];

        $response = $this->makeRequest('POST', '/auth/login', $loginData);
        
        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('success', $response['data']);
        $this->assertTrue($response['data']['success']);
    }

    public function testAuthenticationFailure(): void
    {
        // Test invalid credentials
        $invalidData = [
            'username' => 'invalid_user',
            'password' => 'invalid_password'
        ];

        $response = $this->makeRequest('POST', '/auth/login', $invalidData);
        
        $this->assertEquals(401, $response['status_code']);
        $this->assertArrayHasKey('success', $response['data']);
        $this->assertFalse($response['data']['success']);
    }

    public function testGetCalendarsEndpoint(): void
    {
        // Arrange - Authenticate first
        $this->authenticateUser();

        // Act
        $response = $this->makeRequest('GET', '/calendars/user');
        
        // Assert
        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('success', $response['data']);
        $this->assertTrue($response['data']['success']);
        $this->assertArrayHasKey('data', $response['data']);
        $this->assertIsArray($response['data']['data']);
    }

    public function testCreateCalendarEndpoint(): void
    {
        // Arrange - Authenticate first
        $this->authenticateUser();

        $calendarData = [
            'name' => 'API Test Calendar',
            'color' => '#4285f4',
            'url' => 'http://test-caldav-server/calendars/api-test/'
        ];

        // Act
        $response = $this->makeRequest('POST', '/calendars', $calendarData);
        
        // Assert
        $this->assertEquals(201, $response['status_code']);
        $this->assertArrayHasKey('success', $response['data']);
        $this->assertTrue($response['data']['success']);
        $this->assertArrayHasKey('data', $response['data']);
        $this->assertEquals('API Test Calendar', $response['data']['data']['name']);
    }

    public function testCreateCalendarValidation(): void
    {
        // Arrange - Authenticate first
        $this->authenticateUser();

        $invalidData = [
            'name' => '', // Empty name should fail
            'url' => 'invalid-url' // Invalid URL should fail
        ];

        // Act
        $response = $this->makeRequest('POST', '/calendars', $invalidData);
        
        // Assert
        $this->assertEquals(400, $response['status_code']);
        $this->assertArrayHasKey('success', $response['data']);
        $this->assertFalse($response['data']['success']);
        $this->assertArrayHasKey('error', $response['data']);
    }

    public function testUpdateCalendarEndpoint(): void
    {
        // Arrange - Authenticate and create calendar
        $this->authenticateUser();
        $calendar = $this->createTestCalendar();

        $updateData = [
            'name' => 'Updated API Test Calendar',
            'color' => '#ea4335'
        ];

        // Act
        $response = $this->makeRequest('PUT', "/calendars/{$calendar['id']}/toggle", $updateData);
        
        // Assert
        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('success', $response['data']);
        $this->assertTrue($response['data']['success']);
    }

    public function testDeleteCalendarEndpoint(): void
    {
        // Arrange - Authenticate and create calendar
        $this->authenticateUser();
        $calendar = $this->createTestCalendar();

        // Act
        $response = $this->makeRequest('DELETE', "/calendars/{$calendar['id']}");
        
        // Assert
        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('success', $response['data']);
        $this->assertTrue($response['data']['success']);
    }

    public function testGetEventsEndpoint(): void
    {
        // Arrange - Authenticate and create calendar
        $this->authenticateUser();
        $calendar = $this->createTestCalendar();

        // Act
        $response = $this->makeRequest('GET', "/events?calendar_url=" . urlencode($calendar['url']));
        
        // Assert
        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('success', $response['data']);
        $this->assertTrue($response['data']['success']);
        $this->assertArrayHasKey('data', $response['data']);
        $this->assertIsArray($response['data']['data']);
    }

    public function testCreateEventEndpoint(): void
    {
        // Arrange - Authenticate and create calendar
        $this->authenticateUser();
        $calendar = $this->createTestCalendar();

        $eventData = [
            'title' => 'API Test Event',
            'description' => 'Test event created via API',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'calendar_id' => $calendar['id'],
            'location' => 'Test Location',
            'all_day' => false
        ];

        // Act
        $response = $this->makeRequest('POST', '/events', $eventData);
        
        // Assert
        $this->assertEquals(201, $response['status_code']);
        $this->assertArrayHasKey('success', $response['data']);
        $this->assertTrue($response['data']['success']);
        $this->assertArrayHasKey('data', $response['data']);
        $this->assertEquals('API Test Event', $response['data']['data']['title']);
    }

    public function testUpdateEventEndpoint(): void
    {
        // Arrange - Authenticate, create calendar and event
        $this->authenticateUser();
        $calendar = $this->createTestCalendar();
        $event = $this->createTestEvent($calendar['id']);

        $updateData = [
            'title' => 'Updated API Test Event',
            'description' => 'Updated test event',
            'start_time' => '2024-01-01T14:00:00Z',
            'end_time' => '2024-01-01T15:00:00Z'
        ];

        // Act
        $response = $this->makeRequest('PUT', "/events/{$event['id']}", $updateData);
        
        // Assert
        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('success', $response['data']);
        $this->assertTrue($response['data']['success']);
    }

    public function testDeleteEventEndpoint(): void
    {
        // Arrange - Authenticate, create calendar and event
        $this->authenticateUser();
        $calendar = $this->createTestCalendar();
        $event = $this->createTestEvent($calendar['id']);

        // Act
        $response = $this->makeRequest('DELETE', "/events/{$event['id']}?calendar_url=" . urlencode($calendar['url']));
        
        // Assert
        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('success', $response['data']);
        $this->assertTrue($response['data']['success']);
    }

    public function testUnauthorizedAccess(): void
    {
        // Test accessing protected endpoint without authentication
        $response = $this->makeRequest('GET', '/calendars/user');
        
        $this->assertEquals(401, $response['status_code']);
        $this->assertArrayHasKey('success', $response['data']);
        $this->assertFalse($response['data']['success']);
    }

    public function testNotFoundEndpoint(): void
    {
        // Test accessing non-existent endpoint
        $response = $this->makeRequest('GET', '/nonexistent');
        
        $this->assertEquals(404, $response['status_code']);
        $this->assertArrayHasKey('error', $response['data']);
    }

    public function testCORSHeaders(): void
    {
        // Test CORS preflight request
        $response = $this->makeRequest('OPTIONS', '/calendars/user');
        
        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('Access-Control-Allow-Origin', $response['headers']);
        $this->assertArrayHasKey('Access-Control-Allow-Methods', $response['headers']);
    }

    public function testRateLimiting(): void
    {
        // Test rate limiting by making many requests
        $this->authenticateUser();
        
        $requestCount = 0;
        $rateLimited = false;
        
        for ($i = 0; $i < 100; $i++) {
            $response = $this->makeRequest('GET', '/calendars/user');
            $requestCount++;
            
            if ($response['status_code'] === 429) {
                $rateLimited = true;
                break;
            }
        }
        
        // Should eventually hit rate limit
        $this->assertTrue($rateLimited || $requestCount >= 100);
    }

    public function testErrorHandling(): void
    {
        // Test malformed JSON request
        $response = $this->makeRequest('POST', '/events', 'invalid-json', false);
        
        $this->assertEquals(400, $response['status_code']);
        $this->assertArrayHasKey('error', $response['data']);
    }

    private function makeRequest(string $method, string $endpoint, $data = null, bool $json = true): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_COOKIE => $this->getSessionCookie(),
            CURLOPT_TIMEOUT => 30
        ]);

        if ($data !== null) {
            $postData = $json ? json_encode($data) : $data;
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        
        curl_close($ch);

        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        
        return [
            'status_code' => $httpCode,
            'headers' => $this->parseHeaders($headers),
            'data' => json_decode($body, true) ?: $body
        ];
    }

    private function parseHeaders(string $headerString): array
    {
        $headers = [];
        $lines = explode("\r\n", $headerString);
        
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        
        return $headers;
    }

    private function getSessionCookie(): string
    {
        return 'PHPSESSID=' . session_id();
    }

    private function authenticateUser(): void
    {
        $loginData = [
            'username' => 'test_user',
            'password' => 'test_password'
        ];

        $response = $this->makeRequest('POST', '/auth/login', $loginData);
        
        if ($response['status_code'] !== 200) {
            $this->fail('Authentication failed');
        }
    }

    private function createTestCalendar(): array
    {
        $calendarData = [
            'name' => 'Test Calendar',
            'color' => '#4285f4',
            'url' => 'http://test-caldav-server/calendars/test/'
        ];

        $response = $this->makeRequest('POST', '/calendars', $calendarData);
        
        if ($response['status_code'] !== 201) {
            $this->fail('Failed to create test calendar');
        }

        return $response['data']['data'];
    }

    private function createTestEvent(int $calendarId): array
    {
        $eventData = [
            'title' => 'Test Event',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'calendar_id' => $calendarId
        ];

        $response = $this->makeRequest('POST', '/events', $eventData);
        
        if ($response['status_code'] !== 201) {
            $this->fail('Failed to create test event');
        }

        return $response['data']['data'];
    }

    protected function tearDown(): void
    {
        session_destroy();
        parent::tearDown();
    }
}

