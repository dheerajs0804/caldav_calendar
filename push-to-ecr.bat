@echo off
REM CalDAV Calendar App - AWS ECR Push Script (Windows)
REM This script builds and pushes all Docker images to AWS ECR

setlocal enabledelayedexpansion

REM Configuration - Update these values for your AWS setup
if "%AWS_REGION%"=="" set AWS_REGION=us-east-1
if "%AWS_ACCOUNT_ID%"=="" set AWS_ACCOUNT_ID=
if "%TAG%"=="" set TAG=latest

REM Image names and tags
set FRONTEND_IMAGE=caldev-frontend
set BACKEND_IMAGE=caldev-backend
set ROUNDCUBE_IMAGE=caldev-roundcube

echo 🚀 CalDAV Calendar App - AWS ECR Push
echo ==============================================

REM Parse command line arguments
set PUSH_FRONTEND=true
set PUSH_BACKEND=true
set PUSH_ROUNDCUBE=true

:parse_args
if "%1"=="--region" (
    set AWS_REGION=%2
    shift
    shift
    goto parse_args
)
if "%1"=="--account-id" (
    set AWS_ACCOUNT_ID=%2
    shift
    shift
    goto parse_args
)
if "%1"=="--tag" (
    set TAG=%2
    shift
    shift
    goto parse_args
)
if "%1"=="--frontend-only" (
    set PUSH_FRONTEND=true
    set PUSH_BACKEND=false
    set PUSH_ROUNDCUBE=false
    shift
    goto parse_args
)
if "%1"=="--backend-only" (
    set PUSH_FRONTEND=false
    set PUSH_BACKEND=true
    set PUSH_ROUNDCUBE=false
    shift
    goto parse_args
)
if "%1"=="--roundcube-only" (
    set PUSH_FRONTEND=false
    set PUSH_BACKEND=false
    set PUSH_ROUNDCUBE=true
    shift
    goto parse_args
)
if "%1"=="--help" goto show_usage
if "%1"=="-h" goto show_usage
if "%1"=="" goto main
if "%1" neq "" (
    echo ❌ Unknown option: %1
    goto show_usage
)

:show_usage
echo Usage: %0 [options]
echo.
echo Options:
echo   --region REGION     AWS region (default: us-east-1)
echo   --account-id ID     AWS account ID (required)
echo   --tag TAG          Image tag (default: latest)
echo   --frontend-only    Push only frontend image
echo   --backend-only     Push only backend image
echo   --roundcube-only   Push only roundcube image
echo   --help             Show this help message
echo.
echo Environment Variables:
echo   AWS_REGION         AWS region
echo   AWS_ACCOUNT_ID     AWS account ID
echo   TAG                Image tag
echo.
goto end

:check_prerequisites
echo 🔍 Checking prerequisites...

REM Check if AWS CLI is installed
aws --version >nul 2>&1
if errorlevel 1 (
    echo ❌ AWS CLI is not installed. Please install it first.
    exit /b 1
)

REM Check if Docker is running
docker info >nul 2>&1
if errorlevel 1 (
    echo ❌ Docker is not running. Please start Docker first.
    exit /b 1
)

REM Check AWS credentials
aws sts get-caller-identity >nul 2>&1
if errorlevel 1 (
    echo ❌ AWS credentials not configured. Please run 'aws configure' first.
    exit /b 1
)

REM Get AWS account ID if not provided
if "%AWS_ACCOUNT_ID%"=="" (
    echo 📋 Getting AWS account ID...
    for /f "tokens=*" %%i in ('aws sts get-caller-identity --query Account --output text') do set AWS_ACCOUNT_ID=%%i
    if "%AWS_ACCOUNT_ID%"=="" (
        echo ❌ Could not get AWS account ID
        exit /b 1
    )
)

set ECR_REGISTRY=%AWS_ACCOUNT_ID%.dkr.ecr.%AWS_REGION%.amazonaws.com
echo ✅ Prerequisites check passed
echo    AWS Account ID: %AWS_ACCOUNT_ID%
echo    AWS Region: %AWS_REGION%
echo    ECR Registry: %ECR_REGISTRY%
goto :eof

:create_ecr_repositories
echo 📦 Creating ECR repositories...

REM Create repositories if they don't exist
for %%r in (%FRONTEND_IMAGE% %BACKEND_IMAGE% %ROUNDCUBE_IMAGE%) do (
    aws ecr describe-repositories --repository-names %%r --region %AWS_REGION% >nul 2>&1
    if errorlevel 1 (
        echo    Creating repository: %%r
        aws ecr create-repository --repository-name %%r --region %AWS_REGION%
    ) else (
        echo    Repository %%r already exists
    )
)
goto :eof

:authenticate_docker
echo 🔐 Authenticating Docker with ECR...
aws ecr get-login-password --region %AWS_REGION% | docker login --username AWS --password-stdin %ECR_REGISTRY%
echo ✅ Docker authenticated with ECR
goto :eof

:push_frontend
echo 🏗️  Building and pushing frontend image...

echo    Building Angular frontend...
cd frontend-angular
docker build -t %FRONTEND_IMAGE%:%TAG% -f Dockerfile .

echo    Tagging for ECR...
docker tag %FRONTEND_IMAGE%:%TAG% %ECR_REGISTRY%/%FRONTEND_IMAGE%:%TAG%

echo    Pushing to ECR...
docker push %ECR_REGISTRY%/%FRONTEND_IMAGE%:%TAG%

cd ..
echo ✅ Frontend image pushed successfully
goto :eof

:push_backend
echo 🏗️  Building and pushing backend image...

echo    Building PHP backend...
cd backend
docker build -t %BACKEND_IMAGE%:%TAG% -f Dockerfile .

echo    Tagging for ECR...
docker tag %BACKEND_IMAGE%:%TAG% %ECR_REGISTRY%/%BACKEND_IMAGE%:%TAG%

echo    Pushing to ECR...
docker push %ECR_REGISTRY%/%BACKEND_IMAGE%:%TAG%

cd ..
echo ✅ Backend image pushed successfully
goto :eof

:push_roundcube
echo 🏗️  Building and pushing roundcube image...

echo    Building Roundcube...
docker build -t %ROUNDCUBE_IMAGE%:%TAG% -f Dockerfile .

echo    Tagging for ECR...
docker tag %ROUNDCUBE_IMAGE%:%TAG% %ECR_REGISTRY%/%ROUNDCUBE_IMAGE%:%TAG%

echo    Pushing to ECR...
docker push %ECR_REGISTRY%/%ROUNDCUBE_IMAGE%:%TAG%

echo ✅ Roundcube image pushed successfully
goto :eof

:show_summary
echo.
echo 🎉 Push completed successfully!
echo ==============================================
echo ECR Registry: %ECR_REGISTRY%
echo Images pushed:
echo   • %FRONTEND_IMAGE%:%TAG%
echo   • %BACKEND_IMAGE%:%TAG%
echo   • %ROUNDCUBE_IMAGE%:%TAG%
echo.
echo To pull these images:
echo   docker pull %ECR_REGISTRY%/%FRONTEND_IMAGE%:%TAG%
echo   docker pull %ECR_REGISTRY%/%BACKEND_IMAGE%:%TAG%
echo   docker pull %ECR_REGISTRY%/%ROUNDCUBE_IMAGE%:%TAG%
echo.
goto :eof

:main
call :check_prerequisites
if errorlevel 1 exit /b 1

call :create_ecr_repositories
if errorlevel 1 exit /b 1

call :authenticate_docker
if errorlevel 1 exit /b 1

if "%PUSH_FRONTEND%"=="true" (
    call :push_frontend
    if errorlevel 1 exit /b 1
)

if "%PUSH_BACKEND%"=="true" (
    call :push_backend
    if errorlevel 1 exit /b 1
)

if "%PUSH_ROUNDCUBE%"=="true" (
    call :push_roundcube
    if errorlevel 1 exit /b 1
)

call :show_summary

:end

