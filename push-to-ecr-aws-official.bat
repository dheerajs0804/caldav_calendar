@echo off
REM CalDAV Calendar App - AWS ECR Push (Following Official AWS Documentation)
REM This script follows the exact steps from AWS ECR documentation

echo 🚀 CalDAV Calendar App - AWS ECR Push (Official AWS Method)
echo ============================================================
echo.

REM Configuration
set AWS_REGION=us-west-2
set AWS_ACCOUNT_ID=248189916187
set ECR_REGISTRY=%AWS_ACCOUNT_ID%.dkr.ecr.%AWS_REGION%.amazonaws.com

echo AWS Account ID: %AWS_ACCOUNT_ID%
echo AWS Region: %AWS_REGION%
echo ECR Registry: %ECR_REGISTRY%
echo.

REM Step 1: Authenticate Docker with ECR (Official AWS Method)
echo 🔐 Step 1: Authenticating Docker with ECR...
echo Running: aws ecr get-login-password --region %AWS_REGION% ^| docker login --username AWS --password-stdin %ECR_REGISTRY%
aws ecr get-login-password --region %AWS_REGION% | docker login --username AWS --password-stdin %ECR_REGISTRY%
if errorlevel 1 (
    echo ❌ Authentication failed. Please check your AWS credentials.
    pause
    exit /b 1
)
echo ✅ Docker authenticated with ECR
echo.

REM Step 2: Build and push Frontend Image
echo 🏗️  Step 2: Building and pushing Frontend image...
echo Repository: mithi/admin-panel/caldev-frontend
echo.

echo Building Docker image...
cd frontend-angular
docker build -t mithi/admin-panel/caldev-frontend .
if errorlevel 1 (
    echo ❌ Frontend build failed
    cd ..
    pause
    exit /b 1
)

echo Tagging image for ECR...
docker tag mithi/admin-panel/caldev-frontend:latest %ECR_REGISTRY%/mithi/admin-panel/caldev-frontend:latest

echo Pushing image to ECR...
docker push %ECR_REGISTRY%/mithi/admin-panel/caldev-frontend:latest
if errorlevel 1 (
    echo ❌ Frontend push failed
    cd ..
    pause
    exit /b 1
)
cd ..
echo ✅ Frontend image pushed successfully
echo.

REM Step 3: Build and push Backend Image
echo 🏗️  Step 3: Building and pushing Backend image...
echo Repository: mithi/admin-panel/caldev-backend
echo.

echo Building Docker image...
cd backend
docker build -t mithi/admin-panel/caldev-backend .
if errorlevel 1 (
    echo ❌ Backend build failed
    cd ..
    pause
    exit /b 1
)

echo Tagging image for ECR...
docker tag mithi/admin-panel/caldev-backend:latest %ECR_REGISTRY%/mithi/admin-panel/caldev-backend:latest

echo Pushing image to ECR...
docker push %ECR_REGISTRY%/mithi/admin-panel/caldev-backend:latest
if errorlevel 1 (
    echo ❌ Backend push failed
    cd ..
    pause
    exit /b 1
)
cd ..
echo ✅ Backend image pushed successfully
echo.

REM Step 4: Skipping Roundcube Image (not needed)
echo ⏭️  Step 4: Skipping Roundcube image (not needed for this deployment)
echo.

echo 🎉 All images pushed successfully!
echo ============================================================
echo ECR Registry: %ECR_REGISTRY%
echo.
echo Images pushed to repositories:
echo   • mithi/admin-panel/caldev-frontend:latest
echo   • mithi/admin-panel/caldev-backend:latest
echo.
echo Full ECR URLs:
echo   • %ECR_REGISTRY%/mithi/admin-panel/caldev-frontend:latest
echo   • %ECR_REGISTRY%/mithi/admin-panel/caldev-backend:latest
echo.
echo To pull these images:
echo   docker pull %ECR_REGISTRY%/mithi/admin-panel/caldev-frontend:latest
echo   docker pull %ECR_REGISTRY%/mithi/admin-panel/caldev-backend:latest
echo.
pause
