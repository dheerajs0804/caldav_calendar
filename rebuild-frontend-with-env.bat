@echo off
echo ============================================
echo Rebuilding Frontend with Environment Support
echo ============================================

set AWS_ACCOUNT_ID=248189916187
set AWS_REGION=us-west-2
set ECR_REGISTRY=%AWS_ACCOUNT_ID%.dkr.ecr.%AWS_REGION%.amazonaws.com
set FRONTEND_REPO_NAME=caldev-frontend
set FRONTEND_IMAGE_NAME=%ECR_REGISTRY%/%FRONTEND_REPO_NAME%:latest

echo.
echo AWS Account ID: %AWS_ACCOUNT_ID%
echo AWS Region: %AWS_REGION%
echo ECR Registry: %ECR_REGISTRY%
echo Frontend Repository: %FRONTEND_REPO_NAME%
echo Frontend Image: %FRONTEND_IMAGE_NAME%
echo.

echo Step 1: Authenticating Docker with ECR...
aws ecr get-login-password --region %AWS_REGION% | docker login --username AWS --password-stdin %ECR_REGISTRY%
if %errorlevel% neq 0 (
    echo ❌ Docker authentication failed. Exiting.
    goto :eof
)
echo ✅ Docker authenticated with ECR

echo.
echo Step 2: Building Frontend image with docker configuration...
cd frontend-angular
docker build -t %FRONTEND_REPO_NAME%:latest -f Dockerfile .
if %errorlevel% neq 0 (
    echo ❌ Frontend image build failed. Exiting.
    cd ..
    goto :eof
)
cd ..
echo ✅ Frontend image built successfully with environment support

echo.
echo Step 3: Tagging Frontend image...
docker tag %FRONTEND_REPO_NAME%:latest %FRONTEND_IMAGE_NAME%
if %errorlevel% neq 0 (
    echo ❌ Frontend image tagging failed. Exiting.
    goto :eof
)
echo ✅ Frontend image tagged successfully

echo.
echo Step 4: Pushing Frontend image to ECR...
docker push %FRONTEND_IMAGE_NAME%
if %errorlevel% neq 0 (
    echo ❌ Frontend image push failed. Exiting.
    goto :eof
)
echo ✅ Frontend image pushed successfully

echo.
echo 🎉 Frontend image updated in ECR with environment support!
echo ✅ Local development: Uses http://localhost:8000
echo ✅ Docker containers: Uses http://php-backend:80
echo Full ECR URL: %FRONTEND_IMAGE_NAME%
echo.
pause
