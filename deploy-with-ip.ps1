# Dynamic CalDAV Calendar App Deployment Script (PowerShell)
# This script helps deploy the application with dynamic IP configuration

param(
    [string]$ServerIP = "",
    [switch]$Help
)

# Colors
$Red = "Red"
$Green = "Green"
$Yellow = "Yellow"
$Blue = "Blue"

function Show-Usage {
    Write-Host "Usage: .\deploy-with-ip.ps1 [options]" -ForegroundColor $Blue
    Write-Host ""
    Write-Host "Options:" -ForegroundColor $Blue
    Write-Host "  -ServerIP IP     Server IP address (if not provided, will prompt)" -ForegroundColor $Blue
    Write-Host "  -Help            Show this help message" -ForegroundColor $Blue
    Write-Host ""
}

function Test-Prerequisites {
    Write-Host "ℹ️  Checking prerequisites..." -ForegroundColor $Yellow
    
    # Check Docker
    try {
        $null = Get-Command docker -ErrorAction Stop
    }
    catch {
        Write-Host "❌ Docker not found. Install Docker first." -ForegroundColor $Red
        exit 1
    }
    
    # Check AWS CLI
    try {
        $null = Get-Command aws -ErrorAction Stop
    }
    catch {
        Write-Host "❌ AWS CLI not found. Install AWS CLI first." -ForegroundColor $Red
        exit 1
    }
    
    Write-Host "✅ Prerequisites check passed" -ForegroundColor $Green
}

function Get-ServerIP {
    param($ProvidedIP)
    
    if ([string]::IsNullOrEmpty($ProvidedIP)) {
        Write-Host "ℹ️  Please enter the server IP address:" -ForegroundColor $Yellow
        $ProvidedIP = Read-Host "Server IP"
        
        if ([string]::IsNullOrEmpty($ProvidedIP)) {
            Write-Host "❌ No IP address provided. Exiting." -ForegroundColor $Red
            exit 1
        }
    }
    
    Write-Host "✅ Using server IP: $ProvidedIP" -ForegroundColor $Green
    return $ProvidedIP
}

function Invoke-ECRAuth {
    $AWS_REGION = "us-west-2"
    $AWS_ACCOUNT_ID = "248189916187"
    $ECR_REGISTRY = "$AWS_ACCOUNT_ID.dkr.ecr.$AWS_REGION.amazonaws.com"
    
    Write-Host "ℹ️  Authenticating with AWS ECR..." -ForegroundColor $Yellow
    
    try {
        $loginCommand = "aws ecr get-login-password --region $AWS_REGION | docker login --username AWS --password-stdin $ECR_REGISTRY"
        Invoke-Expression $loginCommand
        Write-Host "✅ ECR authentication successful" -ForegroundColor $Green
        return $ECR_REGISTRY
    }
    catch {
        Write-Host "❌ ECR authentication failed" -ForegroundColor $Red
        exit 1
    }
}

function Invoke-ImagePull {
    param($ECRRegistry)
    
    Write-Host "ℹ️  Pulling latest Docker images..." -ForegroundColor $Yellow
    
    try {
        docker pull "$ECRRegistry/caldev-frontend:latest"
        docker pull "$ECRRegistry/caldev-backend:latest"
        Write-Host "✅ Images pulled successfully" -ForegroundColor $Green
    }
    catch {
        Write-Host "❌ Failed to pull images" -ForegroundColor $Red
        exit 1
    }
}

function Start-Application {
    param($ServerIP)
    
    Write-Host "ℹ️  Stopping existing containers..." -ForegroundColor $Yellow
    docker compose down
    
    Write-Host "ℹ️  Starting application with server IP: $ServerIP" -ForegroundColor $Yellow
    
    # Set environment variables
    $env:SERVER_IP = $ServerIP
    $env:SERVER_DOMAIN = ""
    $env:CALDAV_SERVER_URL = "http://rc.mithi.com:8008"
    $env:APP_DEBUG = "false"
    $env:APP_ENV = "production"
    
    # Start containers
    try {
        docker compose up -d
        Write-Host "✅ Application started successfully" -ForegroundColor $Green
    }
    catch {
        Write-Host "❌ Failed to start application" -ForegroundColor $Red
        exit 1
    }
}

function Show-Status {
    param($ServerIP)
    
    Write-Host "ℹ️  Waiting for services to be ready..." -ForegroundColor $Yellow
    Start-Sleep -Seconds 10
    
    Write-Host "ℹ️  Checking container status..." -ForegroundColor $Yellow
    docker compose ps
    
    Write-Host ""
    Write-Host "🎉 Deployment completed successfully!" -ForegroundColor $Green
    Write-Host "============================================" -ForegroundColor $Blue
    Write-Host "Application URLs:" -ForegroundColor $Blue
    Write-Host "  Frontend: http://$ServerIP:4200" -ForegroundColor $Green
    Write-Host "  Backend:  http://$ServerIP:8000" -ForegroundColor $Green
    Write-Host ""
    Write-Host "Local URLs:" -ForegroundColor $Blue
    Write-Host "  Frontend: http://localhost:4200" -ForegroundColor $Green
    Write-Host "  Backend:  http://localhost:8000" -ForegroundColor $Green
    Write-Host ""
    Write-Host "Management Commands:" -ForegroundColor $Blue
    Write-Host "  View logs:     docker compose logs -f" -ForegroundColor $Yellow
    Write-Host "  Stop app:      docker compose down" -ForegroundColor $Yellow
    Write-Host "  Restart app:   docker compose restart" -ForegroundColor $Yellow
    Write-Host "  Check status:  docker compose ps" -ForegroundColor $Yellow
    Write-Host ""
}

function Save-Configuration {
    param($ServerIP)
    
    Write-Host "ℹ️  Saving deployment configuration..." -ForegroundColor $Yellow
    
    $envContent = @"
# CalDAV Calendar App - Deployment Configuration
# Generated on: $(Get-Date)
SERVER_IP=$ServerIP
SERVER_DOMAIN=
CALDAV_SERVER_URL=http://rc.mithi.com:8008
APP_DEBUG=false
APP_ENV=production
"@
    
    $envContent | Out-File -FilePath ".env" -Encoding UTF8
    Write-Host "✅ Configuration saved to .env file" -ForegroundColor $Green
    Write-Host "ℹ️  You can modify .env and run 'docker compose up -d' to apply changes" -ForegroundColor $Yellow
}

# Main execution
if ($Help) {
    Show-Usage
    exit 0
}

Write-Host "🚀 CalDAV Calendar App - Dynamic Deployment" -ForegroundColor $Blue
Write-Host "==========================================" -ForegroundColor $Blue

$ServerIP = Get-ServerIP -ProvidedIP $ServerIP
Test-Prerequisites
$ECRRegistry = Invoke-ECRAuth
Invoke-ImagePull -ECRRegistry $ECRRegistry
Start-Application -ServerIP $ServerIP
Show-Status -ServerIP $ServerIP
Save-Configuration -ServerIP $ServerIP
