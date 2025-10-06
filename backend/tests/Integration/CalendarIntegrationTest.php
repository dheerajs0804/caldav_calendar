<?php
declare(strict_types=1);

namespace CalDev\Tests\Integration;

use PHPUnit\Framework\TestCase;
use CalDev\Calendar\CalDAV\CalDAVClient;
use CalDev\Calendar\Services\CalendarService;
use CalDev\Calendar\Repositories\CalendarRepository;

/**
 * Calendar Integration Tests
 * 
 * Tests the integration between different components:
 * - Calendar service and repository
 * - CalDAV client integration
 * - End-to-end calendar operations
 */
class CalendarIntegrationTest extends TestCase
{
    private CalendarService $calendarService;
    private CalendarRepository $calendarRepository;
    private CalDAVClient $caldavClient;
    private string $testDataDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testDataDir = TEST_ROOT . '/data';
        
        // Create test data directory
        if (!is_dir($this->testDataDir)) {
            mkdir($this->testDataDir, 0755, true);
        }
        
        // Initialize test data files
        $this->initializeTestData();
        
        // Create repository instance
        $this->calendarRepository = new CalendarRepository($this->testDataDir);
        
        // Create CalDAV client (mocked for integration tests)
        $this->caldavClient = new CalDAVClient(
            'http://test-caldav-server',
            'test_user',
            'test_password'
        );
        
        // Create service instance
        $this->calendarService = new CalendarService(
            $this->calendarRepository,
            $this->caldavClient
        );
    }

    public function testCreateCalendarIntegration(): void
    {
        // Arrange
        $calendarData = [
            'name' => 'Integration Test Calendar',
            'color' => '#4285f4',
            'url' => 'http://test-caldav-server/calendars/test/',
            'user_id' => 1
        ];

        // Act
        $result = $this->calendarService->createCalendar($calendarData);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('Integration Test Calendar', $result['name']);
        $this->assertEquals('#4285f4', $result['color']);
        $this->assertEquals(1, $result['user_id']);
    }

    public function testGetCalendarsByUser(): void
    {
        // Arrange - Create multiple calendars
        $calendar1 = $this->calendarService->createCalendar([
            'name' => 'Calendar 1',
            'color' => '#4285f4',
            'url' => 'http://test-caldav-server/calendars/1/',
            'user_id' => 1
        ]);

        $calendar2 = $this->calendarService->createCalendar([
            'name' => 'Calendar 2',
            'color' => '#ea4335',
            'url' => 'http://test-caldav-server/calendars/2/',
            'user_id' => 1
        ]);

        // Act
        $calendars = $this->calendarService->getCalendarsByUser(1);

        // Assert
        $this->assertIsArray($calendars);
        $this->assertCount(2, $calendars);
        
        $calendarNames = array_column($calendars, 'name');
        $this->assertContains('Calendar 1', $calendarNames);
        $this->assertContains('Calendar 2', $calendarNames);
    }

    public function testUpdateCalendarIntegration(): void
    {
        // Arrange
        $calendar = $this->calendarService->createCalendar([
            'name' => 'Original Calendar',
            'color' => '#4285f4',
            'url' => 'http://test-caldav-server/calendars/original/',
            'user_id' => 1
        ]);

        $updateData = [
            'name' => 'Updated Calendar',
            'color' => '#ea4335'
        ];

        // Act
        $result = $this->calendarService->updateCalendar($calendar['id'], $updateData);

        // Assert
        $this->assertTrue($result);
        
        $updatedCalendar = $this->calendarService->getCalendarById($calendar['id']);
        $this->assertEquals('Updated Calendar', $updatedCalendar['name']);
        $this->assertEquals('#ea4335', $updatedCalendar['color']);
    }

    public function testDeleteCalendarIntegration(): void
    {
        // Arrange
        $calendar = $this->calendarService->createCalendar([
            'name' => 'Calendar to Delete',
            'color' => '#4285f4',
            'url' => 'http://test-caldav-server/calendars/delete/',
            'user_id' => 1
        ]);

        // Act
        $result = $this->calendarService->deleteCalendar($calendar['id']);

        // Assert
        $this->assertTrue($result);
        
        $deletedCalendar = $this->calendarService->getCalendarById($calendar['id']);
        $this->assertNull($deletedCalendar);
    }

    public function testCalendarSyncIntegration(): void
    {
        // Arrange
        $calendar = $this->calendarService->createCalendar([
            'name' => 'Sync Test Calendar',
            'color' => '#4285f4',
            'url' => 'http://test-caldav-server/calendars/sync/',
            'user_id' => 1
        ]);

        // Act
        $syncResult = $this->calendarService->syncCalendar($calendar['id']);

        // Assert
        $this->assertIsArray($syncResult);
        $this->assertArrayHasKey('success', $syncResult);
        $this->assertArrayHasKey('events_processed', $syncResult);
    }

    public function testCalendarColorManagement(): void
    {
        // Arrange
        $calendar = $this->calendarService->createCalendar([
            'name' => 'Color Test Calendar',
            'color' => '#4285f4',
            'url' => 'http://test-caldav-server/calendars/color/',
            'user_id' => 1
        ]);

        // Act - Update calendar color
        $this->calendarService->updateCalendarColor($calendar['id'], '#ea4335');

        // Assert
        $updatedCalendar = $this->calendarService->getCalendarById($calendar['id']);
        $this->assertEquals('#ea4335', $updatedCalendar['color']);
    }

    public function testCalendarStateManagement(): void
    {
        // Arrange
        $calendar = $this->calendarService->createCalendar([
            'name' => 'State Test Calendar',
            'color' => '#4285f4',
            'url' => 'http://test-caldav-server/calendars/state/',
            'user_id' => 1
        ]);

        // Act - Disable calendar
        $this->calendarService->toggleCalendar($calendar['id'], false);

        // Assert
        $disabledCalendar = $this->calendarService->getCalendarById($calendar['id']);
        $this->assertFalse($disabledCalendar['enabled']);

        // Act - Enable calendar
        $this->calendarService->toggleCalendar($calendar['id'], true);

        // Assert
        $enabledCalendar = $this->calendarService->getCalendarById($calendar['id']);
        $this->assertTrue($enabledCalendar['enabled']);
    }

    public function testCalendarValidation(): void
    {
        // Test invalid calendar data
        $invalidData = [
            'name' => '', // Empty name
            'url' => 'invalid-url', // Invalid URL
            'user_id' => 1
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->calendarService->createCalendar($invalidData);
    }

    public function testCalendarDuplicatePrevention(): void
    {
        // Arrange
        $calendarData = [
            'name' => 'Duplicate Test Calendar',
            'color' => '#4285f4',
            'url' => 'http://test-caldav-server/calendars/duplicate/',
            'user_id' => 1
        ];

        // Create first calendar
        $this->calendarService->createCalendar($calendarData);

        // Act & Assert - Try to create duplicate
        $this->expectException(\InvalidArgumentException::class);
        $this->calendarService->createCalendar($calendarData);
    }

    public function testCalendarUserIsolation(): void
    {
        // Arrange - Create calendars for different users
        $calendar1 = $this->calendarService->createCalendar([
            'name' => 'User 1 Calendar',
            'color' => '#4285f4',
            'url' => 'http://test-caldav-server/calendars/user1/',
            'user_id' => 1
        ]);

        $calendar2 = $this->calendarService->createCalendar([
            'name' => 'User 2 Calendar',
            'color' => '#ea4335',
            'url' => 'http://test-caldav-server/calendars/user2/',
            'user_id' => 2
        ]);

        // Act
        $user1Calendars = $this->calendarService->getCalendarsByUser(1);
        $user2Calendars = $this->calendarService->getCalendarsByUser(2);

        // Assert
        $this->assertCount(1, $user1Calendars);
        $this->assertCount(1, $user2Calendars);
        $this->assertEquals('User 1 Calendar', $user1Calendars[0]['name']);
        $this->assertEquals('User 2 Calendar', $user2Calendars[0]['name']);
    }

    public function testCalendarPerformanceWithManyCalendars(): void
    {
        // Arrange - Create many calendars
        $calendarCount = 100;
        for ($i = 1; $i <= $calendarCount; $i++) {
            $this->calendarService->createCalendar([
                'name' => "Calendar {$i}",
                'color' => '#4285f4',
                'url' => "http://test-caldav-server/calendars/{$i}/",
                'user_id' => 1
            ]);
        }

        // Act
        $startTime = microtime(true);
        $calendars = $this->calendarService->getCalendarsByUser(1);
        $endTime = microtime(true);

        // Assert
        $this->assertCount($calendarCount, $calendars);
        $this->assertLessThan(1.0, $endTime - $startTime); // Should complete in under 1 second
    }

    private function initializeTestData(): void
    {
        // Initialize empty test data files
        $testFiles = [
            'test_calendars.json' => [],
            'test_calendar_colors.json' => [],
            'test_calendar_states.json' => []
        ];

        foreach ($testFiles as $filename => $data) {
            $filepath = $this->testDataDir . '/' . $filename;
            file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
        }
    }

    protected function tearDown(): void
    {
        // Clean up test data files
        $testFiles = glob($this->testDataDir . '/test_*.json');
        foreach ($testFiles as $file) {
            unlink($file);
        }
        
        parent::tearDown();
    }
}

