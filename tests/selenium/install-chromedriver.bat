@echo off
echo Installing ChromeDriver for Selenium...

echo.
echo Step 1: Check Chrome version
reg query "HKEY_CURRENT_USER\Software\Google\Chrome\BLBeacon" /v version

echo.
echo Step 2: Download ChromeDriver
echo Please go to: https://chromedriver.chromium.org/
echo Download ChromeDriver for your Chrome version
echo Extract chromedriver.exe from the zip file

echo.
echo Step 3: Install ChromeDriver
echo Copy chromedriver.exe to C:\Windows\System32\
echo Or add the folder containing chromedriver.exe to your PATH

echo.
echo Step 4: Test ChromeDriver
chromedriver --version

echo.
echo Step 5: Run Selenium test
echo npx mocha minimal.test.js --timeout 60000

pause
