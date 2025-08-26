@echo off
echo ========================================
echo    PHP Installation Helper Script
echo ========================================
echo.

echo This script will help you install PHP on Windows
echo.

echo Step 1: Download PHP
echo - Go to: https://windows.php.net/download/
echo - Download "VS16 x64 Thread Safe" version
echo - Extract to C:\php
echo.

echo Step 2: Add PHP to PATH
echo - Press Win + R, type: sysdm.cpl
echo - Click "Environment Variables"
echo - Under "System Variables", find "Path"
echo - Click "Edit" -> "New"
echo - Add: C:\php
echo - Click OK on all dialogs
echo.

echo Step 3: Restart Command Prompt
echo - Close this window
echo - Open new Command Prompt
echo - Test with: php --version
echo.

echo Alternative: Use XAMPP
echo - Download: https://www.apachefriends.org/
echo - Install and start Apache
echo.

pause
