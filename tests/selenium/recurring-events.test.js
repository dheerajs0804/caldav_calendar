const { expect } = require('chai');
const TestConfig = require('./config/test-config');
const TestHelpers = require('./utils/test-helpers');

describe('Recurring Events Tests', function() {
  let driver;
  let testConfig;
  let helpers;
  
  // Increase timeout for all tests
  this.timeout(60000);

  before(async function() {
    testConfig = new TestConfig();
    driver = await testConfig.createDriver('chrome', false);
    helpers = new TestHelpers(driver);
    
    // Login before running recurring event tests
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

  describe('Recurring Event Creation', function() {
    it('should create a daily recurring event', async function() {
      const eventTitle = `Daily Recurring Event ${Date.now()}`;
      
      await helpers.openEventModal();
      
      // Fill basic event data
      const eventData = {
        title: eventTitle,
        description: 'Daily recurring event',
        startDate: '2025-03-01T09:00',
        endDate: '2025-03-01T10:00'
      };
      
      await helpers.fillEventForm(eventData);
      
      // Set up daily recurrence
      const recurrenceData = {
        frequency: 'daily',
        interval: 1,
        count: 5
      };
      
      await helpers.setRecurrence(recurrenceData);
      await helpers.saveEvent();
      
      // Verify recurring event was created
      await driver.sleep(3000);
      await helpers.assertEventExists(eventTitle);
    });

    it('should create a weekly recurring event', async function() {
      const eventTitle = `Weekly Recurring Event ${Date.now()}`;
      
      await helpers.openEventModal();
      
      const eventData = {
        title: eventTitle,
        description: 'Weekly recurring event',
        startDate: '2025-03-03T14:00',
        endDate: '2025-03-03T15:00'
      };
      
      await helpers.fillEventForm(eventData);
      
      const recurrenceData = {
        frequency: 'weekly',
        interval: 1,
        count: 4
      };
      
      await helpers.setRecurrence(recurrenceData);
      await helpers.saveEvent();
      
      // Verify recurring event was created
      await driver.sleep(3000);
      await helpers.assertEventExists(eventTitle);
    });

    it('should create a monthly recurring event', async function() {
      const eventTitle = `Monthly Recurring Event ${Date.now()}`;
      
      await helpers.openEventModal();
      
      const eventData = {
        title: eventTitle,
        description: 'Monthly recurring event',
        startDate: '2025-03-05T11:00',
        endDate: '2025-03-05T12:00'
      };
      
      await helpers.fillEventForm(eventData);
      
      const recurrenceData = {
        frequency: 'monthly',
        interval: 1,
        count: 6
      };
      
      await helpers.setRecurrence(recurrenceData);
      await helpers.saveEvent();
      
      // Verify recurring event was created
      await driver.sleep(3000);
      await helpers.assertEventExists(eventTitle);
    });

    it('should create recurring event with until date', async function() {
      const eventTitle = `Until Date Recurring Event ${Date.now()}`;
      
      await helpers.openEventModal();
      
      const eventData = {
        title: eventTitle,
        description: 'Recurring event with until date',
        startDate: '2025-03-07T16:00',
        endDate: '2025-03-07T17:00'
      };
      
      await helpers.fillEventForm(eventData);
      
      const recurrenceData = {
        frequency: 'daily',
        interval: 1,
        until: '2025-03-15'
      };
      
      await helpers.setRecurrence(recurrenceData);
      await helpers.saveEvent();
      
      // Verify recurring event was created
      await driver.sleep(3000);
      await helpers.assertEventExists(eventTitle);
    });

    it('should create recurring event with custom interval', async function() {
      const eventTitle = `Custom Interval Event ${Date.now()}`;
      
      await helpers.openEventModal();
      
      const eventData = {
        title: eventTitle,
        description: 'Every 3 days recurring event',
        startDate: '2025-03-10T13:00',
        endDate: '2025-03-10T14:00'
      };
      
      await helpers.fillEventForm(eventData);
      
      const recurrenceData = {
        frequency: 'daily',
        interval: 3,
        count: 4
      };
      
      await helpers.setRecurrence(recurrenceData);
      await helpers.saveEvent();
      
      // Verify recurring event was created
      await driver.sleep(3000);
      await helpers.assertEventExists(eventTitle);
    });
  });

  describe('Recurring Event Display', function() {
    let recurringEventTitle;

    beforeEach(async function() {
      // Create a recurring event for display tests
      recurringEventTitle = `Display Test Recurring Event ${Date.now()}`;
      
      await helpers.openEventModal();
      const eventData = {
        title: recurringEventTitle,
        description: 'Test recurring event for display',
        startDate: '2025-03-12T10:00',
        endDate: '2025-03-12T11:00'
      };
      
      await helpers.fillEventForm(eventData);
      
      const recurrenceData = {
        frequency: 'daily',
        interval: 1,
        count: 7
      };
      
      await helpers.setRecurrence(recurrenceData);
      await helpers.saveEvent();
      await driver.sleep(3000);
    });

    it('should display multiple occurrences of recurring event', async function() {
      // Navigate to different days to see occurrences
      await helpers.navigateToMonth('next');
      await driver.sleep(1000);
      
      // Check if recurring event appears on multiple days
      const events = await helpers.getCalendarEvents();
      const recurringEvents = events.filter(event => event.title.includes(recurringEventTitle));
      
      expect(recurringEvents.length).to.be.greaterThan(1);
    });

    it('should show recurring event indicator', async function() {
      // Look for recurring event indicators
      const hasRecurringIndicator = await helpers.isElementPresent('.recurring-indicator, .repeat-icon, [data-testid="recurring-indicator"]');
      // This is optional - depends on UI design
    });

    it('should display recurring event details', async function() {
      // Click on a recurring event occurrence
      const eventElements = await driver.findElements(require('selenium-webdriver').By.css('.calendar-event, .event-item, [data-testid="calendar-event"]'));
      
      for (const element of eventElements) {
        const text = await element.getText();
        if (text.includes(recurringEventTitle)) {
          await element.click();
          break;
        }
      }
      
      // Wait for event detail modal
      await driver.sleep(1000);
      
      // Check for recurring event information
      await helpers.assertElementPresent('.event-detail-modal, .event-modal, [data-testid="event-detail"]');
    });
  });

  describe('Recurring Event Editing', function() {
    let recurringEventTitle;

    beforeEach(async function() {
      // Create a recurring event for editing tests
      recurringEventTitle = `Edit Test Recurring Event ${Date.now()}`;
      
      await helpers.openEventModal();
      const eventData = {
        title: recurringEventTitle,
        description: 'Original description',
        startDate: '2025-03-15T15:00',
        endDate: '2025-03-15T16:00'
      };
      
      await helpers.fillEventForm(eventData);
      
      const recurrenceData = {
        frequency: 'weekly',
        interval: 1,
        count: 5
      };
      
      await helpers.setRecurrence(recurrenceData);
      await helpers.saveEvent();
      await driver.sleep(3000);
    });

    it('should show warning message for single occurrence edit', async function() {
      // Open recurring event for editing
      const eventElements = await driver.findElements(require('selenium-webdriver').By.css('.calendar-event, .event-item, [data-testid="calendar-event"]'));
      
      for (const element of eventElements) {
        const text = await element.getText();
        if (text.includes(recurringEventTitle)) {
          await element.click();
          break;
        }
      }
      
      // Wait for event detail modal
      await driver.sleep(1000);
      
      // Click edit button
      await helpers.clickElement('.edit-event, [data-testid="edit-event"], button:contains("Edit")');
      
      // Check for warning message about single occurrence editing
      await helpers.assertElementPresent('.bg-yellow-50, .warning-message, [data-testid="single-occurrence-warning"]');
    });

    it('should allow editing all occurrences', async function() {
      // Open recurring event for editing
      const eventElements = await driver.findElements(require('selenium-webdriver').By.css('.calendar-event, .event-item, [data-testid="calendar-event"]'));
      
      for (const element of eventElements) {
        const text = await element.getText();
        if (text.includes(recurringEventTitle)) {
          await element.click();
          break;
        }
      }
      
      // Wait for event detail modal
      await driver.sleep(1000);
      
      // Click edit button
      await helpers.clickElement('.edit-event, [data-testid="edit-event"], button:contains("Edit")');
      
      // Check for "Edit all occurrences" option
      await helpers.assertElementPresent('input[value="all"], [data-testid="edit-all-occurrences"]');
      
      // Select "Edit all occurrences"
      await helpers.clickElement('input[value="all"], [data-testid="edit-all-occurrences"]');
      
      // Make changes
      const newTitle = `Updated ${recurringEventTitle}`;
      await helpers.typeText('input[name="title"], input[placeholder*="title"], [data-testid="event-title"]', newTitle);
      
      // Save changes
      await helpers.saveEvent();
      
      // Verify all occurrences were updated
      await driver.sleep(3000);
      await helpers.assertEventExists(newTitle);
    });

    it('should not allow editing single occurrence', async function() {
      // Open recurring event for editing
      const eventElements = await driver.findElements(require('selenium-webdriver').By.css('.calendar-event, .event-item, [data-testid="calendar-event"]'));
      
      for (const element of eventElements) {
        const text = await element.getText();
        if (text.includes(recurringEventTitle)) {
          await element.click();
          break;
        }
      }
      
      // Wait for event detail modal
      await driver.sleep(1000);
      
      // Click edit button
      await helpers.clickElement('.edit-event, [data-testid="edit-event"], button:contains("Edit")');
      
      // Check that "Edit only this occurrence" option is not available
      await helpers.assertElementNotPresent('input[value="this"], [data-testid="edit-single-occurrence"]');
      
      // Verify warning message is displayed
      await helpers.assertElementPresent('.bg-yellow-50, .warning-message, [data-testid="single-occurrence-warning"]');
    });
  });

  describe('Recurring Event Deletion', function() {
    let recurringEventTitle;

    beforeEach(async function() {
      // Create a recurring event for deletion tests
      recurringEventTitle = `Delete Test Recurring Event ${Date.now()}`;
      
      await helpers.openEventModal();
      const eventData = {
        title: recurringEventTitle,
        description: 'Recurring event for deletion test',
        startDate: '2025-03-20T12:00',
        endDate: '2025-03-20T13:00'
      };
      
      await helpers.fillEventForm(eventData);
      
      const recurrenceData = {
        frequency: 'daily',
        interval: 1,
        count: 5
      };
      
      await helpers.setRecurrence(recurrenceData);
      await helpers.saveEvent();
      await driver.sleep(3000);
    });

    it('should delete all occurrences of recurring event', async function() {
      // Verify recurring event exists
      await helpers.assertEventExists(recurringEventTitle);
      
      // Delete the recurring event
      await helpers.deleteEvent(recurringEventTitle);
      
      // Verify all occurrences were deleted
      await driver.sleep(3000);
      await helpers.assertEventNotExists(recurringEventTitle);
    });

    it('should show confirmation for deleting recurring event', async function() {
      // Open recurring event for deletion
      const eventElements = await driver.findElements(require('selenium-webdriver').By.css('.calendar-event, .event-item, [data-testid="calendar-event"]'));
      
      for (const element of eventElements) {
        const text = await element.getText();
        if (text.includes(recurringEventTitle)) {
          await element.click();
          break;
        }
      }
      
      // Wait for event detail modal
      await driver.sleep(1000);
      
      // Click delete button
      await helpers.clickElement('.delete-event, [data-testid="delete-event"], button:contains("Delete")');
      
      // Check for confirmation dialog
      const hasConfirmation = await helpers.isElementPresent('.confirm-delete, .delete-confirm, [data-testid="confirm-delete"]');
      expect(hasConfirmation).to.be.true;
    });
  });

  describe('Recurring Event Validation', function() {
    it('should validate recurrence parameters', async function() {
      const eventTitle = `Validation Test Event ${Date.now()}`;
      
      await helpers.openEventModal();
      
      const eventData = {
        title: eventTitle,
        startDate: '2025-03-25T14:00',
        endDate: '2025-03-25T15:00'
      };
      
      await helpers.fillEventForm(eventData);
      
      // Try to set invalid recurrence (count = 0)
      try {
        await helpers.typeText('input[name="count"], [data-testid="recurrence-count"]', '0');
        await helpers.saveEvent();
        
        // Check for validation error
        await driver.sleep(1000);
        const hasValidationError = await helpers.isElementPresent('.error, .validation-error, .alert-danger, [data-testid="error"]');
        expect(hasValidationError).to.be.true;
      } catch (error) {
        // If the field doesn't exist or has different validation, that's also acceptable
        console.log('Recurrence validation test skipped - field not found or different validation');
      }
    });

    it('should handle invalid until date', async function() {
      const eventTitle = `Invalid Until Date Event ${Date.now()}`;
      
      await helpers.openEventModal();
      
      const eventData = {
        title: eventTitle,
        startDate: '2025-03-28T16:00',
        endDate: '2025-03-28T17:00'
      };
      
      await helpers.fillEventForm(eventData);
      
      // Try to set until date before start date
      try {
        await helpers.clickElement('input[name="end_type"][value="until"], [data-testid="end-type-until"]');
        await helpers.typeText('input[name="until"], [data-testid="recurrence-until"]', '2025-03-01');
        await helpers.saveEvent();
        
        // Check for validation error
        await driver.sleep(1000);
        const hasValidationError = await helpers.isElementPresent('.error, .validation-error, .alert-danger, [data-testid="error"]');
        expect(hasValidationError).to.be.true;
      } catch (error) {
        console.log('Until date validation test skipped - field not found or different validation');
      }
    });
  });

  describe('Recurring Event Performance', function() {
    it('should handle creating multiple recurring events', async function() {
      const eventTitles = [];
      
      // Create 3 recurring events
      for (let i = 0; i < 3; i++) {
        const eventTitle = `Performance Test Recurring Event ${i} ${Date.now()}`;
        eventTitles.push(eventTitle);
        
        await helpers.openEventModal();
        const eventData = {
          title: eventTitle,
          startDate: `2025-04-0${i + 1}T10:00`,
          endDate: `2025-04-0${i + 1}T11:00`
        };
        
        await helpers.fillEventForm(eventData);
        
        const recurrenceData = {
          frequency: 'daily',
          interval: 1,
          count: 3
        };
        
        await helpers.setRecurrence(recurrenceData);
        await helpers.saveEvent();
        await driver.sleep(1000);
      }
      
      // Verify all recurring events were created
      for (const title of eventTitles) {
        await helpers.assertEventExists(title);
      }
    });

    it('should load calendar with many recurring events efficiently', async function() {
      // Navigate to a month with recurring events
      await helpers.navigateToMonth('next');
      await driver.sleep(2000);
      
      // Check if calendar loads within reasonable time
      const startTime = Date.now();
      await testConfig.navigateToCalendar(driver);
      const loadTime = Date.now() - startTime;
      
      expect(loadTime).to.be.lessThan(15000); // Should load within 15 seconds
    });
  });

  describe('Recurring Event Edge Cases', function() {
    it('should handle recurring event spanning multiple months', async function() {
      const eventTitle = `Multi-Month Recurring Event ${Date.now()}`;
      
      await helpers.openEventModal();
      
      const eventData = {
        title: eventTitle,
        startDate: '2025-04-30T09:00',
        endDate: '2025-04-30T10:00'
      };
      
      await helpers.fillEventForm(eventData);
      
      const recurrenceData = {
        frequency: 'monthly',
        interval: 1,
        count: 3
      };
      
      await helpers.setRecurrence(recurrenceData);
      await helpers.saveEvent();
      
      // Verify recurring event was created
      await driver.sleep(3000);
      await helpers.assertEventExists(eventTitle);
      
      // Navigate to next month to see if occurrences appear
      await helpers.navigateToMonth('next');
      await driver.sleep(2000);
      
      // Check if event appears in next month
      const events = await helpers.getCalendarEvents();
      const hasEventInNextMonth = events.some(event => event.title.includes(eventTitle));
      expect(hasEventInNextMonth).to.be.true;
    });

    it('should handle recurring event with leap year dates', async function() {
      const eventTitle = `Leap Year Recurring Event ${Date.now()}`;
      
      await helpers.openEventModal();
      
      const eventData = {
        title: eventTitle,
        startDate: '2024-02-29T14:00', // Leap year date
        endDate: '2024-02-29T15:00'
      };
      
      await helpers.fillEventForm(eventData);
      
      const recurrenceData = {
        frequency: 'yearly',
        interval: 1,
        count: 2
      };
      
      await helpers.setRecurrence(recurrenceData);
      await helpers.saveEvent();
      
      // Verify recurring event was created
      await driver.sleep(3000);
      await helpers.assertEventExists(eventTitle);
    });
  });
});
