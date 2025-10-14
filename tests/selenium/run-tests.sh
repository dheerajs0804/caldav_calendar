#!/bin/bash

# Calendar UI Selenium Tests Runner
# This script provides a convenient way to run different test suites

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
BROWSER="chrome"
HEADLESS="false"
TIMEOUT="30000"
TEST_SUITE="all"

# Function to print colored output
print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -b, --browser BROWSER     Browser to use (chrome, firefox) [default: chrome]"
    echo "  -h, --headless            Run tests in headless mode"
    echo "  -t, --timeout TIMEOUT     Test timeout in milliseconds [default: 30000]"
    echo "  -s, --suite SUITE         Test suite to run [default: all]"
    echo "                            Options: all, login, calendar, events, recurring"
    echo "  --help                    Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0                        # Run all tests in Chrome"
    echo "  $0 -b firefox             # Run all tests in Firefox"
    echo "  $0 -h -s login            # Run login tests in headless Chrome"
    echo "  $0 -t 60000 -s events     # Run event tests with 60s timeout"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -b|--browser)
            BROWSER="$2"
            shift 2
            ;;
        -h|--headless)
            HEADLESS="true"
            shift
            ;;
        -t|--timeout)
            TIMEOUT="$2"
            shift 2
            ;;
        -s|--suite)
            TEST_SUITE="$2"
            shift 2
            ;;
        --help)
            show_usage
            exit 0
            ;;
        *)
            print_error "Unknown option: $1"
            show_usage
            exit 1
            ;;
    esac
done

# Validate browser
if [[ "$BROWSER" != "chrome" && "$BROWSER" != "firefox" ]]; then
    print_error "Invalid browser: $BROWSER. Must be 'chrome' or 'firefox'"
    exit 1
fi

# Validate test suite
if [[ "$TEST_SUITE" != "all" && "$TEST_SUITE" != "login" && "$TEST_SUITE" != "calendar" && "$TEST_SUITE" != "events" && "$TEST_SUITE" != "recurring" ]]; then
    print_error "Invalid test suite: $TEST_SUITE. Must be 'all', 'login', 'calendar', 'events', or 'recurring'"
    exit 1
fi

# Check if we're in the right directory
if [[ ! -f "package.json" ]]; then
    print_error "package.json not found. Please run this script from the tests/selenium directory."
    exit 1
fi

# Check if node_modules exists
if [[ ! -d "node_modules" ]]; then
    print_warning "node_modules not found. Installing dependencies..."
    npm install
fi

# Check if .env file exists
if [[ ! -f ".env" ]]; then
    print_warning ".env file not found. Creating from template..."
    if [[ -f "env.example" ]]; then
        cp env.example .env
        print_warning "Please update .env file with your test credentials before running tests."
    else
        print_error "env.example file not found. Please create .env file manually."
        exit 1
    fi
fi

# Set environment variables
export BROWSER="$BROWSER"
export HEADLESS="$HEADLESS"
export TEST_TIMEOUT="$TIMEOUT"

# Print test configuration
print_info "Test Configuration:"
echo "  Browser: $BROWSER"
echo "  Headless: $HEADLESS"
echo "  Timeout: ${TIMEOUT}ms"
echo "  Test Suite: $TEST_SUITE"
echo ""

# Create screenshots directory if it doesn't exist
mkdir -p screenshots

# Function to run tests
run_tests() {
    local test_file="$1"
    local test_name="$2"
    
    print_info "Running $test_name tests..."
    
    if [[ "$HEADLESS" == "true" ]]; then
        npx mocha "$test_file" --timeout "$TIMEOUT" --headless
    else
        npx mocha "$test_file" --timeout "$TIMEOUT"
    fi
}

# Run the appropriate test suite
case "$TEST_SUITE" in
    "all")
        print_info "Running all test suites..."
        if [[ "$HEADLESS" == "true" ]]; then
            npm run test:headless
        else
            npm test
        fi
        ;;
    "login")
        run_tests "login.test.js" "Login"
        ;;
    "calendar")
        run_tests "calendar.test.js" "Calendar Navigation"
        ;;
    "events")
        run_tests "events.test.js" "Event Management"
        ;;
    "recurring")
        run_tests "recurring-events.test.js" "Recurring Events"
        ;;
esac

# Check if tests passed
if [[ $? -eq 0 ]]; then
    print_success "All tests completed successfully!"
    
    # Show screenshot count if any were taken
    if [[ -d "screenshots" ]]; then
        screenshot_count=$(ls -1 screenshots/*.png 2>/dev/null | wc -l)
        if [[ $screenshot_count -gt 0 ]]; then
            print_info "Screenshots captured: $screenshot_count"
        fi
    fi
else
    print_error "Some tests failed. Check the output above for details."
    
    # Show screenshot count
    if [[ -d "screenshots" ]]; then
        screenshot_count=$(ls -1 screenshots/*.png 2>/dev/null | wc -l)
        if [[ $screenshot_count -gt 0 ]]; then
            print_info "Screenshots captured: $screenshot_count"
            print_info "Check the screenshots/ directory for failure screenshots."
        fi
    fi
    
    exit 1
fi
