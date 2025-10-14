@echo off
REM Calendar UI Selenium Tests Runner for Windows
REM This script provides a convenient way to run different test suites

setlocal enabledelayedexpansion

REM Default values
set BROWSER=chrome
set HEADLESS=false
set TIMEOUT=30000
set TEST_SUITE=all

REM Parse command line arguments
:parse_args
if "%~1"=="" goto :end_parse
if "%~1"=="-b" (
    set BROWSER=%~2
    shift
    shift
    goto :parse_args
)
if "%~1"=="--browser" (
    set BROWSER=%~2
    shift
    shift
    goto :parse_args
)
if "%~1"=="-h" (
    set HEADLESS=true
    shift
    goto :parse_args
)
if "%~1"=="--headless" (
    set HEADLESS=true
    shift
    goto :parse_args
)
if "%~1"=="-t" (
    set TIMEOUT=%~2
    shift
    shift
    goto :parse_args
)
if "%~1"=="--timeout" (
    set TIMEOUT=%~2
    shift
    shift
    goto :parse_args
)
if "%~1"=="-s" (
    set TEST_SUITE=%~2
    shift
    shift
    goto :parse_args
)
if "%~1"=="--suite" (
    set TEST_SUITE=%~2
    shift
    shift
    goto :parse_args
)
if "%~1"=="--help" (
    call :show_usage
    exit /b 0
)
echo [ERROR] Unknown option: %~1
call :show_usage
exit /b 1

:end_parse

REM Validate browser
if not "%BROWSER%"=="chrome" if not "%BROWSER%"=="firefox" (
    echo [ERROR] Invalid browser: %BROWSER%. Must be 'chrome' or 'firefox'
    exit /b 1
)

REM Validate test suite
if not "%TEST_SUITE%"=="all" if not "%TEST_SUITE%"=="login" if not "%TEST_SUITE%"=="calendar" if not "%TEST_SUITE%"=="events" if not "%TEST_SUITE%"=="recurring" (
    echo [ERROR] Invalid test suite: %TEST_SUITE%. Must be 'all', 'login', 'calendar', 'events', or 'recurring'
    exit /b 1
)

REM Check if we're in the right directory
if not exist "package.json" (
    echo [ERROR] package.json not found. Please run this script from the tests/selenium directory.
    exit /b 1
)

REM Check if node_modules exists
if not exist "node_modules" (
    echo [WARNING] node_modules not found. Installing dependencies...
    npm install
)

REM Check if .env file exists
if not exist ".env" (
    echo [WARNING] .env file not found. Creating from template...
    if exist "env.example" (
        copy env.example .env
        echo [WARNING] Please update .env file with your test credentials before running tests.
    ) else (
        echo [ERROR] env.example file not found. Please create .env file manually.
        exit /b 1
    )
)

REM Set environment variables
set BROWSER=%BROWSER%
set HEADLESS=%HEADLESS%
set TEST_TIMEOUT=%TIMEOUT%

REM Print test configuration
echo [INFO] Test Configuration:
echo   Browser: %BROWSER%
echo   Headless: %HEADLESS%
echo   Timeout: %TIMEOUT%ms
echo   Test Suite: %TEST_SUITE%
echo.

REM Create screenshots directory if it doesn't exist
if not exist "screenshots" mkdir screenshots

REM Run the appropriate test suite
if "%TEST_SUITE%"=="all" (
    echo [INFO] Running all test suites...
    if "%HEADLESS%"=="true" (
        npm run test:headless
    ) else (
        npm test
    )
) else if "%TEST_SUITE%"=="login" (
    call :run_tests "login.test.js" "Login"
) else if "%TEST_SUITE%"=="calendar" (
    call :run_tests "calendar.test.js" "Calendar Navigation"
) else if "%TEST_SUITE%"=="events" (
    call :run_tests "events.test.js" "Event Management"
) else if "%TEST_SUITE%"=="recurring" (
    call :run_tests "recurring-events.test.js" "Recurring Events"
)

REM Check if tests passed
if %ERRORLEVEL% equ 0 (
    echo [SUCCESS] All tests completed successfully!
    
    REM Show screenshot count if any were taken
    if exist "screenshots" (
        for /f %%i in ('dir /b screenshots\*.png 2^>nul ^| find /c /v ""') do set screenshot_count=%%i
        if defined screenshot_count (
            echo [INFO] Screenshots captured: !screenshot_count!
        )
    )
) else (
    echo [ERROR] Some tests failed. Check the output above for details.
    
    REM Show screenshot count
    if exist "screenshots" (
        for /f %%i in ('dir /b screenshots\*.png 2^>nul ^| find /c /v ""') do set screenshot_count=%%i
        if defined screenshot_count (
            echo [INFO] Screenshots captured: !screenshot_count!
            echo [INFO] Check the screenshots\ directory for failure screenshots.
        )
    )
    
    exit /b 1
)

goto :eof

REM Function to show usage
:show_usage
echo Usage: %~nx0 [OPTIONS]
echo.
echo Options:
echo   -b, --browser BROWSER     Browser to use (chrome, firefox) [default: chrome]
echo   -h, --headless            Run tests in headless mode
echo   -t, --timeout TIMEOUT     Test timeout in milliseconds [default: 30000]
echo   -s, --suite SUITE         Test suite to run [default: all]
echo                            Options: all, login, calendar, events, recurring
echo   --help                    Show this help message
echo.
echo Examples:
echo   %~nx0                        # Run all tests in Chrome
echo   %~nx0 -b firefox             # Run all tests in Firefox
echo   %~nx0 -h -s login            # Run login tests in headless Chrome
echo   %~nx0 -t 60000 -s events     # Run event tests with 60s timeout
goto :eof

REM Function to run tests
:run_tests
set test_file=%~1
set test_name=%~2
echo [INFO] Running %test_name% tests...

if "%HEADLESS%"=="true" (
    npx mocha "%test_file%" --timeout "%TIMEOUT%" --headless
) else (
    npx mocha "%test_file%" --timeout "%TIMEOUT%"
)
goto :eof
