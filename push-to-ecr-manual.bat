@echo off
REM CalDAV Calendar App - Manual ECR Push Helper
REM This script provides step-by-step instructions for pushing to ECR

echo 🚀 CalDAV Calendar App - Manual ECR Push Helper
echo ==============================================
echo.

REM Check if AWS CLI is available
aws --version >nul 2>&1
if errorlevel 1 (
    echo ❌ AWS CLI is not installed.
    echo.
    echo Please install AWS CLI from: https://aws.amazon.com/cli/
    echo Then run: aws configure
    echo.
    pause
    exit /b 1
)

REM Check if Docker is running
docker info >nul 2>&1
if errorlevel 1 (
    echo ❌ Docker is not running.
    echo.
    echo Please start Docker Desktop and try again.
    echo.
    pause
    exit /b 1
)

echo ✅ Prerequisites check passed
echo.

REM Get AWS account information
echo 📋 Getting AWS account information...
for /f "tokens=*" %%i in ('aws sts get-caller-identity --query Account --output text') do set AWS_ACCOUNT_ID=%%i
for /f "tokens=*" %%i in ('aws configure get region') do set AWS_REGION=%%i

if "%AWS_REGION%"=="" set AWS_REGION=us-east-1

echo AWS Account ID: %AWS_ACCOUNT_ID%
echo AWS Region: %AWS_REGION%
echo.

set ECR_REGISTRY=%AWS_ACCOUNT_ID%.dkr.ecr.%AWS_REGION%.amazonaws.com
echo ECR Registry: %ECR_REGISTRY%
echo.

REM Authenticate Docker with ECR
echo 🔐 Authenticating Docker with ECR...
aws ecr get-login-password --region %AWS_REGION% | docker login --username AWS --password-stdin %ECR_REGISTRY%
if errorlevel 1 (
    echo ❌ Failed to authenticate with ECR
    echo Please check your AWS credentials and try again.
    pause
    exit /b 1
)
echo ✅ Docker authenticated with ECR
echo.

REM Create ECR repositories
echo 📦 Creating ECR repositories...
aws ecr create-repository --repository-name caldev-frontend --region %AWS_REGION% 2>nul
if errorlevel 1 (
    echo Repository caldev-frontend already exists
) else (
    echo ✅ Created repository caldev-frontend
)

aws ecr create-repository --repository-name caldev-backend --region %AWS_REGION% 2>nul
if errorlevel 1 (
    echo Repository caldev-backend already exists
) else (
    echo ✅ Created repository caldev-backend
)

aws ecr create-repository --repository-name caldev-roundcube --region %AWS_REGION% 2>nul
if errorlevel 1 (
    echo Repository caldev-roundcube already exists
) else (
    echo ✅ Created repository caldev-roundcube
)
echo.

REM Build and push frontend
echo 🏗️  Building and pushing frontend image...
cd frontend-angular
docker build -t caldev-frontend:latest -f Dockerfile .
docker tag caldev-frontend:latest %ECR_REGISTRY%/caldev-frontend:latest
docker push %ECR_REGISTRY%/caldev-frontend:latest
cd ..
echo ✅ Frontend image pushed successfully
echo.

REM Build and push backend
echo 🏗️  Building and pushing backend image...
cd backend
docker build -t caldev-backend:latest -f Dockerfile .
docker tag caldev-backend:latest %ECR_REGISTRY%/caldev-backend:latest
docker push %ECR_REGISTRY%/caldev-backend:latest
cd ..
echo ✅ Backend image pushed successfully
echo.

REM Build and push roundcube
echo 🏗️  Building and pushing roundcube image...
docker build -t caldev-roundcube:latest -f Dockerfile .
docker tag caldev-roundcube:latest %ECR_REGISTRY%/caldev-roundcube:latest
docker push %ECR_REGISTRY%/caldev-roundcube:latest
echo ✅ Roundcube image pushed successfully
echo.

echo 🎉 All images pushed successfully!
echo ==============================================
echo ECR Registry: %ECR_REGISTRY%
echo.
echo Images available at:
echo   • %ECR_REGISTRY%/caldev-frontend:latest
echo   • %ECR_REGISTRY%/caldev-backend:latest
echo   • %ECR_REGISTRY%/caldev-roundcube:latest
echo.
echo To pull these images:
echo   docker pull %ECR_REGISTRY%/caldev-frontend:latest
echo   docker pull %ECR_REGISTRY%/caldev-backend:latest
echo   docker pull %ECR_REGISTRY%/caldev-roundcube:latest
echo.
pause

