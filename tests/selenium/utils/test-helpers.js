const { By, until } = require('selenium-webdriver');

class TestHelpers {
  constructor(driver) {
    this.driver = driver;
  }

  // Element interaction helpers
  async clickElement(selector, timeout = 10000) {
    const element = await this.driver.wait(until.elementLocated(By.css(selector)), timeout);
    await this.driver.wait(until.elementIsVisible(element), timeout);
    await element.click();
  }

  async typeText(selector, text, clearFirst = true) {
    const element = await this.driver.wait(until.elementLocated(By.css(selector)), 10000);
    await this.driver.wait(until.elementIsVisible(element), 10000);
    
    if (clearFirst) {
      await element.clear();
    }
    await element.sendKeys(text);
  }

  async getText(selector) {
    const element = await this.driver.wait(until.elementLocated(By.css(selector)), 10000);
    return await element.getText();
  }

  async getAttribute(selector, attribute) {
    const element = await this.driver.wait(until.elementLocated(By.css(selector)), 10000);
    return await element.getAttribute(attribute);
  }

  async isElementPresent(selector) {
    try {
      await this.driver.findElement(By.css(selector));
      return true;
    } catch (error) {
      return false;
    }
  }

  async isElementVisible(selector) {
    try {
      const element = await this.driver.findElement(By.css(selector));
      return await element.isDisplayed();
    } catch (error) {
      return false;
    }
  }

  async waitForElementToDisappear(selector, timeout = 10000) {
    await this.driver.wait(async () => {
      try {
        const element = await this.driver.findElement(By.css(selector));
        return !(await element.isDisplayed());
      } catch (error) {
        return true; // Element not found, so it's disappeared
      }
    }, timeout);
  }

  // Calendar specific helpers
  async getCurrentMonth() {
    return await this.getText('.calendar-header .month-year, .current-month, [data-testid="current-month"]');
  }

  async navigateToMonth(direction) {
    const buttonSelector = direction === 'next' 
      ? '.next-month, .calendar-nav-next, [data-testid="next-month"]'
      : '.prev-month, .calendar-nav-prev, [data-testid="prev-month"]';
    
    await this.clickElement(buttonSelector);
    await this.driver.sleep(1000); // Wait for calendar to update
  }

  async getCalendarEvents() {
    const eventElements = await this.driver.findElements(By.css('.calendar-event, .event-item, [data-testid="calendar-event"]'));
    const events = [];
    
    for (const element of eventElements) {
      const title = await element.getText();
      const date = await element.getAttribute('data-date') || await element.getAttribute('data-testid');
      events.push({ title, date, element });
    }
    
    return events;
  }

  async clickOnDate(date) {
    const dateSelector = `[data-date="${date}"], .calendar-day[data-date="${date}"], [data-testid="day-${date}"]`;
    await this.clickElement(dateSelector);
  }

  async getEventsForDate(date) {
    const dateSelector = `[data-date="${date}"], .calendar-day[data-date="${date}"]`;
    const dayElement = await this.driver.findElement(By.css(dateSelector));
    const events = await dayElement.findElements(By.css('.day-event, .event-item'));
    
    const eventData = [];
    for (const event of events) {
      const title = await event.getText();
      eventData.push({ title, element: event });
    }
    
    return eventData;
  }

  // Event creation helpers
  async openEventModal() {
    // Try different selectors for "Add Event" button
    const selectors = [
      '.add-event-btn',
      '.create-event-btn',
      '[data-testid="add-event"]',
      'button:contains("Add Event")',
      'button:contains("New Event")',
      '.calendar-header button',
      '.toolbar button'
    ];

    for (const selector of selectors) {
      if (await this.isElementPresent(selector)) {
        await this.clickElement(selector);
        break;
      }
    }

    // Wait for modal to appear
    await this.driver.wait(until.elementLocated(By.css('.event-modal, .modal, [data-testid="event-modal"]')), 10000);
  }

  async fillEventForm(eventData) {
    // Title
    if (eventData.title) {
      await this.typeText('input[name="title"], input[placeholder*="title"], [data-testid="event-title"]', eventData.title);
    }

    // Description
    if (eventData.description) {
      await this.typeText('textarea[name="description"], textarea[placeholder*="description"], [data-testid="event-description"]', eventData.description);
    }

    // Location
    if (eventData.location) {
      await this.typeText('input[name="location"], input[placeholder*="location"], [data-testid="event-location"]', eventData.location);
    }

    // Start Date/Time
    if (eventData.startDate) {
      await this.typeText('input[name="start_date"], input[type="datetime-local"], [data-testid="start-date"]', eventData.startDate);
    }

    // End Date/Time
    if (eventData.endDate) {
      await this.typeText('input[name="end_date"], input[type="datetime-local"]:nth-of-type(2), [data-testid="end-date"]', eventData.endDate);
    }

    // All Day checkbox
    if (eventData.allDay !== undefined) {
      const allDayCheckbox = await this.driver.findElement(By.css('input[type="checkbox"][name="all_day"], [data-testid="all-day"]'));
      const isChecked = await allDayCheckbox.isSelected();
      if (eventData.allDay !== isChecked) {
        await allDayCheckbox.click();
      }
    }
  }

  async saveEvent() {
    await this.clickElement('button[type="submit"], .save-event, [data-testid="save-event"], button:contains("Save")');
    
    // Wait for modal to close
    await this.waitForElementToDisappear('.event-modal, .modal, [data-testid="event-modal"]');
  }

  async cancelEvent() {
    await this.clickElement('.cancel-event, [data-testid="cancel-event"], button:contains("Cancel")');
    
    // Wait for modal to close
    await this.waitForElementToDisappear('.event-modal, .modal, [data-testid="event-modal"]');
  }

  // Event editing helpers
  async openEventForEdit(eventTitle) {
    const eventSelector = `.calendar-event:contains("${eventTitle}"), [data-testid="calendar-event"]:contains("${eventTitle}")`;
    await this.clickElement(eventSelector);
    
    // Wait for event detail modal
    await this.driver.wait(until.elementLocated(By.css('.event-detail-modal, .event-modal, [data-testid="event-detail"]')), 10000);
    
    // Click edit button
    await this.clickElement('.edit-event, [data-testid="edit-event"], button:contains("Edit")');
  }

  async deleteEvent(eventTitle) {
    await this.openEventForEdit(eventTitle);
    
    // Click delete button
    await this.clickElement('.delete-event, [data-testid="delete-event"], button:contains("Delete")');
    
    // Confirm deletion if confirmation dialog appears
    if (await this.isElementPresent('.confirm-delete, .delete-confirm, [data-testid="confirm-delete"]')) {
      await this.clickElement('.confirm-delete, .delete-confirm, [data-testid="confirm-delete"]');
    }
    
    // Wait for modal to close
    await this.waitForElementToDisappear('.event-detail-modal, .event-modal, [data-testid="event-detail"]');
  }

  // Recurring event helpers
  async setRecurrence(recurrenceData) {
    // Frequency
    if (recurrenceData.frequency) {
      await this.clickElement('select[name="frequency"], [data-testid="recurrence-frequency"]');
      await this.clickElement(`option[value="${recurrenceData.frequency}"]`);
    }

    // Interval
    if (recurrenceData.interval) {
      await this.typeText('input[name="interval"], [data-testid="recurrence-interval"]', recurrenceData.interval.toString());
    }

    // Count
    if (recurrenceData.count) {
      await this.clickElement('input[name="end_type"][value="count"], [data-testid="end-type-count"]');
      await this.typeText('input[name="count"], [data-testid="recurrence-count"]', recurrenceData.count.toString());
    }

    // Until date
    if (recurrenceData.until) {
      await this.clickElement('input[name="end_type"][value="until"], [data-testid="end-type-until"]');
      await this.typeText('input[name="until"], [data-testid="recurrence-until"]', recurrenceData.until);
    }
  }

  // Assertion helpers
  async assertElementText(selector, expectedText) {
    const actualText = await this.getText(selector);
    if (!actualText.includes(expectedText)) {
      throw new Error(`Expected text "${expectedText}" not found in element "${selector}". Actual text: "${actualText}"`);
    }
  }

  async assertElementPresent(selector) {
    if (!(await this.isElementPresent(selector))) {
      throw new Error(`Element "${selector}" not found`);
    }
  }

  async assertElementNotPresent(selector) {
    if (await this.isElementPresent(selector)) {
      throw new Error(`Element "${selector}" should not be present`);
    }
  }

  async assertEventExists(eventTitle) {
    const events = await this.getCalendarEvents();
    const eventExists = events.some(event => event.title.includes(eventTitle));
    
    if (!eventExists) {
      throw new Error(`Event "${eventTitle}" not found on calendar`);
    }
  }

  async assertEventNotExists(eventTitle) {
    const events = await this.getCalendarEvents();
    const eventExists = events.some(event => event.title.includes(eventTitle));
    
    if (eventExists) {
      throw new Error(`Event "${eventTitle}" should not exist on calendar`);
    }
  }

  // Utility methods
  async scrollToElement(selector) {
    const element = await this.driver.findElement(By.css(selector));
    await this.driver.executeScript('arguments[0].scrollIntoView(true);', element);
    await this.driver.sleep(500);
  }

  async getCurrentUrl() {
    return await this.driver.getCurrentUrl();
  }

  async refreshPage() {
    await this.driver.navigate().refresh();
    await this.driver.sleep(2000);
  }

  async waitForPageLoad() {
    await this.driver.wait(async () => {
      return await this.driver.executeScript('return document.readyState') === 'complete';
    }, 10000);
  }
}

module.exports = TestHelpers;
