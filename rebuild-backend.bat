@echo off
echo ============================================
echo Rebuilding Backend Image with Apache
echo ============================================

echo.
echo Step 1: Building backend image...
docker build -t caldev-backend:latest -f backend/Dockerfile backend/

if %ERRORLEVEL% neq 0 (
    echo ❌ Backend build failed!
    pause
    exit /b 1
)

echo.
echo Step 2: Tagging backend image for ECR...
docker tag caldev-backend:latest 248189916187.dkr.ecr.us-west-2.amazonaws.com/caldev-backend:latest

echo.
echo Step 3: Authenticating with ECR...
aws ecr get-login-password --region us-west-2 | docker login --username AWS --password-stdin 248189916187.dkr.ecr.us-west-2.amazonaws.com

if %ERRORLEVEL% neq 0 (
    echo ❌ ECR authentication failed!
    pause
    exit /b 1
)

echo.
echo Step 4: Pushing backend image to ECR...
docker push 248189916187.dkr.ecr.us-west-2.amazonaws.com/caldev-backend:latest

if %ERRORLEVEL% neq 0 (
    echo ❌ Backend push failed!
    pause
    exit /b 1
)

echo.
echo ✅ Backend image rebuilt and pushed successfully!
echo.
echo Next steps:
echo 1. Copy docker-compose.yml to SSH server
echo 2. Run: docker-compose up angular-dev php-backend
echo.
pause
