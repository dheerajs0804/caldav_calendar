@echo off
echo === Mithi Calendar Backend Test Runner ===
echo.

REM Try to find PHP in common locations
set PHP_PATHS=^
C:\xampp\php\php.exe;^
C:\wamp\bin\php\php8.1.0\php.exe;^
C:\wamp64\bin\php\php8.1.0\php.exe;^
C:\laragon\bin\php\php-8.1.0-Win32-VS16-x64\php.exe;^
php.exe

for %%p in (%PHP_PATHS%) do (
    if exist "%%p" (
        echo Found PHP at: %%p
        echo.
        "%%p" tests\backend\simple_test_runner.php
        goto :end
    )
)

echo ERROR: PHP not found in common locations.
echo Please ensure XAMPP, WAMP, or Laragon is installed and PHP is in your PATH.
echo.
echo You can also manually run:
echo   C:\xampp\php\php.exe tests\backend\simple_test_runner.php
echo.
pause

:end
pause
