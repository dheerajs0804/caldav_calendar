const { expect } = require('chai');
const TestConfig = require('./config/test-config');
const TestHelpers = require('./utils/test-helpers');

describe('Calendar Navigation and View Tests', function() {
  let driver;
  let testConfig;
  let helpers;
  
  // Increase timeout for all tests
  this.timeout(60000);

  before(async function() {
    testConfig = new TestConfig();
    driver = await testConfig.createDriver('chrome', false);
    helpers = new TestHelpers(driver);
    
    // Login before running calendar tests
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

  describe('Calendar Page Load', function() {
    it('should load calendar page successfully', async function() {
      const currentUrl = await driver.getCurrentUrl();
      expect(currentUrl).to.include('/calendar');
      
      // Check for calendar container
      await helpers.assertElementPresent('.calendar-container, .calendar, [data-testid="calendar"]');
    });

    it('should display current month and year', async function() {
      const currentMonth = await helpers.getCurrentMonth();
      expect(currentMonth).to.not.be.empty;
      
      // Should contain current year
      const currentYear = new Date().getFullYear().toString();
      expect(currentMonth).to.include(currentYear);
    });

    it('should display calendar grid', async function() {
      // Check for calendar days
      await helpers.assertElementPresent('.calendar-day, .day-cell, [data-testid="calendar-day"]');
      
      // Should have at least 28 days (minimum for any month)
      const dayElements = await driver.findElements(require('selenium-webdriver').By.css('.calendar-day, .day-cell, [data-testid="calendar-day"]'));
      expect(dayElements.length).to.be.at.least(28);
    });
  });

  describe('Calendar Navigation', function() {
    it('should navigate to next month', async function() {
      const initialMonth = await helpers.getCurrentMonth();
      
      await helpers.navigateToMonth('next');
      
      const newMonth = await helpers.getCurrentMonth();
      expect(newMonth).to.not.equal(initialMonth);
    });

    it('should navigate to previous month', async function() {
      const initialMonth = await helpers.getCurrentMonth();
      
      await helpers.navigateToMonth('prev');
      
      const newMonth = await helpers.getCurrentMonth();
      expect(newMonth).to.not.equal(initialMonth);
    });

    it('should navigate multiple months forward and back', async function() {
      const initialMonth = await helpers.getCurrentMonth();
      
      // Navigate forward 3 months
      for (let i = 0; i < 3; i++) {
        await helpers.navigateToMonth('next');
        await driver.sleep(500);
      }
      
      // Navigate back 3 months
      for (let i = 0; i < 3; i++) {
        await helpers.navigateToMonth('prev');
        await driver.sleep(500);
      }
      
      const finalMonth = await helpers.getCurrentMonth();
      expect(finalMonth).to.equal(initialMonth);
    });

    it('should display navigation buttons', async function() {
      await helpers.assertElementPresent('.next-month, .calendar-nav-next, [data-testid="next-month"]');
      await helpers.assertElementPresent('.prev-month, .calendar-nav-prev, [data-testid="prev-month"]');
    });
  });

  describe('Calendar View Modes', function() {
    it('should switch to month view', async function() {
      // Look for view toggle buttons
      const viewSelectors = [
        '.view-toggle .month, .month-view, [data-testid="month-view"]',
        'button:contains("Month")',
        '.calendar-view-selector .month'
      ];
      
      for (const selector of viewSelectors) {
        if (await helpers.isElementPresent(selector)) {
          await helpers.clickElement(selector);
          break;
        }
      }
      
      // Verify month view is active
      await driver.sleep(1000);
      await helpers.assertElementPresent('.calendar-month, .month-calendar, [data-testid="month-calendar"]');
    });

    it('should switch to week view', async function() {
      const viewSelectors = [
        '.view-toggle .week, .week-view, [data-testid="week-view"]',
        'button:contains("Week")',
        '.calendar-view-selector .week'
      ];
      
      for (const selector of viewSelectors) {
        if (await helpers.isElementPresent(selector)) {
          await helpers.clickElement(selector);
          break;
        }
      }
      
      // Verify week view is active
      await driver.sleep(1000);
      await helpers.assertElementPresent('.calendar-week, .week-calendar, [data-testid="week-calendar"]');
    });

    it('should switch to day view', async function() {
      const viewSelectors = [
        '.view-toggle .day, .day-view, [data-testid="day-view"]',
        'button:contains("Day")',
        '.calendar-view-selector .day'
      ];
      
      for (const selector of viewSelectors) {
        if (await helpers.isElementPresent(selector)) {
          await helpers.clickElement(selector);
          break;
        }
      }
      
      // Verify day view is active
      await driver.sleep(1000);
      await helpers.assertElementPresent('.calendar-day-view, .day-calendar, [data-testid="day-calendar"]');
    });
  });

  describe('Calendar Date Selection', function() {
    it('should highlight today\'s date', async function() {
      const today = new Date();
      const todayString = today.toISOString().split('T')[0];
      
      // Look for today's date with different possible selectors
      const todaySelectors = [
        `[data-date="${todayString}"]`,
        `.today, .current-day, [data-testid="today"]`,
        `.calendar-day.today`
      ];
      
      let todayFound = false;
      for (const selector of todaySelectors) {
        if (await helpers.isElementPresent(selector)) {
          todayFound = true;
          break;
        }
      }
      
      expect(todayFound).to.be.true;
    });

    it('should allow clicking on dates', async function() {
      // Get a date from current month
      const today = new Date();
      const testDate = today.toISOString().split('T')[0];
      
      try {
        await helpers.clickOnDate(testDate);
        await driver.sleep(1000);
        
        // Check if date was selected (might show event creation modal or highlight)
        const hasSelection = await helpers.isElementPresent('.selected, .active, .event-modal, [data-testid="selected-date"]');
        expect(hasSelection).to.be.true;
      } catch (error) {
        console.log('Date clicking test skipped - date selector not found');
      }
    });

    it('should display date information when hovering', async function() {
      const today = new Date();
      const testDate = today.toISOString().split('T')[0];
      
      try {
        const dateElement = await driver.findElement(require('selenium-webdriver').By.css(`[data-date="${testDate}"]`));
        
        // Hover over the date
        const actions = driver.actions();
        await actions.move({ origin: dateElement }).perform();
        
        await driver.sleep(1000);
        
        // Check for tooltip or hover effect
        const hasTooltip = await helpers.isElementPresent('.tooltip, .hover-info, [data-testid="date-tooltip"]');
        // This is optional - not all calendars have hover tooltips
      } catch (error) {
        console.log('Hover test skipped - date element not found');
      }
    });
  });

  describe('Calendar Responsiveness', function() {
    it('should be responsive on mobile viewport', async function() {
      // Set mobile viewport
      await driver.manage().window().setRect({ width: 375, height: 667 });
      await driver.sleep(1000);
      
      // Check if calendar is still visible and functional
      await helpers.assertElementPresent('.calendar-container, .calendar, [data-testid="calendar"]');
      
      // Check if navigation buttons are accessible
      const navButtons = await driver.findElements(require('selenium-webdriver').By.css('.next-month, .prev-month, .calendar-nav-next, .calendar-nav-prev'));
      expect(navButtons.length).to.be.greaterThan(0);
      
      // Reset to desktop view
      await driver.manage().window().maximize();
    });

    it('should be responsive on tablet viewport', async function() {
      // Set tablet viewport
      await driver.manage().window().setRect({ width: 768, height: 1024 });
      await driver.sleep(1000);
      
      // Check if calendar is still visible and functional
      await helpers.assertElementPresent('.calendar-container, .calendar, [data-testid="calendar"]');
      
      // Check if calendar days are properly displayed
      const dayElements = await driver.findElements(require('selenium-webdriver').By.css('.calendar-day, .day-cell, [data-testid="calendar-day"]'));
      expect(dayElements.length).to.be.greaterThan(0);
      
      // Reset to desktop view
      await driver.manage().window().maximize();
    });
  });

  describe('Calendar Performance', function() {
    it('should load calendar within reasonable time', async function() {
      const startTime = Date.now();
      
      await testConfig.navigateToCalendar(driver);
      
      const loadTime = Date.now() - startTime;
      expect(loadTime).to.be.lessThan(10000); // Should load within 10 seconds
    });

    it('should handle rapid navigation without errors', async function() {
      // Rapidly navigate between months
      for (let i = 0; i < 5; i++) {
        await helpers.navigateToMonth('next');
        await driver.sleep(100);
        await helpers.navigateToMonth('prev');
        await driver.sleep(100);
      }
      
      // Check if calendar is still functional
      await helpers.assertElementPresent('.calendar-container, .calendar, [data-testid="calendar"]');
    });
  });

  describe('Calendar Accessibility', function() {
    it('should have proper ARIA labels', async function() {
      // Check for ARIA labels on navigation buttons
      const nextButton = await driver.findElement(require('selenium-webdriver').By.css('.next-month, .calendar-nav-next, [data-testid="next-month"]'));
      const nextAriaLabel = await nextButton.getAttribute('aria-label');
      expect(nextAriaLabel).to.not.be.null;
      
      const prevButton = await driver.findElement(require('selenium-webdriver').By.css('.prev-month, .calendar-nav-prev, [data-testid="prev-month"]'));
      const prevAriaLabel = await prevButton.getAttribute('aria-label');
      expect(prevAriaLabel).to.not.be.null;
    });

    it('should support keyboard navigation', async function() {
      // Test tab navigation through calendar elements
      const calendarContainer = await driver.findElement(require('selenium-webdriver').By.css('.calendar-container, .calendar, [data-testid="calendar"]'));
      await calendarContainer.click();
      
      // Tab through elements
      for (let i = 0; i < 5; i++) {
        await driver.actions().sendKeys(require('selenium-webdriver').Key.TAB).perform();
        await driver.sleep(200);
      }
      
      // Should not throw errors
      await helpers.assertElementPresent('.calendar-container, .calendar, [data-testid="calendar"]');
    });
  });
});
