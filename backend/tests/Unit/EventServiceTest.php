<?php
declare(strict_types=1);

namespace CalDev\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Mockery;
use CalDev\Calendar\Services\EventService;
use CalDev\Calendar\Repositories\EventRepositoryInterface;
use CalDev\Calendar\CalDAV\CalDAVServiceInterface;
use CalDev\Calendar\DTOs\EventDTO;

/**
 * Event Service Unit Tests
 * 
 * Tests the event service business logic including:
 * - Event creation and validation
 * - Event updates and modifications
 * - Event deletion
 * - Recurring event handling
 * - CalDAV synchronization
 */
class EventServiceTest extends TestCase
{
    private EventService $eventService;
    private $eventRepository;
    private $caldavService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->eventRepository = Mockery::mock(EventRepositoryInterface::class);
        $this->caldavService = Mockery::mock(CalDAVServiceInterface::class);
        
        // Create service instance
        $this->eventService = new EventService(
            $this->eventRepository,
            $this->caldavService
        );
    }

    public function testCreateEventSuccess(): void
    {
        // Arrange
        $eventDTO = new EventDTO([
            'title' => 'Test Event',
            'description' => 'Test event description',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'calendar_id' => 1,
            'location' => 'Test Location',
            'all_day' => false
        ]);

        $expectedEvent = [
            'id' => 1,
            'title' => 'Test Event',
            'description' => 'Test event description',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'calendar_id' => 1,
            'location' => 'Test Location',
            'all_day' => false,
            'uid' => 'test-uid-123',
            'etag' => 'test-etag-456'
        ];

        $this->eventRepository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn(1);

        $this->eventRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($expectedEvent);

        $this->caldavService
            ->shouldReceive('createEvent')
            ->once()
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->andReturn(true);

        // Act
        $result = $this->eventService->createEvent($eventDTO);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('Test Event', $result['title']);
        $this->assertEquals(1, $result['id']);
    }

    public function testCreateEventWithValidationFailure(): void
    {
        // Arrange
        $invalidEventDTO = new EventDTO([
            'title' => '', // Empty title should fail validation
            'start_time' => 'invalid-date',
            'end_time' => 'invalid-date',
            'calendar_id' => 1
        ]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->eventService->createEvent($invalidEventDTO);
    }

    public function testCreateRecurringEvent(): void
    {
        // Arrange
        $recurringEventDTO = new EventDTO([
            'title' => 'Recurring Event',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'calendar_id' => 1,
            'recurrence' => [
                'frequency' => 'weekly',
                'interval' => 1,
                'count' => 10
            ]
        ]);

        $this->eventRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn(1);

        $this->eventRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn(['id' => 1, 'title' => 'Recurring Event']);

        $this->caldavService
            ->shouldReceive('createEvent')
            ->once()
            ->andReturn(true);

        // Act
        $result = $this->eventService->createEvent($recurringEventDTO);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('Recurring Event', $result['title']);
    }

    public function testUpdateEventSuccess(): void
    {
        // Arrange
        $eventId = 1;
        $updateDTO = new EventDTO([
            'title' => 'Updated Event',
            'description' => 'Updated description',
            'start_time' => '2024-01-01T14:00:00Z',
            'end_time' => '2024-01-01T15:00:00Z',
            'calendar_id' => 1
        ]);

        $existingEvent = [
            'id' => 1,
            'title' => 'Original Event',
            'calendar_id' => 1,
            'uid' => 'test-uid-123'
        ];

        $this->eventRepository
            ->shouldReceive('findById')
            ->once()
            ->with($eventId)
            ->andReturn($existingEvent);

        $this->eventRepository
            ->shouldReceive('update')
            ->once()
            ->with($eventId, Mockery::type('array'))
            ->andReturn(true);

        $this->caldavService
            ->shouldReceive('updateEvent')
            ->once()
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->andReturn(true);

        // Act
        $result = $this->eventService->updateEvent($eventId, $updateDTO);

        // Assert
        $this->assertTrue($result);
    }

    public function testUpdateNonExistentEvent(): void
    {
        // Arrange
        $eventId = 999;
        $updateDTO = new EventDTO([
            'title' => 'Updated Event',
            'calendar_id' => 1
        ]);

        $this->eventRepository
            ->shouldReceive('findById')
            ->once()
            ->with($eventId)
            ->andReturn(null);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->eventService->updateEvent($eventId, $updateDTO);
    }

    public function testDeleteEventSuccess(): void
    {
        // Arrange
        $eventId = 1;
        $existingEvent = [
            'id' => 1,
            'title' => 'Event to Delete',
            'calendar_id' => 1,
            'uid' => 'test-uid-123'
        ];

        $this->eventRepository
            ->shouldReceive('findById')
            ->once()
            ->with($eventId)
            ->andReturn($existingEvent);

        $this->eventRepository
            ->shouldReceive('delete')
            ->once()
            ->with($eventId)
            ->andReturn(true);

        $this->caldavService
            ->shouldReceive('deleteEvent')
            ->once()
            ->with(Mockery::type('string'))
            ->andReturn(true);

        // Act
        $result = $this->eventService->deleteEvent($eventId);

        // Assert
        $this->assertTrue($result);
    }

    public function testDeleteNonExistentEvent(): void
    {
        // Arrange
        $eventId = 999;

        $this->eventRepository
            ->shouldReceive('findById')
            ->once()
            ->with($eventId)
            ->andReturn(null);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->eventService->deleteEvent($eventId);
    }

    public function testGetEventsByCalendar(): void
    {
        // Arrange
        $calendarId = 1;
        $startDate = '2024-01-01T00:00:00Z';
        $endDate = '2024-12-31T23:59:59Z';

        $expectedEvents = [
            [
                'id' => 1,
                'title' => 'Event 1',
                'start_time' => '2024-01-01T10:00:00Z',
                'end_time' => '2024-01-01T11:00:00Z',
                'calendar_id' => 1
            ],
            [
                'id' => 2,
                'title' => 'Event 2',
                'start_time' => '2024-01-02T10:00:00Z',
                'end_time' => '2024-01-02T11:00:00Z',
                'calendar_id' => 1
            ]
        ];

        $this->eventRepository
            ->shouldReceive('findByCalendarId')
            ->once()
            ->with($calendarId, $startDate, $endDate)
            ->andReturn($expectedEvents);

        // Act
        $result = $this->eventService->getEventsByCalendar($calendarId, $startDate, $endDate);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Event 1', $result[0]['title']);
    }

    public function testGetEventsByUser(): void
    {
        // Arrange
        $userId = 1;
        $startDate = '2024-01-01T00:00:00Z';
        $endDate = '2024-12-31T23:59:59Z';

        $expectedEvents = [
            [
                'id' => 1,
                'title' => 'User Event 1',
                'calendar_id' => 1
            ]
        ];

        $this->eventRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->with($userId, $startDate, $endDate)
            ->andReturn($expectedEvents);

        // Act
        $result = $this->eventService->getEventsByUser($userId, $startDate, $endDate);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('User Event 1', $result[0]['title']);
    }

    public function testSearchEvents(): void
    {
        // Arrange
        $userId = 1;
        $query = 'meeting';

        $expectedEvents = [
            [
                'id' => 1,
                'title' => 'Team Meeting',
                'description' => 'Weekly team meeting',
                'calendar_id' => 1
            ]
        ];

        $this->eventRepository
            ->shouldReceive('search')
            ->once()
            ->with($userId, $query)
            ->andReturn($expectedEvents);

        // Act
        $result = $this->eventService->searchEvents($userId, $query);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Team Meeting', $result[0]['title']);
    }

    public function testHandleCalDAVSyncFailure(): void
    {
        // Arrange
        $eventDTO = new EventDTO([
            'title' => 'Test Event',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'calendar_id' => 1
        ]);

        $this->eventRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn(1);

        $this->eventRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn(['id' => 1, 'title' => 'Test Event']);

        $this->caldavService
            ->shouldReceive('createEvent')
            ->once()
            ->andThrow(new \Exception('CalDAV sync failed'));

        // Act
        $result = $this->eventService->createEvent($eventDTO);

        // Assert - Event should still be created locally even if CalDAV sync fails
        $this->assertIsArray($result);
        $this->assertEquals('Test Event', $result['title']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

