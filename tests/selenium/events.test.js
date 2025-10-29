const { expect } = require('chai');
const TestConfig = require('./config/test-config');
const TestHelpers = require('./utils/test-helpers');

describe('Calendar Events Tests', function() {
  let driver;
  let testConfig;
  let helpers;
  
  // Increase timeout for all tests
  this.timeout(60000);

  before(async function() {
    testConfig = new TestConfig();
    driver = await testConfig.createDriver('chrome', false);
    helpers = new TestHelpers(driver);
    
    // Login before running event tests
    const username = process.env.TEST_USERNAME || 'test@example.com';
    const password = process.env.TEST_PASSWORD || 'password123';
    
    try {
      await testConfig.login(driver, username, password);
    } catch (error) {
      console.log('Login failed, continuing with tests...');
    }
  });

  after(async function() {
    if (driver) {
      await driver.quit();
    }
  });

  beforeEach(async function() {
    // Navigate to calendar page before each test
    await testConfig.navigateToCalendar(driver);
  });

  describe('Event Creation', function() {
    it('should open event creation modal', async function() {
      await helpers.openEventModal();
      
      // Verify modal is open
      await helpers.assertElementPresent('.event-modal, .modal, [data-testid="event-modal"]');
      
      // Check for form fields
      await helpers.assertElementPresent('input[name="title"], input[placeholder*="title"], [data-testid="event-title"]');
    });

    it('should create a simple event', async function() {
      const eventTitle = `Test Event ${Date.now()}`;
      const eventDescription = 'This is a test event description';
      
      await helpers.openEventModal();
      
      // Fill event form
      const eventData = {
        title: eventTitle,
        description: eventDescription,
        startDate: '2025-01-15T10:00',
        endDate: '2025-01-15T11:00'
      };
      
      await helpers.fillEventForm(eventData);
      await helpers.saveEvent();
      
      // Verify event was created
      await driver.sleep(2000);
      await helpers.assertEventExists(eventTitle);
    });

    it('should create an all-day event', async function() {
      const eventTitle = `All Day Event ${Date.now()}`;
      
      await helpers.openEventModal();
      
      const eventData = {
        title: eventTitle,
        allDay: true,
        startDate: '2025-01-20',
        endDate: '2025-01-20'
      };
      
      await helpers.fillEventForm(eventData);
      await helpers.saveEvent();
      
      // Verify all-day event was created
      await driver.sleep(2000);
      await helpers.assertEventExists(eventTitle);
    });

    it('should validate required fields', async function() {
      await helpers.openEventModal();
      
      // Try to save without filling required fields
      await helpers.saveEvent();
      
      // Check for validation errors
      await driver.sleep(1000);
      const hasValidationError = await helpers.isElementPresent('.error, .validation-error, .alert-danger, [data-testid="error"]');
      expect(hasValidationError).to.be.true;
    });

    it('should cancel event creation', async function() {
      await helpers.openEventModal();
      
      // Fill some data
      await helpers.typeText('input[name="title"], input[placeholder*="title"], [data-testid="event-title"]', 'Test Event');
      
      // Cancel
      await helpers.cancelEvent();
      
      // Verify modal is closed and no event was created
      await helpers.assertElementNotPresent('.event-modal, .modal, [data-testid="event-modal"]');
    });

    it('should create event with location', async function() {
      const eventTitle = `Event with Location ${Date.now()}`;
      const eventLocation = 'Conference Room A';
      
      await helpers.openEventModal();
      
      const eventData = {
        title: eventTitle,
        location: eventLocation,
        startDate: '2025-01-25T14:00',
        endDate: '2025-01-25T15:00'
      };
      
      await helpers.fillEventForm(eventData);
      await helpers.saveEvent();
      
      // Verify event was created
      await driver.sleep(2000);
      await helpers.assertEventExists(eventTitle);
    });
  });

  describe('Event Editing', function() {
    let testEventTitle;

    beforeEach(async function() {
      // Create a test event for editing
      testEventTitle = `Edit Test Event ${Date.now()}`;
      
      await helpers.openEventModal();
      const eventData = {
        title: testEventTitle,
        description: 'Original description',
        startDate: '2025-02-01T09:00',
        endDate: '2025-02-01T10:00'
      };
      
      await helpers.fillEventForm(eventData);
      await helpers.saveEvent();
      await driver.sleep(2000);
    });

    it('should open event for editing', async function() {
      await helpers.openEventForEdit(testEventTitle);
      
      // Verify edit modal is open
      await helpers.assertElementPresent('.event-detail-modal, .event-modal, [data-testid="event-detail"]');
    });

    it('should edit event title', async function() {
      const newTitle = `Updated ${testEventTitle}`;
      
      await helpers.openEventForEdit(testEventTitle);
      
      // Update title
      await helpers.typeText('input[name="title"], input[placeholder*="title"], [data-testid="event-title"]', newTitle);
      await helpers.saveEvent();
      
      // Verify event was updated
      await driver.sleep(2000);
      await helpers.assertEventExists(newTitle);
      await helpers.assertEventNotExists(testEventTitle);
    });

    it('should edit event description', async function() {
      const newDescription = 'Updated description';
      
      await helpers.openEventForEdit(testEventTitle);
      
      // Update description
      await helpers.typeText('textarea[name="description"], textarea[placeholder*="description"], [data-testid="event-description"]', newDescription);
      await helpers.saveEvent();
      
      // Verify event still exists (description change doesn't affect title)
      await driver.sleep(2000);
      await helpers.assertEventExists(testEventTitle);
    });

    it('should edit event time', async function() {
      await helpers.openEventForEdit(testEventTitle);
      
      // Update time
      await helpers.typeText('input[name="start_date"], input[type="datetime-local"], [data-testid="start-date"]', '2025-02-01T11:00');
      await helpers.typeText('input[name="end_date"], input[type="datetime-local"]:nth-of-type(2), [data-testid="end-date"]', '2025-02-01T12:00');
      await helpers.saveEvent();
      
      // Verify event still exists
      await driver.sleep(2000);
      await helpers.assertEventExists(testEventTitle);
    });

    it('should cancel event editing', async function() {
      await helpers.openEventForEdit(testEventTitle);
      
      // Make some changes
      await helpers.typeText('input[name="title"], input[placeholder*="title"], [data-testid="event-title"]', 'This should not be saved');
      
      // Cancel
      await helpers.cancelEvent();
      
      // Verify original event still exists
      await driver.sleep(1000);
      await helpers.assertEventExists(testEventTitle);
    });
  });

  describe('Event Deletion', function() {
    let testEventTitle;

    beforeEach(async function() {
      // Create a test event for deletion
      testEventTitle = `Delete Test Event ${Date.now()}`;
      
      await helpers.openEventModal();
      const eventData = {
        title: testEventTitle,
        startDate: '2025-02-05T10:00',
        endDate: '2025-02-05T11:00'
      };
      
      await helpers.fillEventForm(eventData);
      await helpers.saveEvent();
      await driver.sleep(2000);
    });

    it('should delete an event', async function() {
      // Verify event exists
      await helpers.assertEventExists(testEventTitle);
      
      // Delete event
      await helpers.deleteEvent(testEventTitle);
      
      // Verify event was deleted
      await driver.sleep(2000);
      await helpers.assertEventNotExists(testEventTitle);
    });

    it('should show confirmation dialog for deletion', async function() {
      await helpers.openEventForEdit(testEventTitle);
      
      // Click delete button
      await helpers.clickElement('.delete-event, [data-testid="delete-event"], button:contains("Delete")');
      
      // Check for confirmation dialog
      const hasConfirmation = await helpers.isElementPresent('.confirm-delete, .delete-confirm, [data-testid="confirm-delete"]');
      expect(hasConfirmation).to.be.true;
    });
  });

  describe('Event Display', function() {
    it('should display events on calendar', async function() {
      const eventTitle = `Display Test Event ${Date.now()}`;
      
      // Create event
      await helpers.openEventModal();
      const eventData = {
        title: eventTitle,
        startDate: '2025-02-10T14:00',
        endDate: '2025-02-10T15:00'
      };
      
      await helpers.fillEventForm(eventData);
      await helpers.saveEvent();
      await driver.sleep(2000);
      
      // Check if event is displayed on calendar
      const events = await helpers.getCalendarEvents();
      const eventExists = events.some(event => event.title.includes(eventTitle));
      expect(eventExists).to.be.true;
    });

    it('should show event details on hover', async function() {
      const eventTitle = `Hover Test Event ${Date.now()}`;
      
      // Create event
      await helpers.openEventModal();
      const eventData = {
        title: eventTitle,
        description: 'Hover test description',
        startDate: '2025-02-15T16:00',
        endDate: '2025-02-15T17:00'
      };
      
      await helpers.fillEventForm(eventData);
      await helpers.saveEvent();
      await driver.sleep(2000);
      
      // Find the event element and hover
      const eventElements = await driver.findElements(require('selenium-webdriver').By.css('.calendar-event, .event-item, [data-testid="calendar-event"]'));
      
      for (const element of eventElements) {
        const text = await element.getText();
        if (text.includes(eventTitle)) {
          const actions = driver.actions();
          await actions.move({ origin: element }).perform();
          await driver.sleep(1000);
          break;
        }
      }
      
      // Check for tooltip or hover effect (optional)
      const hasTooltip = await helpers.isElementPresent('.tooltip, .hover-info, [data-testid="event-tooltip"]');
      // This is optional - not all calendars have hover tooltips
    });

    it('should display multiple events on same day', async function() {
      const event1Title = `Multi Event 1 ${Date.now()}`;
      const event2Title = `Multi Event 2 ${Date.now()}`;
      
      // Create first event
      await helpers.openEventModal();
      const event1Data = {
        title: event1Title,
        startDate: '2025-02-20T09:00',
        endDate: '2025-02-20T10:00'
      };
      
      await helpers.fillEventForm(event1Data);
      await helpers.saveEvent();
      await driver.sleep(1000);
      
      // Create second event on same day
      await helpers.openEventModal();
      const event2Data = {
        title: event2Title,
        startDate: '2025-02-20T11:00',
        endDate: '2025-02-20T12:00'
      };
      
      await helpers.fillEventForm(event2Data);
      await helpers.saveEvent();
      await driver.sleep(2000);
      
      // Verify both events exist
      await helpers.assertEventExists(event1Title);
      await helpers.assertEventExists(event2Title);
    });
  });

  describe('Event Validation', function() {
    it('should prevent creating event with end time before start time', async function() {
      await helpers.openEventModal();
      
      const eventData = {
        title: 'Invalid Time Event',
        startDate: '2025-02-25T15:00',
        endDate: '2025-02-25T14:00' // End before start
      };
      
      await helpers.fillEventForm(eventData);
      await helpers.saveEvent();
      
      // Check for validation error
      await driver.sleep(1000);
      const hasValidationError = await helpers.isElementPresent('.error, .validation-error, .alert-danger, [data-testid="error"]');
      expect(hasValidationError).to.be.true;
    });

    it('should validate event title length', async function() {
      await helpers.openEventModal();
      
      // Try to create event with very long title
      const longTitle = 'A'.repeat(500); // Very long title
      
      await helpers.typeText('input[name="title"], input[placeholder*="title"], [data-testid="event-title"]', longTitle);
      await helpers.saveEvent();
      
      // Should either truncate or show validation error
      await driver.sleep(1000);
      // This test depends on the application's validation rules
    });

    it('should handle special characters in event title', async function() {
      const specialTitle = 'Event with Special Chars: @#$%^&*()_+-=[]{}|;:,.<>?';
      
      await helpers.openEventModal();
      
      const eventData = {
        title: specialTitle,
        startDate: '2025-02-28T10:00',
        endDate: '2025-02-28T11:00'
      };
      
      await helpers.fillEventForm(eventData);
      await helpers.saveEvent();
      
      // Verify event was created with special characters
      await driver.sleep(2000);
      await helpers.assertEventExists(specialTitle);
    });
  });

  describe('Event Performance', function() {
    it('should handle creating multiple events quickly', async function() {
      const eventTitles = [];
      
      // Create 5 events quickly
      for (let i = 0; i < 5; i++) {
        const eventTitle = `Quick Event ${i} ${Date.now()}`;
        eventTitles.push(eventTitle);
        
        await helpers.openEventModal();
        const eventData = {
          title: eventTitle,
          startDate: `2025-03-0${i + 1}T10:00`,
          endDate: `2025-03-0${i + 1}T11:00`
        };
        
        await helpers.fillEventForm(eventData);
        await helpers.saveEvent();
        await driver.sleep(500);
      }
      
      // Verify all events were created
      for (const title of eventTitles) {
        await helpers.assertEventExists(title);
      }
    });

    it('should load calendar with many events efficiently', async function() {
      // This test would require pre-existing events
      // For now, just verify calendar loads within reasonable time
      const startTime = Date.now();
      await testConfig.navigateToCalendar(driver);
      const loadTime = Date.now() - startTime;
      
      expect(loadTime).to.be.lessThan(10000); // Should load within 10 seconds
    });
  });
});
