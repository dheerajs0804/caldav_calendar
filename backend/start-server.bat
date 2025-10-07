@echo off
echo Starting CalDAV Calendar Backend Server...
echo.
echo Server will be available at: http://localhost:8000
echo Press Ctrl+C to stop the server
echo.
cd /d "%~dp0"
C:\xampp\php\php.exe -S localhost:8000 index.php
pause
