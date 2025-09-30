@echo off
REM Quick Server IP Update Script (Windows)
REM Use this to update the server IP for existing deployments

echo 🔧 CalDAV Calendar App - Server IP Update
echo ========================================

REM Get current server IP
for /f "tokens=2 delims==" %%a in ('findstr "SERVER_IP=" .env 2^>nul') do set CURRENT_IP=%%a
if defined CURRENT_IP (
    echo Current server IP: %CURRENT_IP%
)

REM Get new server IP
set /p NEW_IP="Enter new server IP address: "

if "%NEW_IP%"=="" (
    echo ❌ No IP address provided. Exiting.
    exit /b 1
)

REM Update .env file
if exist .env (
    powershell -Command "(Get-Content .env) -replace 'SERVER_IP=.*', 'SERVER_IP=%NEW_IP%' | Set-Content .env"
) else (
    (
        echo SERVER_IP=%NEW_IP%
        echo SERVER_DOMAIN=
        echo CALDAV_SERVER_URL=http://rc.mithi.com:8008
        echo APP_DEBUG=false
        echo APP_ENV=production
    ) > .env
)

echo ✅ Updated .env file with new IP: %NEW_IP%

REM Restart containers with new configuration
echo 🔄 Restarting containers with new configuration...
docker compose down
docker compose up -d

echo ✅ Application restarted with new server IP
echo.
echo Application URLs:
echo   Frontend: http://%NEW_IP%:4200
echo   Backend:  http://%NEW_IP%:8000

pause
