# Mithi Calendar - Testing Documentation

## 📋 Overview

This directory contains comprehensive testing resources for the Mithi Calendar application, including:

- **135 Test Cases** covering all functionality areas
- **Automated Test Scripts** for backend, frontend, and API testing
- **Test Execution Table** for tracking test progress
- **Performance & Security Testing** tools
- **Automated Test Runner** script

## 🗂️ Directory Structure

```
tests/
├── README.md                           # This file
├── TEST_CASES.md                       # Complete test cases (135 tests)
├── TEST_EXECUTION_TABLE.md             # Test execution tracking table
├── backend/
│   └── phpunit_tests.php              # PHPUnit test suite
├── frontend/
│   └── angular_tests.spec.ts          # Angular/Jasmine test suite
├── api/
│   └── postman_collection.json        # Postman API test collection
├── performance/
│   └── k6_performance_test.js         # k6 performance test script
└── run_tests.sh                       # Automated test execution script
```

## 🚀 Quick Start

### 1. Run All Tests (Recommended)

```bash
# Make the script executable
chmod +x tests/run_tests.sh

# Run all tests
./tests/run_tests.sh
```

This will:
- Check all prerequisites
- Run backend PHP tests
- Run frontend Angular tests
- Run API tests
- Run performance tests
- Run security tests
- Generate comprehensive reports

### 2. Run Individual Test Suites

#### Backend PHP Tests
```bash
cd backend
phpunit ../tests/backend/phpunit_tests.php
```

#### Frontend Angular Tests
```bash
cd frontend
ng test
```

#### API Tests (Postman/Newman)
```bash
# Install Newman if not already installed
npm install -g newman

# Run API tests
newman run tests/api/postman_collection.json
```

#### Performance Tests (k6)
```bash
# Install k6
# Linux: Follow instructions at https://k6.io/docs/getting-started/installation/
# macOS: brew install k6

# Run performance tests
k6 run tests/performance/k6_performance_test.js
```

## 📊 Test Categories

### 1. Authentication & Authorization (15 Tests)
- **TC001-TC015**: Login, logout, session management, security
- **Priority**: High (Critical functionality)
- **Tools**: PHPUnit, Postman

### 2. Calendar View & Navigation (20 Tests)
- **TC016-TC035**: Month/week/day views, navigation, responsive design
- **Priority**: High (Core UI functionality)
- **Tools**: Angular/Jasmine, Browser testing

### 3. Event Creation & Management (25 Tests)
- **TC036-TC060**: CRUD operations, validation, search, filtering
- **Priority**: High (Core business logic)
- **Tools**: PHPUnit, Angular/Jasmine, Postman

### 4. Attendee Management (15 Tests)
- **TC061-TC075**: Add/remove attendees, validation, permissions
- **Priority**: High (Collaboration features)
- **Tools**: PHPUnit, Angular/Jasmine, Postman

### 5. Email & Invitation System (20 Tests)
- **TC076-TC095**: Email sending, templates, iCalendar, delivery
- **Priority**: High (Communication features)
- **Tools**: PHPUnit, Postman

### 6. CalDAV Integration (15 Tests)
- **TC096-TC110**: Server connection, sync, conflict resolution
- **Priority**: High (Calendar synchronization)
- **Tools**: PHPUnit, Postman

### 7. Database & Storage (10 Tests)
- **TC111-TC120**: Data persistence, backup, validation
- **Priority**: High (Data integrity)
- **Tools**: PHPUnit

### 8. Performance & Load (5 Tests)
- **TC121-TC125**: Page load, rendering, search performance
- **Priority**: High (User experience)
- **Tools**: k6, Browser DevTools

### 9. Security Testing (5 Tests)
- **TC126-TC130**: SQL injection, XSS, CSRF prevention
- **Priority**: High (Security)
- **Tools**: OWASP ZAP, Manual testing

### 10. Cross-Platform (5 Tests)
- **TC131-TC135**: Browser compatibility, mobile responsiveness
- **Priority**: High (Accessibility)
- **Tools**: Browser testing, Device simulation

## 🛠️ Testing Tools

### Backend Testing
- **PHPUnit**: PHP unit testing framework
- **Codeception**: PHP acceptance testing (optional)
- **PHP Built-in Server**: For local testing

### Frontend Testing
- **Jasmine**: JavaScript testing framework
- **Karma**: Test runner
- **Protractor**: End-to-end testing (optional)
- **Angular CLI**: Built-in testing commands

### API Testing
- **Postman**: GUI for API testing
- **Newman**: CLI for Postman collections
- **cURL**: Command-line HTTP client

### Performance Testing
- **k6**: Modern load testing tool
- **Apache JMeter**: Alternative load testing
- **Browser DevTools**: Performance profiling

### Security Testing
- **OWASP ZAP**: Security vulnerability scanner
- **Burp Suite**: Web application security testing
- **Manual Testing**: Security best practices

### Cross-Browser Testing
- **BrowserStack**: Cloud-based browser testing
- **Sauce Labs**: Alternative cloud testing
- **Local Browsers**: Chrome, Firefox, Safari, Edge

## 📈 Test Execution Strategy

### Phase 1: Critical Functionality (Week 1)
- **High Priority Tests**: TC001-TC065 (65 tests)
- Focus on authentication, core calendar features, event management

### Phase 2: Important Features (Week 2)
- **Medium Priority Tests**: TC066-TC105 (40 tests)
- Focus on attendee management, email system, CalDAV integration

### Phase 3: Enhancement Features (Week 3)
- **Low Priority Tests**: TC106-TC135 (30 tests)
- Focus on performance, security, cross-platform compatibility

### Daily Execution
- Run regression tests on critical paths
- Execute new feature tests as they're developed
- Monitor test results and fix failures immediately

## 📋 Test Execution Table

Use `TEST_EXECUTION_TABLE.md` to track:
- Test execution status
- Results and defects
- Execution dates and testers
- Progress reporting

### Status Legend
- 🔴 **Not Started**: Test case not yet executed
- 🟡 **In Progress**: Test case currently being executed
- 🟢 **Passed**: Test case executed successfully
- 🔴 **Failed**: Test case failed (defect found)
- ⚠️ **Blocked**: Test case blocked by external dependency
- 🔄 **Retest**: Test case needs to be re-executed

## 🚨 Troubleshooting

### Common Issues

#### PHPUnit Tests Fail
```bash
# Check PHP version compatibility
php --version

# Install/update PHPUnit
composer require --dev phpunit/phpunit

# Check PHP extensions
php -m | grep -E "(mbstring|xml|json)"
```

#### Angular Tests Fail
```bash
# Clear npm cache
npm cache clean --force

# Reinstall dependencies
rm -rf node_modules package-lock.json
npm install

# Check Angular CLI version
ng version
```

#### API Tests Fail
```bash
# Check if backend server is running
curl http://localhost:8000

# Verify database connection
# Check CalDAV server connectivity
# Verify SMTP configuration
```

#### Performance Tests Fail
```bash
# Check k6 installation
k6 version

# Verify backend server performance
# Check system resources (CPU, memory)
# Monitor network latency
```

### Environment Setup

#### Development Environment
```bash
# Backend
cd backend
php -S localhost:8000

# Frontend
cd frontend
ng serve --port 4200

# Database
# Ensure MySQL/PostgreSQL is running
# Check connection settings in config files
```

#### Test Environment
```bash
# Use separate test database
# Configure test CalDAV server
# Use test SMTP server
# Set environment variables for testing
```

## 📊 Reporting & Metrics

### Test Reports Generated
- **PHPUnit**: XML results + HTML reports
- **Angular**: Code coverage reports
- **Newman**: API test results
- **k6**: Performance metrics
- **OWASP ZAP**: Security scan results
- **Summary Report**: Comprehensive overview

### Key Metrics
- **Test Coverage**: Percentage of code covered by tests
- **Pass Rate**: Percentage of tests passing
- **Execution Time**: Total time to run all tests
- **Defect Density**: Number of defects per test case
- **Performance Baselines**: Response time benchmarks

### Continuous Integration
```yaml
# Example GitHub Actions workflow
name: Test Suite
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run Backend Tests
        run: ./tests/run_tests.sh
      - name: Upload Test Results
        uses: actions/upload-artifact@v2
        with:
          name: test-results
          path: test_reports/
```

## 🔄 Maintenance

### Regular Updates
- **Weekly**: Review and update test cases
- **Monthly**: Update testing tools and dependencies
- **Quarterly**: Review test strategy and coverage
- **Annually**: Comprehensive test suite audit

### Test Case Management
- Add new test cases for new features
- Update existing test cases for changed functionality
- Remove obsolete test cases
- Prioritize test cases based on business impact

### Tool Updates
- Keep testing tools up to date
- Monitor for security vulnerabilities
- Evaluate new testing tools and approaches
- Maintain compatibility with application versions

## 📞 Support

### Getting Help
1. **Check this documentation** for common solutions
2. **Review test logs** for specific error messages
3. **Check tool documentation** for configuration issues
4. **Consult team members** for domain-specific questions

### Contributing
- Add new test cases for uncovered functionality
- Improve existing test cases
- Update documentation
- Share testing best practices

---

## 🎯 Success Metrics

A successful testing implementation should achieve:
- **100% Test Coverage** of critical functionality
- **>95% Pass Rate** on all test suites
- **<5 minutes** total execution time
- **Zero Critical Defects** in production
- **Automated Testing** for all deployment stages

Remember: **Quality is not an act, it is a habit.** Regular testing ensures your Mithi Calendar application remains reliable, secure, and performant for all users.
