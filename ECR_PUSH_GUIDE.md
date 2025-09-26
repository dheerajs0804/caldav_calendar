# AWS ECR Push Setup Guide

## Prerequisites

### 1. Install AWS CLI
Download and install AWS CLI from: https://aws.amazon.com/cli/

### 2. Configure AWS Credentials
Run the following command and enter your AWS credentials:
```bash
aws configure
```

You'll need:
- AWS Access Key ID
- AWS Secret Access Key
- Default region (e.g., us-east-1)
- Default output format (json)

### 3. Verify AWS Configuration
Test your configuration:
```bash
aws sts get-caller-identity
```

This should return your AWS account ID and user information.

## Using the ECR Push Scripts

### Option 1: Automatic (Recommended)
The script will automatically detect your AWS account ID and region:

**Linux/Mac:**
```bash
./push-to-ecr.sh
```

**Windows:**
```cmd
push-to-ecr.bat
```

### Option 2: Manual Configuration
If you want to specify values manually:

**Linux/Mac:**
```bash
./push-to-ecr.sh --region us-west-2 --account-id 123456789012 --tag v1.0.0
```

**Windows:**
```cmd
push-to-ecr.bat --region us-west-2 --account-id 123456789012 --tag v1.0.0
```

### Option 3: Environment Variables
Set environment variables:

**Linux/Mac:**
```bash
export AWS_REGION=us-west-2
export AWS_ACCOUNT_ID=123456789012
export TAG=v1.0.0
./push-to-ecr.sh
```

**Windows:**
```cmd
set AWS_REGION=us-west-2
set AWS_ACCOUNT_ID=123456789012
set TAG=v1.0.0
push-to-ecr.bat
```

## What the Script Does

1. **Checks Prerequisites**: Verifies AWS CLI and Docker are installed and configured
2. **Creates ECR Repositories**: Creates repositories for:
   - `caldev-frontend` (Angular app)
   - `caldev-backend` (PHP backend)
   - `caldev-roundcube` (Email/calendar integration)
3. **Authenticates Docker**: Logs Docker into ECR
4. **Builds Images**: Builds all Docker images
5. **Tags Images**: Tags images with ECR repository URLs
6. **Pushes Images**: Pushes all images to ECR

## Manual ECR Push (Alternative)

If you prefer to do it manually:

### 1. Get ECR Login Token
```bash
aws ecr get-login-password --region YOUR_REGION | docker login --username AWS --password-stdin YOUR_ACCOUNT_ID.dkr.ecr.YOUR_REGION.amazonaws.com
```

### 2. Create ECR Repositories
```bash
aws ecr create-repository --repository-name caldev-frontend --region YOUR_REGION
aws ecr create-repository --repository-name caldev-backend --region YOUR_REGION
aws ecr create-repository --repository-name caldev-roundcube --region YOUR_REGION
```

### 3. Build and Push Images
```bash
# Frontend
cd frontend-angular
docker build -t caldev-frontend:latest .
docker tag caldev-frontend:latest YOUR_ACCOUNT_ID.dkr.ecr.YOUR_REGION.amazonaws.com/caldev-frontend:latest
docker push YOUR_ACCOUNT_ID.dkr.ecr.YOUR_REGION.amazonaws.com/caldev-frontend:latest

# Backend
cd ../backend
docker build -t caldev-backend:latest .
docker tag caldev-backend:latest YOUR_ACCOUNT_ID.dkr.ecr.YOUR_REGION.amazonaws.com/caldev-backend:latest
docker push YOUR_ACCOUNT_ID.dkr.ecr.YOUR_REGION.amazonaws.com/caldev-backend:latest

# Roundcube
cd ..
docker build -t caldev-roundcube:latest .
docker tag caldev-roundcube:latest YOUR_ACCOUNT_ID.dkr.ecr.YOUR_REGION.amazonaws.com/caldev-roundcube:latest
docker push YOUR_ACCOUNT_ID.dkr.ecr.YOUR_REGION.amazonaws.com/caldev-roundcube:latest
```

## Troubleshooting

### AWS CLI Not Found
- Install AWS CLI from https://aws.amazon.com/cli/
- Make sure it's added to your PATH

### Authentication Failed
- Run `aws configure` to set up credentials
- Verify your AWS credentials have ECR permissions

### Docker Not Running
- Start Docker Desktop
- Verify Docker is running with `docker info`

### Permission Denied
- Ensure your AWS user has ECR permissions
- Check repository policies in AWS Console

## ECR Repository URLs

After successful push, your images will be available at:
- `YOUR_ACCOUNT_ID.dkr.ecr.YOUR_REGION.amazonaws.com/caldev-frontend:latest`
- `YOUR_ACCOUNT_ID.dkr.ecr.YOUR_REGION.amazonaws.com/caldev-backend:latest`
- `YOUR_ACCOUNT_ID.dkr.ecr.YOUR_REGION.amazonaws.com/caldev-roundcube:latest`

