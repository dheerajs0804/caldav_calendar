# Calendar UI Selenium Tests

This directory contains comprehensive automated functional tests for the Calendar UI using Selenium WebDriver.

## Test Structure

```
tests/selenium/
├── config/
│   └── test-config.js          # Test configuration and WebDriver setup
├── utils/
│   └── test-helpers.js         # Helper functions for common test operations
├── screenshots/                # Screenshots captured during test failures
├── login.test.js              # Login functionality tests
├── calendar.test.js           # Calendar navigation and view tests
├── events.test.js             # Event creation, editing, and deletion tests
├── recurring-events.test.js   # Recurring event specific tests
├── package.json               # Test dependencies and scripts
├── env.example                # Environment variables template
└── README.md                  # This file
```

## Prerequisites

1. **Node.js** (v14 or higher)
2. **Chrome Browser** (for ChromeDriver)
3. **Firefox Browser** (optional, for GeckoDriver)
4. **Calendar Application** running on `http://localhost:4200`
5. **Backend API** running on `http://localhost:8000`

## Installation

1. Navigate to the test directory:
   ```bash
   cd tests/selenium
   ```

2. Install dependencies:
   ```bash
   npm install
   ```

3. Copy environment configuration:
   ```bash
   cp env.example .env
   ```

4. Update `.env` file with your test credentials and configuration:
   ```env
   TEST_BASE_URL=http://localhost:4200
   TEST_BACKEND_URL=http://localhost:8000
   TEST_USERNAME=your-test-email@example.com
   TEST_PASSWORD=your-test-password
   ```

## Running Tests

### Run All Tests
```bash
npm test
```

### Run Specific Test Suites
```bash
# Login tests only
npm run test:login

# Calendar navigation tests only
npm run test:calendar

# Event management tests only
npm run test:events

# Recurring event tests only
npm run test:recurring
```

### Run Tests in Different Browsers
```bash
# Chrome (default)
npm run test:chrome

# Firefox
npm run test:firefox

# Headless mode
npm run test:headless
```

### Run Tests with Custom Parameters
```bash
# Run with specific timeout
mocha tests/selenium/**/*.test.js --timeout 60000

# Run with specific browser
mocha tests/selenium/**/*.test.js --browser=firefox

# Run in headless mode
mocha tests/selenium/**/*.test.js --headless
```

## Test Categories

### 1. Login Tests (`login.test.js`)
- **Login Page Elements**: Verifies all required form elements are present
- **Login Functionality**: Tests valid/invalid credentials, validation errors
- **Form Behavior**: Tests form reset, keyboard navigation
- **Responsiveness**: Tests mobile and tablet viewports
- **Security**: Tests password field behavior, special characters

### 2. Calendar Navigation Tests (`calendar.test.js`)
- **Page Load**: Verifies calendar loads successfully with current month/year
- **Navigation**: Tests month navigation (next/previous), multiple month navigation
- **View Modes**: Tests switching between month/week/day views
- **Date Selection**: Tests date clicking, today highlighting, hover effects
- **Responsiveness**: Tests mobile and tablet layouts
- **Performance**: Tests load times, rapid navigation handling
- **Accessibility**: Tests ARIA labels, keyboard navigation

### 3. Event Management Tests (`events.test.js`)
- **Event Creation**: Tests simple events, all-day events, events with location
- **Event Editing**: Tests title/description/time editing, cancellation
- **Event Deletion**: Tests event deletion with confirmation dialogs
- **Event Display**: Tests event visibility, hover effects, multiple events per day
- **Validation**: Tests required fields, time validation, special characters
- **Performance**: Tests multiple event creation, calendar load times

### 4. Recurring Event Tests (`recurring-events.test.js`)
- **Recurring Event Creation**: Tests daily/weekly/monthly recurrence patterns
- **Recurrence Options**: Tests custom intervals, until dates, occurrence counts
- **Event Display**: Tests multiple occurrence display, recurring indicators
- **Event Editing**: Tests the warning message for single occurrence edits
- **Event Deletion**: Tests deletion of all occurrences
- **Validation**: Tests recurrence parameter validation
- **Edge Cases**: Tests multi-month spanning, leap year dates

## Test Configuration

### Environment Variables
- `TEST_BASE_URL`: Frontend application URL (default: http://localhost:4200)
- `TEST_BACKEND_URL`: Backend API URL (default: http://localhost:8000)
- `TEST_USERNAME`: Test user email for login
- `TEST_PASSWORD`: Test user password for login
- `BROWSER`: Browser to use (chrome/firefox)
- `HEADLESS`: Run in headless mode (true/false)

### Timeout Configuration
- `TEST_TIMEOUT`: Overall test timeout (default: 30000ms)
- `PAGE_LOAD_TIMEOUT`: Page load timeout (default: 30000ms)
- `IMPLICIT_WAIT`: Implicit wait for elements (default: 5000ms)

## Test Helpers

The `TestHelpers` class provides common functionality:

### Element Interaction
- `clickElement(selector)`: Click an element
- `typeText(selector, text)`: Type text into an input
- `getText(selector)`: Get text from an element
- `isElementPresent(selector)`: Check if element exists
- `isElementVisible(selector)`: Check if element is visible

### Calendar Specific
- `getCurrentMonth()`: Get current month/year display
- `navigateToMonth(direction)`: Navigate to next/previous month
- `getCalendarEvents()`: Get all events on calendar
- `clickOnDate(date)`: Click on a specific date
- `getEventsForDate(date)`: Get events for a specific date

### Event Management
- `openEventModal()`: Open event creation modal
- `fillEventForm(eventData)`: Fill event form with data
- `saveEvent()`: Save event and close modal
- `cancelEvent()`: Cancel event creation
- `openEventForEdit(eventTitle)`: Open event for editing
- `deleteEvent(eventTitle)`: Delete an event

### Recurring Events
- `setRecurrence(recurrenceData)`: Set recurrence parameters
- `assertEventExists(eventTitle)`: Assert event exists on calendar
- `assertEventNotExists(eventTitle)`: Assert event doesn't exist

## Screenshots

Screenshots are automatically captured on test failures and saved to the `screenshots/` directory. The screenshot filename includes the test name and timestamp.

## Debugging Tests

### Enable Verbose Logging
```bash
DEBUG=* npm test
```

### Take Screenshots Manually
```javascript
await testConfig.takeScreenshot(driver, 'debug-screenshot');
```

### Pause Test Execution
```javascript
await driver.sleep(5000); // Pause for 5 seconds
```

### Check Current URL
```javascript
const currentUrl = await driver.getCurrentUrl();
console.log('Current URL:', currentUrl);
```

## Common Issues and Solutions

### 1. Element Not Found
- **Issue**: Test fails with "Element not found" error
- **Solution**: Check if the element selector is correct and the element exists in the DOM
- **Debug**: Use browser dev tools to verify element selectors

### 2. Timeout Errors
- **Issue**: Tests timeout waiting for elements
- **Solution**: Increase timeout values or check if the application is loading properly
- **Debug**: Add explicit waits or check network requests

### 3. Login Failures
- **Issue**: Login tests fail with authentication errors
- **Solution**: Verify test credentials in `.env` file and ensure backend is running
- **Debug**: Check backend logs and authentication flow

### 4. Browser Compatibility
- **Issue**: Tests work in one browser but not another
- **Solution**: Use browser-specific selectors or update WebDriver versions
- **Debug**: Run tests in different browsers to identify issues

## Continuous Integration

### GitHub Actions Example
```yaml
name: Selenium Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: actions/setup-node@v2
        with:
          node-version: '16'
      - name: Install dependencies
        run: |
          cd tests/selenium
          npm install
      - name: Run tests
        run: |
          cd tests/selenium
          npm run test:headless
        env:
          TEST_USERNAME: ${{ secrets.TEST_USERNAME }}
          TEST_PASSWORD: ${{ secrets.TEST_PASSWORD }}
```

## Best Practices

1. **Use Descriptive Test Names**: Test names should clearly describe what is being tested
2. **Keep Tests Independent**: Each test should be able to run independently
3. **Use Page Object Model**: Consider implementing page objects for complex pages
4. **Handle Async Operations**: Always wait for elements and async operations
5. **Clean Up After Tests**: Remove test data created during tests
6. **Use Meaningful Assertions**: Assert specific conditions, not just element presence
7. **Handle Flaky Tests**: Add retries or improve element selectors for flaky tests

## Contributing

When adding new tests:

1. Follow the existing test structure and naming conventions
2. Add appropriate test categories and descriptions
3. Use the TestHelpers class for common operations
4. Add proper error handling and timeouts
5. Update this README with new test information
6. Ensure tests are independent and can run in any order

## Support

For issues with the test suite:
1. Check the browser console for JavaScript errors
2. Verify the application is running and accessible
3. Check the test logs for specific error messages
4. Ensure all dependencies are installed correctly
