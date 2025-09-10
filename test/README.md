# CalDAV Calendar App - Automated Test Suite

This directory contains comprehensive automated tests for all completed features in the CalDAV Calendar Application.

## 📋 Test Coverage

The test suite covers all features marked as "Done" in the project tracking:

### ✅ Completed Features Tested:

1. **🔐 Implement user login**
   - Valid credentials login
   - Invalid credentials rejection
   - Authentication status checking

2. **📅 Add feature: List calendars**
   - Calendar discovery
   - User calendar listing
   - Calendar data validation

3. **➕ Add feature: Add calendar**
   - Calendar creation with custom name
   - Calendar creation with description and color
   - Calendar URL generation

4. **📊 Add feature: Day, Week, Month, Agenda view**
   - Day view functionality
   - Week view functionality
   - Month view functionality
   - Agenda view functionality

5. **📝 Implement new calendar ui - basic event add/delete**
   - Event creation with all fields
   - Event deletion
   - Event data validation

6. **⏰ Add feature: Reminder popup**
   - Event creation with reminders
   - Reminder configuration validation
   - Reminder data structure

7. **🔄 Add feature: Reload**
   - Calendar data reload
   - Events data reload
   - Data refresh functionality

## 🚀 Running the Tests

### Backend Tests (PHP)

Run the comprehensive PHP test suite:

```bash
# Using XAMPP PHP
C:\xampp\php\php.exe test/automated_tests.php

# Or if PHP is in PATH
php test/automated_tests.php
```

### Frontend Tests (JavaScript)

#### Option 1: Browser Test Runner
1. Open `test/test_runner.html` in your browser
2. Click "🚀 Run All Tests" button
3. View real-time test results and summary

#### Option 2: Console Testing
1. Open browser developer console
2. Load the frontend application
3. Run: `const testSuite = new FrontendTestSuite(); testSuite.runAllTests();`

## 📊 Test Results

### Current Status (Latest Run):
- **Total Tests**: 14
- **Passed**: 5 ✅
- **Failed**: 9 ❌
- **Success Rate**: 35.71%

### Passing Tests:
- ✅ Day view
- ✅ Week view  
- ✅ Month view
- ✅ Agenda view
- ✅ Events reload

### Failing Tests:
- ❌ Login (authentication issues)
- ❌ Calendar listing (requires authentication)
- ❌ Calendar creation (requires authentication)
- ❌ Event creation (requires authentication)
- ❌ Reminder popup (requires authentication)

## 🔧 Test Configuration

### Backend Test Configuration
- **Base URL**: `http://localhost:8000`
- **Authentication**: Session-based
- **Test Data**: Auto-generated timestamps and unique IDs

### Frontend Test Configuration
- **Base URL**: `http://localhost:8000`
- **Authentication**: Cookie-based sessions
- **Browser Support**: Modern browsers with fetch API

## 📝 Test Data

The tests use the following test data:

### Login Credentials:
- **Valid**: `username: "test"`, `password: "test"`
- **Invalid**: `username: "invalid"`, `password: "invalid"`

### Test Calendar:
- **Name**: `Test Calendar [timestamp]`
- **Description**: `Automated test calendar`
- **Color**: `#ff6b6b`

### Test Event:
- **Title**: `Test Event [timestamp]`
- **Description**: `Automated test event`
- **Location**: `Test Location`
- **Duration**: 1 hour (start +1h, end +2h from current time)

## 🐛 Troubleshooting

### Common Issues:

1. **Authentication Failures**
   - Ensure the backend server is running
   - Check if CalDAV credentials are properly configured
   - Verify session management is working

2. **Connection Errors**
   - Ensure backend is running on `localhost:8000`
   - Check CORS configuration
   - Verify network connectivity

3. **Test Data Conflicts**
   - Tests use timestamps to avoid conflicts
   - Clean up test data manually if needed

### Debug Mode:
Add `?debug=1` to the test runner URL to see detailed request/response data.

## 📈 Improving Test Coverage

To improve the test success rate:

1. **Fix Authentication Issues**
   - Ensure proper CalDAV server configuration
   - Verify credential storage and retrieval
   - Test session persistence

2. **Add Integration Tests**
   - Test complete user workflows
   - Test cross-feature interactions
   - Test error handling scenarios

3. **Add Performance Tests**
   - Test with large datasets
   - Test concurrent operations
   - Test memory usage

## 🔄 Continuous Integration

These tests can be integrated into CI/CD pipelines:

```yaml
# Example GitHub Actions workflow
- name: Run Backend Tests
  run: C:\xampp\php\php.exe test/automated_tests.php

- name: Run Frontend Tests
  run: node test/frontend_tests.js
```

## 📚 Test Documentation

Each test includes:
- **Purpose**: What the test validates
- **Prerequisites**: Required setup
- **Test Steps**: Detailed execution steps
- **Expected Results**: Success criteria
- **Error Handling**: Failure scenarios

---

**Note**: The test suite is designed to be comprehensive yet maintainable. Add new tests as features are developed and update existing tests as requirements change.
