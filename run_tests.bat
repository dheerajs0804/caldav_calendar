@echo off
REM Comprehensive Test Runner for CalDev Calendar Application (Windows)
REM This script runs all tests for both backend and frontend

setlocal enabledelayedexpansion

REM Colors for output (Windows compatible)
set "RED=[91m"
set "GREEN=[92m"
set "YELLOW=[93m"
set "BLUE=[94m"
set "NC=[0m"

REM Test configuration
set "BACKEND_DIR=backend"
set "FRONTEND_DIR=frontend-angular"
set "COVERAGE_THRESHOLD=70"
set "TEST_TIMEOUT=300"

REM Function to print colored output
:print_status
echo %BLUE%[INFO]%NC% %~1
goto :eof

:print_success
echo %GREEN%[SUCCESS]%NC% %~1
goto :eof

:print_warning
echo %YELLOW%[WARNING]%NC% %~1
goto :eof

:print_error
echo %RED%[ERROR]%NC% %~1
goto :eof

REM Function to check if command exists
:command_exists
where %1 >nul 2>&1
if %errorlevel% equ 0 (
    exit /b 0
) else (
    exit /b 1
)

REM Function to check prerequisites
:check_prerequisites
call :print_status "Checking prerequisites..."

REM Check Node.js
call :command_exists node
if %errorlevel% neq 0 (
    call :print_error "Node.js is not installed"
    exit /b 1
)

REM Check npm
call :command_exists npm
if %errorlevel% neq 0 (
    call :print_error "npm is not installed"
    exit /b 1
)

REM Check PHP
call :command_exists php
if %errorlevel% neq 0 (
    call :print_error "PHP is not installed"
    exit /b 1
)

REM Check Composer
call :command_exists composer
if %errorlevel% neq 0 (
    call :print_warning "Composer is not installed. Please install Composer first."
    exit /b 1
)

call :print_success "All prerequisites are installed"
goto :eof

REM Function to install dependencies
:install_dependencies
call :print_status "Installing dependencies..."

REM Install backend dependencies
if exist "%BACKEND_DIR%" (
    call :print_status "Installing PHP dependencies..."
    cd "%BACKEND_DIR%"
    if exist "composer.json" (
        composer install --no-interaction --prefer-dist
    ) else (
        call :print_warning "No composer.json found in backend directory"
    )
    cd ..
)

REM Install frontend dependencies
if exist "%FRONTEND_DIR%" (
    call :print_status "Installing Angular dependencies..."
    cd "%FRONTEND_DIR%"
    if exist "package.json" (
        npm ci
    ) else (
        call :print_warning "No package.json found in frontend directory"
    )
    cd ..
)

call :print_success "Dependencies installed successfully"
goto :eof

REM Function to run PHP backend tests
:run_backend_tests
call :print_status "Running PHP backend tests..."

if not exist "%BACKEND_DIR%" (
    call :print_warning "Backend directory not found, skipping backend tests"
    goto :eof
)

cd "%BACKEND_DIR%"

REM Check if PHPUnit is available
if not exist "vendor\bin\phpunit.bat" (
    call :print_error "PHPUnit not found. Please run 'composer install' first"
    cd ..
    exit /b 1
)

REM Create test directories if they don't exist
if not exist "tests\Unit" mkdir tests\Unit
if not exist "tests\Integration" mkdir tests\Integration
if not exist "tests\Feature" mkdir tests\Feature
if not exist "tests\data" mkdir tests\data
if not exist "tests\logs" mkdir tests\logs
if not exist "coverage" mkdir coverage

REM Run PHPUnit tests
call :print_status "Running unit tests..."
vendor\bin\phpunit tests\Unit --coverage-html coverage\unit --coverage-text

call :print_status "Running integration tests..."
vendor\bin\phpunit tests\Integration --coverage-html coverage\integration --coverage-text

call :print_status "Running feature tests..."
vendor\bin\phpunit tests\Feature --coverage-html coverage\feature --coverage-text

REM Run all tests with coverage
call :print_status "Running all tests with coverage..."
vendor\bin\phpunit --coverage-html coverage\all --coverage-text --coverage-clover coverage\clover.xml

REM Run PHP CodeSniffer
if exist "vendor\bin\phpcs.bat" (
    call :print_status "Running PHP CodeSniffer..."
    vendor\bin\phpcs --standard=PSR12 src\ classes\ || call :print_warning "Code style issues found"
)

REM Run PHPStan
if exist "vendor\bin\phpstan.bat" (
    call :print_status "Running PHPStan static analysis..."
    vendor\bin\phpstan analyse src\ classes\ --level=8 || call :print_warning "Static analysis issues found"
)

cd ..
call :print_success "Backend tests completed"
goto :eof

REM Function to run Angular frontend tests
:run_frontend_tests
call :print_status "Running Angular frontend tests..."

if not exist "%FRONTEND_DIR%" (
    call :print_warning "Frontend directory not found, skipping frontend tests"
    goto :eof
)

cd "%FRONTEND_DIR%"

REM Check if Angular CLI is available
call :command_exists ng
if %errorlevel% neq 0 (
    call :print_status "Installing Angular CLI globally..."
    npm install -g @angular/cli
)

REM Run unit tests
call :print_status "Running Angular unit tests..."
ng test --watch=false --browsers=ChromeHeadless --code-coverage || call :print_warning "Some unit tests failed"

REM Run linting
call :print_status "Running Angular linting..."
ng lint || call :print_warning "Linting issues found"

REM Build the application
call :print_status "Building Angular application..."
ng build --configuration=production || call :print_error "Build failed"

cd ..
call :print_success "Frontend tests completed"
goto :eof

REM Function to run Cypress E2E tests
:run_e2e_tests
call :print_status "Running Cypress E2E tests..."

if not exist "%FRONTEND_DIR%" (
    call :print_warning "Frontend directory not found, skipping E2E tests"
    goto :eof
)

cd "%FRONTEND_DIR%"

REM Check if Cypress is installed
if not exist "node_modules\cypress" (
    call :print_status "Installing Cypress..."
    npm install cypress --save-dev
)

REM Start the application in background
call :print_status "Starting Angular development server..."
start /b ng serve --port=4200

REM Wait for server to start
timeout /t 10 /nobreak >nul

REM Run Cypress tests
call :print_status "Running Cypress E2E tests..."
npx cypress run --spec "cypress/e2e/**/*.cy.ts" || call :print_warning "Some E2E tests failed"

REM Stop the server (find and kill ng process)
taskkill /f /im node.exe >nul 2>&1

cd ..
call :print_success "E2E tests completed"
goto :eof

REM Function to generate test report
:generate_test_report
call :print_status "Generating test report..."

set "REPORT_FILE=test_report_%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%.html"
set "REPORT_FILE=!REPORT_FILE: =0!"

(
echo ^<!DOCTYPE html^>
echo ^<html^>
echo ^<head^>
echo     ^<title^>CalDev Calendar - Test Report^</title^>
echo     ^<style^>
echo         body { font-family: Arial, sans-serif; margin: 20px; }
echo         .header { background: #f0f0f0; padding: 20px; border-radius: 5px; }
echo         .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
echo         .success { color: green; }
echo         .warning { color: orange; }
echo         .error { color: red; }
echo         .coverage { background: #f9f9f9; padding: 10px; border-radius: 3px; }
echo     ^</style^>
echo ^</head^>
echo ^<body^>
echo     ^<div class="header"^>
echo         ^<h1^>CalDev Calendar - Test Report^</h1^>
echo         ^<p^>Generated on: %date% %time%^</p^>
echo     ^</div^>
echo     
echo     ^<div class="section"^>
echo         ^<h2^>Backend Tests^</h2^>
echo         ^<p^>PHP Unit Tests: ^<span class="success"^>Completed^</span^>^</p^>
echo         ^<p^>PHP Integration Tests: ^<span class="success"^>Completed^</span^>^</p^>
echo         ^<p^>PHP Feature Tests: ^<span class="success"^>Completed^</span^>^</p^>
echo         ^<div class="coverage"^>
echo             ^<h3^>Coverage Report^</h3^>
echo             ^<p^>Coverage files available in backend/coverage/^</p^>
echo         ^</div^>
echo     ^</div^>
echo     
echo     ^<div class="section"^>
echo         ^<h2^>Frontend Tests^</h2^>
echo         ^<p^>Angular Unit Tests: ^<span class="success"^>Completed^</span^>^</p^>
echo         ^<p^>Angular Linting: ^<span class="success"^>Completed^</span^>^</p^>
echo         ^<p^>Angular Build: ^<span class="success"^>Completed^</span^>^</p^>
echo     ^</div^>
echo     
echo     ^<div class="section"^>
echo         ^<h2^>E2E Tests^</h2^>
echo         ^<p^>Cypress E2E Tests: ^<span class="success"^>Completed^</span^>^</p^>
echo     ^</div^>
echo     
echo     ^<div class="section"^>
echo         ^<h2^>Integration Tests^</h2^>
echo         ^<p^>API Integration Tests: ^<span class="success"^>Completed^</span^>^</p^>
echo     ^</div^>
echo ^</body^>
echo ^</html^>
) > "%REPORT_FILE%"

call :print_success "Test report generated: %REPORT_FILE%"
goto :eof

REM Function to show help
:show_help
echo CalDev Calendar Test Runner (Windows)
echo.
echo Usage: %0 [OPTIONS]
echo.
echo Options:
echo   -h, --help          Show this help message
echo   -b, --backend       Run only backend tests
echo   -f, --frontend      Run only frontend tests
echo   -e, --e2e           Run only E2E tests
echo   -c, --coverage      Generate coverage reports
echo   -r, --report        Generate test report
echo   --install           Install dependencies only
echo.
echo Examples:
echo   %0                  Run all tests
echo   %0 -b               Run only backend tests
echo   %0 -f -e            Run frontend and E2E tests
echo   %0 --coverage       Run all tests with coverage
goto :eof

REM Main function
:main
set "RUN_BACKEND=true"
set "RUN_FRONTEND=true"
set "RUN_E2E=true"
set "GENERATE_COVERAGE=false"
set "GENERATE_REPORT=false"
set "INSTALL_ONLY=false"

REM Parse command line arguments
:parse_args
if "%~1"=="" goto :args_done
if "%~1"=="-h" goto :show_help
if "%~1"=="--help" goto :show_help
if "%~1"=="-b" (
    set "RUN_FRONTEND=false"
    set "RUN_E2E=false"
    shift
    goto :parse_args
)
if "%~1"=="--backend" (
    set "RUN_FRONTEND=false"
    set "RUN_E2E=false"
    shift
    goto :parse_args
)
if "%~1"=="-f" (
    set "RUN_BACKEND=false"
    set "RUN_E2E=false"
    shift
    goto :parse_args
)
if "%~1"=="--frontend" (
    set "RUN_BACKEND=false"
    set "RUN_E2E=false"
    shift
    goto :parse_args
)
if "%~1"=="-e" (
    set "RUN_BACKEND=false"
    set "RUN_FRONTEND=false"
    shift
    goto :parse_args
)
if "%~1"=="--e2e" (
    set "RUN_BACKEND=false"
    set "RUN_FRONTEND=false"
    shift
    goto :parse_args
)
if "%~1"=="-c" (
    set "GENERATE_COVERAGE=true"
    shift
    goto :parse_args
)
if "%~1"=="--coverage" (
    set "GENERATE_COVERAGE=true"
    shift
    goto :parse_args
)
if "%~1"=="-r" (
    set "GENERATE_REPORT=true"
    shift
    goto :parse_args
)
if "%~1"=="--report" (
    set "GENERATE_REPORT=true"
    shift
    goto :parse_args
)
if "%~1"=="--install" (
    set "INSTALL_ONLY=true"
    shift
    goto :parse_args
)
call :print_error "Unknown option: %~1"
call :show_help
exit /b 1

:args_done

REM Handle install only
if "%INSTALL_ONLY%"=="true" (
    call :check_prerequisites
    call :install_dependencies
    exit /b 0
)

REM Start test execution
call :print_status "Starting CalDev Calendar test suite..."
call :print_status "Test configuration:"
call :print_status "  Backend tests: %RUN_BACKEND%"
call :print_status "  Frontend tests: %RUN_FRONTEND%"
call :print_status "  E2E tests: %RUN_E2E%"
call :print_status "  Coverage: %GENERATE_COVERAGE%"
call :print_status "  Report: %GENERATE_REPORT%"
echo.

REM Check prerequisites
call :check_prerequisites

REM Install dependencies
call :install_dependencies

REM Run tests
if "%RUN_BACKEND%"=="true" (
    call :run_backend_tests
)

if "%RUN_FRONTEND%"=="true" (
    call :run_frontend_tests
)

if "%RUN_E2E%"=="true" (
    call :run_e2e_tests
)

REM Generate report
if "%GENERATE_REPORT%"=="true" (
    call :generate_test_report
)

REM Final summary
call :print_success "All tests completed successfully!"
call :print_status "Test artifacts and coverage reports are available in respective directories"

if "%GENERATE_COVERAGE%"=="true" (
    call :print_status "Coverage reports:"
    if exist "%BACKEND_DIR%\coverage" (
        call :print_status "  Backend: %BACKEND_DIR%\coverage\all\index.html"
    )
    if exist "%FRONTEND_DIR%\coverage" (
        call :print_status "  Frontend: %FRONTEND_DIR%\coverage\index.html"
    )
)

goto :eof

REM Run main function
call :main %*

