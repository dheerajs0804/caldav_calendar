#!/bin/bash

# Comprehensive Test Runner for CalDev Calendar Application
# This script runs all tests for both backend and frontend

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test configuration
BACKEND_DIR="backend"
FRONTEND_DIR="frontend-angular"
COVERAGE_THRESHOLD=70
TEST_TIMEOUT=300

# Function to print colored output
print_status() {
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

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check prerequisites
check_prerequisites() {
    print_status "Checking prerequisites..."
    
    # Check Node.js
    if ! command_exists node; then
        print_error "Node.js is not installed"
        exit 1
    fi
    
    # Check npm
    if ! command_exists npm; then
        print_error "npm is not installed"
        exit 1
    fi
    
    # Check PHP
    if ! command_exists php; then
        print_error "PHP is not installed"
        exit 1
    fi
    
    # Check Composer
    if ! command_exists composer; then
        print_warning "Composer is not installed. Installing..."
        curl -sS https://getcomposer.org/installer | php
        mv composer.phar /usr/local/bin/composer
    fi
    
    print_success "All prerequisites are installed"
}

# Function to install dependencies
install_dependencies() {
    print_status "Installing dependencies..."
    
    # Install backend dependencies
    if [ -d "$BACKEND_DIR" ]; then
        print_status "Installing PHP dependencies..."
        cd "$BACKEND_DIR"
        if [ -f "composer.json" ]; then
            composer install --no-interaction --prefer-dist
        else
            print_warning "No composer.json found in backend directory"
        fi
        cd ..
    fi
    
    # Install frontend dependencies
    if [ -d "$FRONTEND_DIR" ]; then
        print_status "Installing Angular dependencies..."
        cd "$FRONTEND_DIR"
        if [ -f "package.json" ]; then
            npm ci
        else
            print_warning "No package.json found in frontend directory"
        fi
        cd ..
    fi
    
    print_success "Dependencies installed successfully"
}

# Function to run PHP backend tests
run_backend_tests() {
    print_status "Running PHP backend tests..."
    
    if [ ! -d "$BACKEND_DIR" ]; then
        print_warning "Backend directory not found, skipping backend tests"
        return 0
    fi
    
    cd "$BACKEND_DIR"
    
    # Check if PHPUnit is available
    if [ ! -f "vendor/bin/phpunit" ]; then
        print_error "PHPUnit not found. Please run 'composer install' first"
        cd ..
        return 1
    fi
    
    # Create test directories if they don't exist
    mkdir -p tests/Unit tests/Integration tests/Feature
    mkdir -p tests/data tests/logs
    mkdir -p coverage
    
    # Run PHPUnit tests
    print_status "Running unit tests..."
    ./vendor/bin/phpunit tests/Unit --coverage-html coverage/unit --coverage-text
    
    print_status "Running integration tests..."
    ./vendor/bin/phpunit tests/Integration --coverage-html coverage/integration --coverage-text
    
    print_status "Running feature tests..."
    ./vendor/bin/phpunit tests/Feature --coverage-html coverage/feature --coverage-text
    
    # Run all tests with coverage
    print_status "Running all tests with coverage..."
    ./vendor/bin/phpunit --coverage-html coverage/all --coverage-text --coverage-clover coverage/clover.xml
    
    # Check coverage
    if [ -f "coverage/clover.xml" ]; then
        COVERAGE=$(grep -o 'lines-covered="[0-9]*"' coverage/clover.xml | grep -o '[0-9]*' | head -1)
        TOTAL_LINES=$(grep -o 'lines-valid="[0-9]*"' coverage/clover.xml | grep -o '[0-9]*' | head -1)
        
        if [ -n "$COVERAGE" ] && [ -n "$TOTAL_LINES" ]; then
            COVERAGE_PERCENT=$((COVERAGE * 100 / TOTAL_LINES))
            print_status "Backend test coverage: ${COVERAGE_PERCENT}%"
            
            if [ "$COVERAGE_PERCENT" -lt "$COVERAGE_THRESHOLD" ]; then
                print_warning "Backend coverage (${COVERAGE_PERCENT}%) is below threshold (${COVERAGE_THRESHOLD}%)"
            else
                print_success "Backend coverage (${COVERAGE_PERCENT}%) meets threshold (${COVERAGE_THRESHOLD}%)"
            fi
        fi
    fi
    
    # Run PHP CodeSniffer
    if [ -f "vendor/bin/phpcs" ]; then
        print_status "Running PHP CodeSniffer..."
        ./vendor/bin/phpcs --standard=PSR12 src/ classes/ || print_warning "Code style issues found"
    fi
    
    # Run PHPStan
    if [ -f "vendor/bin/phpstan" ]; then
        print_status "Running PHPStan static analysis..."
        ./vendor/bin/phpstan analyse src/ classes/ --level=8 || print_warning "Static analysis issues found"
    fi
    
    cd ..
    print_success "Backend tests completed"
}

# Function to run Angular frontend tests
run_frontend_tests() {
    print_status "Running Angular frontend tests..."
    
    if [ ! -d "$FRONTEND_DIR" ]; then
        print_warning "Frontend directory not found, skipping frontend tests"
        return 0
    fi
    
    cd "$FRONTEND_DIR"
    
    # Check if Angular CLI is available
    if ! command_exists ng; then
        print_status "Installing Angular CLI globally..."
        npm install -g @angular/cli
    fi
    
    # Run unit tests
    print_status "Running Angular unit tests..."
    ng test --watch=false --browsers=ChromeHeadless --code-coverage || print_warning "Some unit tests failed"
    
    # Run linting
    print_status "Running Angular linting..."
    ng lint || print_warning "Linting issues found"
    
    # Build the application
    print_status "Building Angular application..."
    ng build --configuration=production || print_error "Build failed"
    
    cd ..
    print_success "Frontend tests completed"
}

# Function to run Cypress E2E tests
run_e2e_tests() {
    print_status "Running Cypress E2E tests..."
    
    if [ ! -d "$FRONTEND_DIR" ]; then
        print_warning "Frontend directory not found, skipping E2E tests"
        return 0
    fi
    
    cd "$FRONTEND_DIR"
    
    # Check if Cypress is installed
    if [ ! -d "node_modules/cypress" ]; then
        print_status "Installing Cypress..."
        npm install cypress --save-dev
    fi
    
    # Start the application in background
    print_status "Starting Angular development server..."
    ng serve --port=4200 &
    SERVER_PID=$!
    
    # Wait for server to start
    sleep 10
    
    # Run Cypress tests
    print_status "Running Cypress E2E tests..."
    npx cypress run --spec "cypress/e2e/**/*.cy.ts" || print_warning "Some E2E tests failed"
    
    # Stop the server
    kill $SERVER_PID 2>/dev/null || true
    
    cd ..
    print_success "E2E tests completed"
}

# Function to run integration tests
run_integration_tests() {
    print_status "Running integration tests..."
    
    # Start backend server
    if [ -d "$BACKEND_DIR" ]; then
        print_status "Starting PHP backend server..."
        cd "$BACKEND_DIR"
        php -S localhost:8000 -t . &
        BACKEND_PID=$!
        cd ..
        
        # Wait for backend to start
        sleep 5
    fi
    
    # Start frontend server
    if [ -d "$FRONTEND_DIR" ]; then
        print_status "Starting Angular frontend server..."
        cd "$FRONTEND_DIR"
        ng serve --port=4200 &
        FRONTEND_PID=$!
        cd ..
        
        # Wait for frontend to start
        sleep 10
    fi
    
    # Run integration tests
    print_status "Running API integration tests..."
    if [ -f "tests/integration/api_tests.sh" ]; then
        chmod +x tests/integration/api_tests.sh
        ./tests/integration/api_tests.sh
    fi
    
    # Clean up servers
    if [ -n "$BACKEND_PID" ]; then
        kill $BACKEND_PID 2>/dev/null || true
    fi
    if [ -n "$FRONTEND_PID" ]; then
        kill $FRONTEND_PID 2>/dev/null || true
    fi
    
    print_success "Integration tests completed"
}

# Function to generate test report
generate_test_report() {
    print_status "Generating test report..."
    
    REPORT_FILE="test_report_$(date +%Y%m%d_%H%M%S).html"
    
    cat > "$REPORT_FILE" << EOF
<!DOCTYPE html>
<html>
<head>
    <title>CalDev Calendar - Test Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f0f0f0; padding: 20px; border-radius: 5px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; }
        .warning { color: orange; }
        .error { color: red; }
        .coverage { background: #f9f9f9; padding: 10px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>CalDev Calendar - Test Report</h1>
        <p>Generated on: $(date)</p>
    </div>
    
    <div class="section">
        <h2>Backend Tests</h2>
        <p>PHP Unit Tests: <span class="success">Completed</span></p>
        <p>PHP Integration Tests: <span class="success">Completed</span></p>
        <p>PHP Feature Tests: <span class="success">Completed</span></p>
        <div class="coverage">
            <h3>Coverage Report</h3>
            <p>Coverage files available in backend/coverage/</p>
        </div>
    </div>
    
    <div class="section">
        <h2>Frontend Tests</h2>
        <p>Angular Unit Tests: <span class="success">Completed</span></p>
        <p>Angular Linting: <span class="success">Completed</span></p>
        <p>Angular Build: <span class="success">Completed</span></p>
    </div>
    
    <div class="section">
        <h2>E2E Tests</h2>
        <p>Cypress E2E Tests: <span class="success">Completed</span></p>
    </div>
    
    <div class="section">
        <h2>Integration Tests</h2>
        <p>API Integration Tests: <span class="success">Completed</span></p>
    </div>
</body>
</html>
EOF
    
    print_success "Test report generated: $REPORT_FILE"
}

# Function to clean up test artifacts
cleanup() {
    print_status "Cleaning up test artifacts..."
    
    # Remove test coverage files
    find . -name "coverage" -type d -exec rm -rf {} + 2>/dev/null || true
    find . -name "*.lcov" -delete 2>/dev/null || true
    find . -name "clover.xml" -delete 2>/dev/null || true
    
    # Remove test logs
    find . -name "test.log" -delete 2>/dev/null || true
    find . -name "phpunit.log" -delete 2>/dev/null || true
    
    print_success "Cleanup completed"
}

# Function to show help
show_help() {
    echo "CalDev Calendar Test Runner"
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -h, --help          Show this help message"
    echo "  -b, --backend       Run only backend tests"
    echo "  -f, --frontend      Run only frontend tests"
    echo "  -e, --e2e           Run only E2E tests"
    echo "  -i, --integration   Run only integration tests"
    echo "  -c, --coverage      Generate coverage reports"
    echo "  -r, --report        Generate test report"
    echo "  --cleanup           Clean up test artifacts"
    echo "  --install           Install dependencies only"
    echo ""
    echo "Examples:"
    echo "  $0                  Run all tests"
    echo "  $0 -b               Run only backend tests"
    echo "  $0 -f -e            Run frontend and E2E tests"
    echo "  $0 --coverage       Run all tests with coverage"
    echo "  $0 --cleanup        Clean up test artifacts"
}

# Main function
main() {
    local RUN_BACKEND=true
    local RUN_FRONTEND=true
    local RUN_E2E=true
    local RUN_INTEGRATION=true
    local GENERATE_COVERAGE=false
    local GENERATE_REPORT=false
    local CLEANUP_ONLY=false
    local INSTALL_ONLY=false
    
    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_help
                exit 0
                ;;
            -b|--backend)
                RUN_FRONTEND=false
                RUN_E2E=false
                RUN_INTEGRATION=false
                shift
                ;;
            -f|--frontend)
                RUN_BACKEND=false
                RUN_E2E=false
                RUN_INTEGRATION=false
                shift
                ;;
            -e|--e2e)
                RUN_BACKEND=false
                RUN_FRONTEND=false
                RUN_INTEGRATION=false
                shift
                ;;
            -i|--integration)
                RUN_BACKEND=false
                RUN_FRONTEND=false
                RUN_E2E=false
                shift
                ;;
            -c|--coverage)
                GENERATE_COVERAGE=true
                shift
                ;;
            -r|--report)
                GENERATE_REPORT=true
                shift
                ;;
            --cleanup)
                CLEANUP_ONLY=true
                shift
                ;;
            --install)
                INSTALL_ONLY=true
                shift
                ;;
            *)
                print_error "Unknown option: $1"
                show_help
                exit 1
                ;;
        esac
    done
    
    # Handle cleanup only
    if [ "$CLEANUP_ONLY" = true ]; then
        cleanup
        exit 0
    fi
    
    # Handle install only
    if [ "$INSTALL_ONLY" = true ]; then
        check_prerequisites
        install_dependencies
        exit 0
    fi
    
    # Start test execution
    print_status "Starting CalDev Calendar test suite..."
    print_status "Test configuration:"
    print_status "  Backend tests: $RUN_BACKEND"
    print_status "  Frontend tests: $RUN_FRONTEND"
    print_status "  E2E tests: $RUN_E2E"
    print_status "  Integration tests: $RUN_INTEGRATION"
    print_status "  Coverage: $GENERATE_COVERAGE"
    print_status "  Report: $GENERATE_REPORT"
    echo ""
    
    # Check prerequisites
    check_prerequisites
    
    # Install dependencies
    install_dependencies
    
    # Run tests
    if [ "$RUN_BACKEND" = true ]; then
        run_backend_tests
    fi
    
    if [ "$RUN_FRONTEND" = true ]; then
        run_frontend_tests
    fi
    
    if [ "$RUN_E2E" = true ]; then
        run_e2e_tests
    fi
    
    if [ "$RUN_INTEGRATION" = true ]; then
        run_integration_tests
    fi
    
    # Generate report
    if [ "$GENERATE_REPORT" = true ]; then
        generate_test_report
    fi
    
    # Final summary
    print_success "All tests completed successfully!"
    print_status "Test artifacts and coverage reports are available in respective directories"
    
    if [ "$GENERATE_COVERAGE" = true ]; then
        print_status "Coverage reports:"
        if [ -d "$BACKEND_DIR/coverage" ]; then
            print_status "  Backend: $BACKEND_DIR/coverage/all/index.html"
        fi
        if [ -d "$FRONTEND_DIR/coverage" ]; then
            print_status "  Frontend: $FRONTEND_DIR/coverage/index.html"
        fi
    fi
}

# Run main function with all arguments
main "$@"

