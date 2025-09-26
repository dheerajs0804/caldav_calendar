# CalDAV Calendar App - AWS ECR Push Script (PowerShell)
# This script builds and pushes all Docker images to AWS ECR

param(
    [string]$Region = "us-east-1",
    [string]$AccountId = "",
    [string]$Tag = "latest",
    [switch]$FrontendOnly,
    [switch]$BackendOnly,
    [switch]$RoundcubeOnly,
    [switch]$Help
)

# Colors for output
$Red = "Red"
$Green = "Green"
$Yellow = "Yellow"
$Blue = "Blue"

function Show-Usage {
    Write-Host "Usage: .\push-to-ecr.ps1 [options]" -ForegroundColor $Blue
    Write-Host ""
    Write-Host "Options:" -ForegroundColor $Blue
    Write-Host "  -Region REGION     AWS region (default: us-east-1)" -ForegroundColor $Blue
    Write-Host "  -AccountId ID      AWS account ID (required)" -ForegroundColor $Blue
    Write-Host "  -Tag TAG           Image tag (default: latest)" -ForegroundColor $Blue
    Write-Host "  -FrontendOnly      Push only frontend image" -ForegroundColor $Blue
    Write-Host "  -BackendOnly       Push only backend image" -ForegroundColor $Blue
    Write-Host "  -RoundcubeOnly     Push only roundcube image" -ForegroundColor $Blue
    Write-Host "  -Help              Show this help message" -ForegroundColor $Blue
    Write-Host ""
}

function Test-Prerequisites {
    Write-Host "🔍 Checking prerequisites..." -ForegroundColor $Yellow
    
    # Check if AWS CLI is installed
    try {
        $null = Get-Command aws -ErrorAction Stop
    }
    catch {
        Write-Host "❌ AWS CLI is not installed. Please install it first." -ForegroundColor $Red
        Write-Host "Download from: https://aws.amazon.com/cli/" -ForegroundColor $Yellow
        exit 1
    }
    
    # Check if Docker is running
    try {
        docker info | Out-Null
    }
    catch {
        Write-Host "❌ Docker is not running. Please start Docker first." -ForegroundColor $Red
        exit 1
    }
    
    # Check AWS credentials
    try {
        $null = aws sts get-caller-identity
    }
    catch {
        Write-Host "❌ AWS credentials not configured. Please run 'aws configure' first." -ForegroundColor $Red
        exit 1
    }
    
    # Get AWS account ID if not provided
    if ([string]::IsNullOrEmpty($AccountId)) {
        Write-Host "📋 Getting AWS account ID..." -ForegroundColor $Yellow
        try {
            $AccountId = aws sts get-caller-identity --query Account --output text
            if ([string]::IsNullOrEmpty($AccountId)) {
                Write-Host "❌ Could not get AWS account ID" -ForegroundColor $Red
                exit 1
            }
        }
        catch {
            Write-Host "❌ Could not get AWS account ID" -ForegroundColor $Red
            exit 1
        }
    }
    
    $ECRRegistry = "$AccountId.dkr.ecr.$Region.amazonaws.com"
    Write-Host "✅ Prerequisites check passed" -ForegroundColor $Green
    Write-Host "   AWS Account ID: $AccountId" -ForegroundColor $Blue
    Write-Host "   AWS Region: $Region" -ForegroundColor $Blue
    Write-Host "   ECR Registry: $ECRRegistry" -ForegroundColor $Blue
    
    return $ECRRegistry
}

function New-ECRRepositories {
    param($Region)
    
    Write-Host "📦 Creating ECR repositories..." -ForegroundColor $Yellow
    
    $repositories = @("caldev-frontend", "caldev-backend", "caldev-roundcube")
    
    foreach ($repo in $repositories) {
        try {
            aws ecr describe-repositories --repository-names $repo --region $Region | Out-Null
            Write-Host "   Repository $repo already exists" -ForegroundColor $Green
        }
        catch {
            Write-Host "   Creating repository: $repo" -ForegroundColor $Yellow
            aws ecr create-repository --repository-name $repo --region $Region
            Write-Host "   ✅ Created repository $repo" -ForegroundColor $Green
        }
    }
}

function Invoke-DockerECRAuth {
    param($Region, $ECRRegistry)
    
    Write-Host "🔐 Authenticating Docker with ECR..." -ForegroundColor $Yellow
    $loginCommand = "aws ecr get-login-password --region $Region | docker login --username AWS --password-stdin $ECRRegistry"
    Invoke-Expression $loginCommand
    Write-Host "✅ Docker authenticated with ECR" -ForegroundColor $Green
}

function Push-FrontendImage {
    param($ECRRegistry, $Tag)
    
    Write-Host "🏗️  Building and pushing frontend image..." -ForegroundColor $Yellow
    
    Set-Location "frontend-angular"
    Write-Host "   Building Angular frontend..." -ForegroundColor $Blue
    docker build -t "caldev-frontend:$Tag" -f Dockerfile .
    
    Write-Host "   Tagging for ECR..." -ForegroundColor $Blue
    docker tag "caldev-frontend:$Tag" "$ECRRegistry/caldev-frontend:$Tag"
    
    Write-Host "   Pushing to ECR..." -ForegroundColor $Blue
    docker push "$ECRRegistry/caldev-frontend:$Tag"
    
    Set-Location ".."
    Write-Host "✅ Frontend image pushed successfully" -ForegroundColor $Green
}

function Push-BackendImage {
    param($ECRRegistry, $Tag)
    
    Write-Host "🏗️  Building and pushing backend image..." -ForegroundColor $Yellow
    
    Set-Location "backend"
    Write-Host "   Building PHP backend..." -ForegroundColor $Blue
    docker build -t "caldev-backend:$Tag" -f Dockerfile .
    
    Write-Host "   Tagging for ECR..." -ForegroundColor $Blue
    docker tag "caldev-backend:$Tag" "$ECRRegistry/caldev-backend:$Tag"
    
    Write-Host "   Pushing to ECR..." -ForegroundColor $Blue
    docker push "$ECRRegistry/caldev-backend:$Tag"
    
    Set-Location ".."
    Write-Host "✅ Backend image pushed successfully" -ForegroundColor $Green
}

function Push-RoundcubeImage {
    param($ECRRegistry, $Tag)
    
    Write-Host "🏗️  Building and pushing roundcube image..." -ForegroundColor $Yellow
    
    Write-Host "   Building Roundcube..." -ForegroundColor $Blue
    docker build -t "caldev-roundcube:$Tag" -f Dockerfile .
    
    Write-Host "   Tagging for ECR..." -ForegroundColor $Blue
    docker tag "caldev-roundcube:$Tag" "$ECRRegistry/caldev-roundcube:$Tag"
    
    Write-Host "   Pushing to ECR..." -ForegroundColor $Blue
    docker push "$ECRRegistry/caldev-roundcube:$Tag"
    
    Write-Host "✅ Roundcube image pushed successfully" -ForegroundColor $Green
}

function Show-Summary {
    param($ECRRegistry, $Tag)
    
    Write-Host ""
    Write-Host "🎉 Push completed successfully!" -ForegroundColor $Green
    Write-Host "==============================================" -ForegroundColor $Blue
    Write-Host "ECR Registry: $ECRRegistry" -ForegroundColor $Blue
    Write-Host "Images pushed:" -ForegroundColor $Blue
    Write-Host "  • caldev-frontend:$Tag" -ForegroundColor $Blue
    Write-Host "  • caldev-backend:$Tag" -ForegroundColor $Blue
    Write-Host "  • caldev-roundcube:$Tag" -ForegroundColor $Blue
    Write-Host ""
    Write-Host "To pull these images:" -ForegroundColor $Yellow
    Write-Host "  docker pull $ECRRegistry/caldev-frontend:$Tag" -ForegroundColor $Blue
    Write-Host "  docker pull $ECRRegistry/caldev-backend:$Tag" -ForegroundColor $Blue
    Write-Host "  docker pull $ECRRegistry/caldev-roundcube:$Tag" -ForegroundColor $Blue
    Write-Host ""
}

# Main execution
if ($Help) {
    Show-Usage
    exit 0
}

Write-Host "🚀 CalDAV Calendar App - AWS ECR Push" -ForegroundColor $Blue
Write-Host "==============================================" -ForegroundColor $Blue

$ECRRegistry = Test-Prerequisites
New-ECRRepositories -Region $Region
Invoke-DockerECRAuth -Region $Region -ECRRegistry $ECRRegistry

# Determine which images to push
$pushFrontend = $true
$pushBackend = $true
$pushRoundcube = $true

if ($FrontendOnly) {
    $pushBackend = $false
    $pushRoundcube = $false
}
elseif ($BackendOnly) {
    $pushFrontend = $false
    $pushRoundcube = $false
}
elseif ($RoundcubeOnly) {
    $pushFrontend = $false
    $pushBackend = $false
}

if ($pushFrontend) {
    Push-FrontendImage -ECRRegistry $ECRRegistry -Tag $Tag
}

if ($pushBackend) {
    Push-BackendImage -ECRRegistry $ECRRegistry -Tag $Tag
}

if ($pushRoundcube) {
    Push-RoundcubeImage -ECRRegistry $ECRRegistry -Tag $Tag
}

Show-Summary -ECRRegistry $ECRRegistry -Tag $Tag

