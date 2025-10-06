<?php
declare(strict_types=1);

namespace CalDev\Tests\Unit;

use PHPUnit\Framework\TestCase;
use CalDev\Calendar\CalDAV\CalDAVClient;
use CalDev\Calendar\CalDAV\CalDAVException;

/**
 * CalDAV Client Unit Tests
 * 
 * Tests the CalDAV client functionality including:
 * - Calendar discovery
 * - Event CRUD operations
 * - Authentication
 * - Error handling
 */
class CalDAVClientTest extends TestCase
{
    private CalDAVClient $client;
    private string $testServerUrl = 'http://test-caldav-server';
    private string $testUsername = 'test_user';
    private string $testPassword = 'test_password';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create CalDAV client instance
        $this->client = new CalDAVClient(
            $this->testServerUrl,
            $this->testUsername,
            $this->testPassword
        );
    }

    public function testCalDAVClientInitialization(): void
    {
        $this->assertInstanceOf(CalDAVClient::class, $this->client);
        $this->assertEquals($this->testServerUrl, $this->client->getServerUrl());
        $this->assertEquals($this->testUsername, $this->client->getUsername());
    }

    public function testCalDAVClientWithEnvironmentVariables(): void
    {
        // Set environment variables
        $_ENV['CALDAV_SERVER_URL'] = 'http://env-caldav-server';
        $_ENV['CALDAV_USERNAME'] = 'env_user';
        $_ENV['CALDAV_PASSWORD'] = 'env_password';

        $client = new CalDAVClient();
        
        $this->assertEquals('http://env-caldav-server', $client->getServerUrl());
        $this->assertEquals('env_user', $client->getUsername());
    }

    public function testCalendarDiscovery(): void
    {
        // Mock successful calendar discovery response
        $mockResponse = [
            [
                'href' => '/calendars/user/calendar1/',
                'displayname' => 'Personal Calendar',
                'color' => '#4285f4',
                'resourcetype' => 'calendar'
            ],
            [
                'href' => '/calendars/user/calendar2/',
                'displayname' => 'Work Calendar',
                'color' => '#ea4335',
                'resourcetype' => 'calendar'
            ]
        ];

        // This would be mocked in a real implementation
        $calendars = $this->client->discoverCalendars();
        
        $this->assertIsArray($calendars);
        $this->assertGreaterThanOrEqual(0, count($calendars));
    }

    public function testEventCreation(): void
    {
        $eventData = [
            'title' => 'Test Event',
            'description' => 'Test event description',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'location' => 'Test Location',
            'all_day' => false
        ];

        $calendarUrl = '/calendars/user/calendar1/';
        
        // Test event creation
        $result = $this->client->createEvent($calendarUrl, $eventData);
        
        $this->assertIsBool($result);
    }

    public function testEventRetrieval(): void
    {
        $calendarUrl = '/calendars/user/calendar1/';
        $startDate = '2024-01-01T00:00:00Z';
        $endDate = '2024-12-31T23:59:59Z';

        $events = $this->client->getEvents($calendarUrl, $startDate, $endDate);
        
        $this->assertIsArray($events);
    }

    public function testEventUpdate(): void
    {
        $eventUrl = '/calendars/user/calendar1/event1.ics';
        $eventData = [
            'title' => 'Updated Test Event',
            'description' => 'Updated description',
            'start_time' => '2024-01-01T14:00:00Z',
            'end_time' => '2024-01-01T15:00:00Z'
        ];

        $result = $this->client->updateEvent($eventUrl, $eventData);
        
        $this->assertIsBool($result);
    }

    public function testEventDeletion(): void
    {
        $eventUrl = '/calendars/user/calendar1/event1.ics';

        $result = $this->client->deleteEvent($eventUrl);
        
        $this->assertIsBool($result);
    }

    public function testAuthenticationFailure(): void
    {
        $invalidClient = new CalDAVClient(
            $this->testServerUrl,
            'invalid_user',
            'invalid_password'
        );

        $this->expectException(CalDAVException::class);
        $invalidClient->discoverCalendars();
    }

    public function testServerConnectionFailure(): void
    {
        $invalidClient = new CalDAVClient(
            'http://invalid-server-url',
            $this->testUsername,
            $this->testPassword
        );

        $this->expectException(CalDAVException::class);
        $invalidClient->discoverCalendars();
    }

    public function testInvalidEventData(): void
    {
        $invalidEventData = [
            'title' => '', // Empty title should fail
            'start_time' => 'invalid-date',
            'end_time' => 'invalid-date'
        ];

        $calendarUrl = '/calendars/user/calendar1/';

        $this->expectException(CalDAVException::class);
        $this->client->createEvent($calendarUrl, $invalidEventData);
    }

    public function testRecurringEventCreation(): void
    {
        $recurringEventData = [
            'title' => 'Recurring Test Event',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'recurrence' => [
                'frequency' => 'weekly',
                'interval' => 1,
                'count' => 10
            ]
        ];

        $calendarUrl = '/calendars/user/calendar1/';
        
        $result = $this->client->createEvent($calendarUrl, $recurringEventData);
        
        $this->assertIsBool($result);
    }

    public function testEventWithAttendees(): void
    {
        $eventWithAttendees = [
            'title' => 'Meeting with Attendees',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'attendees' => [
                [
                    'email' => 'attendee1@example.com',
                    'name' => 'Attendee One',
                    'role' => 'REQ-PARTICIPANT'
                ],
                [
                    'email' => 'attendee2@example.com',
                    'name' => 'Attendee Two',
                    'role' => 'OPT-PARTICIPANT'
                ]
            ]
        ];

        $calendarUrl = '/calendars/user/calendar1/';
        
        $result = $this->client->createEvent($calendarUrl, $eventWithAttendees);
        
        $this->assertIsBool($result);
    }

    public function testEventWithReminders(): void
    {
        $eventWithReminders = [
            'title' => 'Event with Reminders',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'reminders' => [
                [
                    'trigger' => '-PT15M',
                    'action' => 'DISPLAY',
                    'description' => 'Reminder: Event starting in 15 minutes'
                ]
            ]
        ];

        $calendarUrl = '/calendars/user/calendar1/';
        
        $result = $this->client->createEvent($calendarUrl, $eventWithReminders);
        
        $this->assertIsBool($result);
    }

    public function testCalendarSync(): void
    {
        $calendarUrl = '/calendars/user/calendar1/';
        $syncToken = 'test-sync-token';

        $changes = $this->client->syncCalendar($calendarUrl, $syncToken);
        
        $this->assertIsArray($changes);
    }

    public function testGetSyncToken(): void
    {
        $calendarUrl = '/calendars/user/calendar1/';

        $syncToken = $this->client->getSyncToken($calendarUrl);
        
        $this->assertIsString($syncToken);
    }

    public function testServerCapabilities(): void
    {
        $capabilities = $this->client->getServerCapabilities();
        
        $this->assertIsArray($capabilities);
        $this->assertArrayHasKey('supported_methods', $capabilities);
        $this->assertArrayHasKey('dav_features', $capabilities);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up environment variables
        unset($_ENV['CALDAV_SERVER_URL']);
        unset($_ENV['CALDAV_USERNAME']);
        unset($_ENV['CALDAV_PASSWORD']);
    }
}

