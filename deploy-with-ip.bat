@echo off
REM Dynamic CalDAV Calendar App Deployment Script (Windows)
REM This script helps deploy the application with dynamic IP configuration

setlocal enabledelayedexpansion

echo 🚀 CalDAV Calendar App - Dynamic Deployment
echo ==========================================

REM Step 1: Get server IP
echo ℹ️  Please enter the server IP address:
set /p SERVER_IP="Server IP: "

if "%SERVER_IP%"=="" (
    echo ❌ No IP address provided. Exiting.
    exit /b 1
)

echo ✅ Using server IP: %SERVER_IP%

REM Step 2: Check prerequisites
echo ℹ️  Checking prerequisites...

where docker >nul 2>&1
if errorlevel 1 (
    echo ❌ Docker not found. Install Docker first.
    exit /b 1
)

where aws >nul 2>&1
if errorlevel 1 (
    echo ❌ AWS CLI not found. Install AWS CLI first.
    exit /b 1
)

echo ✅ Prerequisites check passed

REM Step 3: Authenticate with ECR
echo ℹ️  Authenticating with AWS ECR...
set AWS_REGION=us-west-2
set AWS_ACCOUNT_ID=248189916187
set ECR_REGISTRY=%AWS_ACCOUNT_ID%.dkr.ecr.%AWS_REGION%.amazonaws.com

aws ecr get-login-password --region %AWS_REGION% | docker login --username AWS --password-stdin %ECR_REGISTRY%
if errorlevel 1 (
    echo ❌ ECR authentication failed
    exit /b 1
)

echo ✅ ECR authentication successful

REM Step 4: Pull latest images
echo ℹ️  Pulling latest Docker images...
docker pull %ECR_REGISTRY%/caldev-frontend:latest
docker pull %ECR_REGISTRY%/caldev-backend:latest

if errorlevel 1 (
    echo ❌ Failed to pull images
    exit /b 1
)

echo ✅ Images pulled successfully

REM Step 5: Stop existing containers
echo ℹ️  Stopping existing containers...
docker compose down

REM Step 6: Set environment variables and start containers
echo ℹ️  Starting application with server IP: %SERVER_IP%

REM Set environment variables
set SERVER_IP=%SERVER_IP%
set SERVER_DOMAIN=
set CALDAV_SERVER_URL=http://rc.mithi.com:8008
set APP_DEBUG=false
set APP_ENV=production

REM Start containers
docker compose up -d

if errorlevel 1 (
    echo ❌ Failed to start application
    exit /b 1
)

echo ✅ Application started successfully

REM Step 7: Wait for services to be ready
echo ℹ️  Waiting for services to be ready...
timeout /t 10 /nobreak >nul

REM Step 8: Check container status
echo ℹ️  Checking container status...
docker compose ps

REM Step 9: Display access information
echo.
echo ✅ 🎉 Deployment completed successfully!
echo ==========================================
echo Application URLs:
echo   Frontend: http://%SERVER_IP%:4200
echo   Backend:  http://%SERVER_IP%:8000
echo.
echo Local URLs:
echo   Frontend: http://localhost:4200
echo   Backend:  http://localhost:8000
echo.
echo Management Commands:
echo   View logs:     docker compose logs -f
echo   Stop app:      docker compose down
echo   Restart app:   docker compose restart
echo   Check status:  docker compose ps
echo.

REM Step 10: Save configuration
echo ℹ️  Saving deployment configuration...
(
echo # CalDAV Calendar App - Deployment Configuration
echo # Generated on: %date% %time%
echo SERVER_IP=%SERVER_IP%
echo SERVER_DOMAIN=
echo CALDAV_SERVER_URL=http://rc.mithi.com:8008
echo APP_DEBUG=false
echo APP_ENV=production
) > .env

echo ✅ Configuration saved to .env file
echo ℹ️  You can modify .env and run 'docker compose up -d' to apply changes

pause
