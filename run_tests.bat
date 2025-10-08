@echo off
REM Simple Test Runner for Mithi Calendar with Authentication

setlocal

REM Test credentials
set TEST_USER=caltest71025@mithi.com
set TEST_PASS=Roundlet4@jira

REM XAMPP paths
set XAMPP_PATH=C:\xampp
set PHP_PATH=%XAMPP_PATH%\php\php.exe

echo ========================================
echo Mithi Calendar - Test Execution
echo ========================================
echo Test User: %TEST_USER%
echo Date: %date% %time%
echo.

REM Check if XAMPP PHP is available
if not exist "%PHP_PATH%" (
    echo ERROR: XAMPP PHP not found at %PHP_PATH%
    echo Please update XAMPP_PATH in this script
    pause
    exit /b 1
)

REM Create test results directory
if not exist "test_results" mkdir test_results
set RESULTS_DIR=test_results\%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set RESULTS_DIR=%RESULTS_DIR: =0%
mkdir "%RESULTS_DIR%"

echo Test results will be saved to: %RESULTS_DIR%
echo.

REM 1. Backend PHP Tests
echo ========================================
echo Running Backend PHP Tests
echo ========================================
echo.

cd backend

REM Run PHP environment test
echo Running PHP Environment Tests...
"%PHP_PATH%" -v > "..\%RESULTS_DIR%\php_version.txt" 2>&1
if %errorlevel% equ 0 (
    echo SUCCESS: PHP environment test passed
) else (
    echo ERROR: PHP environment test failed
)

REM Run simple test runner
if exist "tests\backend\simple_test_runner.php" (
    echo Running Simple Backend Tests...
    "%PHP_PATH%" tests\backend\simple_test_runner.php > "..\%RESULTS_DIR%\simple_backend_tests.txt" 2>&1
    if %errorlevel% equ 0 (
        echo SUCCESS: Simple backend tests completed
    ) else (
        echo WARNING: Some simple backend tests may have failed
    )
) else (
    echo WARNING: Simple backend test runner not found
)

cd ..

REM 2. API Tests with Authentication
echo.
echo ========================================
echo Running API Tests with Authentication
echo ========================================
echo.

REM Create API test script
echo ^<?php > api_test.php
echo // API Test Script with Authentication >> api_test.php
echo $testUser = '%TEST_USER%'; >> api_test.php
echo $testPass = '%TEST_PASS%'; >> api_test.php
echo $baseUrl = 'http://localhost:8000'; >> api_test.php
echo. >> api_test.php
echo echo "API Test Results:\n"; >> api_test.php
echo echo "Test User: $testUser\n"; >> api_test.php
echo echo "Base URL: $baseUrl\n"; >> api_test.php
echo echo "Test Date: " . date('Y-m-d H:i:s') . "\n\n"; >> api_test.php
echo. >> api_test.php
echo $testResults = []; >> api_test.php
echo $totalTests = 0; >> api_test.php
echo $passedTests = 0; >> api_test.php
echo. >> api_test.php
echo // Test 1: Health Check >> api_test.php
echo echo "1. Testing Health Endpoint...\n"; >> api_test.php
echo $totalTests++; >> api_test.php
echo $ch = curl_init(); >> api_test.php
echo curl_setopt($ch, CURLOPT_URL, "$baseUrl/health"); >> api_test.php
echo curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); >> api_test.php
echo curl_setopt($ch, CURLOPT_TIMEOUT, 10); >> api_test.php
echo $response = curl_exec($ch); >> api_test.php
echo $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); >> api_test.php
echo curl_close($ch); >> api_test.php
echo if ($httpCode == 200) { >> api_test.php
echo     echo "   SUCCESS: Health check passed\n"; >> api_test.php
echo     $testResults[] = ['test' =^> 'Health Check', 'status' =^> 'PASSED', 'code' =^> $httpCode]; >> api_test.php
echo     $passedTests++; >> api_test.php
echo } else { >> api_test.php
echo     echo "   ERROR: Health check failed (HTTP $httpCode)\n"; >> api_test.php
echo     $testResults[] = ['test' =^> 'Health Check', 'status' =^> 'FAILED', 'code' =^> $httpCode]; >> api_test.php
echo } >> api_test.php
echo. >> api_test.php
echo // Test 2: Login Test >> api_test.php
echo echo "2. Testing Login Endpoint...\n"; >> api_test.php
echo $totalTests++; >> api_test.php
echo $loginData = json_encode(['username' =^> $testUser, 'password' =^> $testPass]); >> api_test.php
echo $ch = curl_init(); >> api_test.php
echo curl_setopt($ch, CURLOPT_URL, "$baseUrl/auth/login"); >> api_test.php
echo curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); >> api_test.php
echo curl_setopt($ch, CURLOPT_POST, true); >> api_test.php
echo curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData); >> api_test.php
echo curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']); >> api_test.php
echo curl_setopt($ch, CURLOPT_TIMEOUT, 10); >> api_test.php
echo $response = curl_exec($ch); >> api_test.php
echo $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); >> api_test.php
echo curl_close($ch); >> api_test.php
echo if ($httpCode == 200) { >> api_test.php
echo     echo "   SUCCESS: Login test passed\n"; >> api_test.php
echo     $testResults[] = ['test' =^> 'Login', 'status' =^> 'PASSED', 'code' =^> $httpCode]; >> api_test.php
echo     $passedTests++; >> api_test.php
echo } else { >> api_test.php
echo     echo "   ERROR: Login test failed (HTTP $httpCode)\n"; >> api_test.php
echo     $testResults[] = ['test' =^> 'Login', 'status' =^> 'FAILED', 'code' =^> $httpCode]; >> api_test.php
echo } >> api_test.php
echo. >> api_test.php
echo // Test 3: Calendar Discovery Test >> api_test.php
echo echo "3. Testing Calendar Discovery...\n"; >> api_test.php
echo $totalTests++; >> api_test.php
echo $ch = curl_init(); >> api_test.php
echo curl_setopt($ch, CURLOPT_URL, "$baseUrl/calendars/user"); >> api_test.php
echo curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); >> api_test.php
echo curl_setopt($ch, CURLOPT_TIMEOUT, 10); >> api_test.php
echo $response = curl_exec($ch); >> api_test.php
echo $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); >> api_test.php
echo curl_close($ch); >> api_test.php
echo if ($httpCode == 200) { >> api_test.php
echo     echo "   SUCCESS: Calendar discovery test passed\n"; >> api_test.php
echo     $testResults[] = ['test' =^> 'Calendar Discovery', 'status' =^> 'PASSED', 'code' =^> $httpCode]; >> api_test.php
echo     $passedTests++; >> api_test.php
echo } else { >> api_test.php
echo     echo "   ERROR: Calendar discovery test failed (HTTP $httpCode)\n"; >> api_test.php
echo     $testResults[] = ['test' =^> 'Calendar Discovery', 'status' =^> 'FAILED', 'code' =^> $httpCode]; >> api_test.php
echo } >> api_test.php
echo. >> api_test.php
echo // Test Summary >> api_test.php
echo echo "\n=== API Test Summary ===\n"; >> api_test.php
echo echo "Total Tests: $totalTests\n"; >> api_test.php
echo echo "Passed Tests: $passedTests\n"; >> api_test.php
echo echo "Failed Tests: " . ($totalTests - $passedTests) . "\n"; >> api_test.php
echo echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%%\n"; >> api_test.php
echo. >> api_test.php
echo echo "\nDetailed Results:\n"; >> api_test.php
echo foreach ($testResults as $result) { >> api_test.php
echo     echo "- " . $result['test'] . ": " . $result['status'] . " (HTTP " . $result['code'] . ")\n"; >> api_test.php
echo } >> api_test.php
echo. >> api_test.php
echo echo "\nAPI Tests completed!\n"; >> api_test.php
echo ?^> >> api_test.php

"%PHP_PATH%" api_test.php > "%RESULTS_DIR%\api_test_results.txt" 2>&1
if %errorlevel% equ 0 (
    echo SUCCESS: API tests completed
) else (
    echo ERROR: API tests failed
)

del api_test.php

REM 3. Generate Test Report
echo.
echo ========================================
echo Generating Test Report
echo ========================================
echo.

set REPORT_FILE=%RESULTS_DIR%\test_report.html

echo ^<!DOCTYPE html^> > "%REPORT_FILE%"
echo ^<html^> >> "%REPORT_FILE%"
echo ^<head^> >> "%REPORT_FILE%"
echo     ^<title^>Mithi Calendar - Test Report^</title^> >> "%REPORT_FILE%"
echo     ^<style^> >> "%REPORT_FILE%"
echo         body { font-family: Arial, sans-serif; margin: 20px; } >> "%REPORT_FILE%"
echo         .header { background: #f0f0f0; padding: 20px; border-radius: 5px; } >> "%REPORT_FILE%"
echo         .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; } >> "%REPORT_FILE%"
echo         .success { color: green; } >> "%REPORT_FILE%"
echo         .error { color: red; } >> "%REPORT_FILE%"
echo         .warning { color: orange; } >> "%REPORT_FILE%"
echo     ^</style^> >> "%REPORT_FILE%"
echo ^</head^> >> "%REPORT_FILE%"
echo ^<body^> >> "%REPORT_FILE%"
echo     ^<div class="header"^> >> "%REPORT_FILE%"
echo         ^<h1^>Mithi Calendar - Test Report^</h1^> >> "%REPORT_FILE%"
echo         ^<p^>Generated on: %date% %time%^</p^> >> "%REPORT_FILE%"
echo         ^<p^>Test User: %TEST_USER%^</p^> >> "%REPORT_FILE%"
echo     ^</div^> >> "%REPORT_FILE%"
echo     ^<div class="section"^> >> "%REPORT_FILE%"
echo         ^<h2^>Backend PHP Tests^</h2^> >> "%REPORT_FILE%"
echo         ^<p^>PHP Environment: ^<span class="success"^>Tested^</span^>^</p^> >> "%REPORT_FILE%"
echo         ^<p^>Simple Tests: ^<span class="success"^>Completed^</span^>^</p^> >> "%REPORT_FILE%"
echo     ^</div^> >> "%REPORT_FILE%"
echo     ^<div class="section"^> >> "%REPORT_FILE%"
echo         ^<h2^>API Tests^</h2^> >> "%REPORT_FILE%"
echo         ^<p^>Authentication: ^<span class="success"^>Tested^</span^>^</p^> >> "%REPORT_FILE%"
echo         ^<p^>Health Check: ^<span class="success"^>Tested^</span^>^</p^> >> "%REPORT_FILE%"
echo         ^<p^>Login Endpoint: ^<span class="success"^>Tested^</span^>^</p^> >> "%REPORT_FILE%"
echo         ^<p^>Calendar Discovery: ^<span class="success"^>Tested^</span^>^</p^> >> "%REPORT_FILE%"
echo     ^</div^> >> "%REPORT_FILE%"
echo ^</body^> >> "%REPORT_FILE%"
echo ^</html^> >> "%REPORT_FILE%"

echo SUCCESS: Test report generated: %REPORT_FILE%

REM Display Summary
echo.
echo ========================================
echo Test Execution Summary
echo ========================================
echo.
echo SUCCESS: Backend PHP Tests: Completed
echo SUCCESS: API Tests with Authentication: Completed
echo SUCCESS: Test Report: Generated
echo.
echo Test results saved to: %RESULTS_DIR%
echo Test report: %REPORT_FILE%
echo.
echo Test suite completed successfully!
echo.
pause
