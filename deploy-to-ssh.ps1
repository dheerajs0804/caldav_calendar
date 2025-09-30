# Deploy to SSH Server with Key Authentication
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "Deploy to SSH Server" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

Write-Host ""
Write-Host "Step 1: Copy docker-compose.yml to SSH server" -ForegroundColor Yellow
Write-Host "Please run this command (replace with your actual server IP and key path):" -ForegroundColor White
Write-Host ""
Write-Host "scp -i /path/to/your/key.pem frontend-angular/docker-compose.yml ubuntu@YOUR_SERVER_IP:/path/to/your/directory/" -ForegroundColor Green
Write-Host ""
Write-Host "Example:" -ForegroundColor Yellow
Write-Host "scp -i ~/.ssh/my-key.pem frontend-angular/docker-compose.yml ubuntu@10.0.0.50:/home/ubuntu/caldav_calendar/" -ForegroundColor Green
Write-Host ""

# Prompt for server details
$serverIP = Read-Host "Enter your server IP (e.g., 10.0.0.50)"
$keyPath = Read-Host "Enter path to your SSH key (e.g., ~/.ssh/my-key.pem)"
$remotePath = Read-Host "Enter remote directory path (e.g., /home/ubuntu/caldav_calendar/)"

Write-Host ""
Write-Host "Step 2: Copy the file" -ForegroundColor Yellow
$copyCommand = "scp -i $keyPath frontend-angular/docker-compose.yml ubuntu@$serverIP`:$remotePath"
Write-Host "Running: $copyCommand" -ForegroundColor Green

try {
    Invoke-Expression $copyCommand
    Write-Host "✅ File copied successfully!" -ForegroundColor Green
} catch {
    Write-Host "❌ Copy failed. Please run the command manually:" -ForegroundColor Red
    Write-Host $copyCommand -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Step 3: SSH into your server and run these commands:" -ForegroundColor Yellow
Write-Host ""
Write-Host "# SSH into server" -ForegroundColor White
Write-Host "ssh -i $keyPath ubuntu@$serverIP" -ForegroundColor Green
Write-Host ""
Write-Host "# Navigate to directory" -ForegroundColor White
Write-Host "cd $remotePath" -ForegroundColor Green
Write-Host ""
Write-Host "# Authenticate with ECR" -ForegroundColor White
Write-Host "aws ecr get-login-password --region us-west-2 | docker login --username AWS --password-stdin 248189916187.dkr.ecr.us-west-2.amazonaws.com" -ForegroundColor Green
Write-Host ""
Write-Host "# Pull latest images" -ForegroundColor White
Write-Host "docker pull 248189916187.dkr.ecr.us-west-2.amazonaws.com/caldev-frontend:latest" -ForegroundColor Green
Write-Host "docker pull 248189916187.dkr.ecr.us-west-2.amazonaws.com/caldev-backend:latest" -ForegroundColor Green
Write-Host ""
Write-Host "# Stop existing containers" -ForegroundColor White
Write-Host "docker-compose down" -ForegroundColor Green
Write-Host ""
Write-Host "# Start with docker-compose" -ForegroundColor White
Write-Host "docker-compose up -d angular-dev php-backend" -ForegroundColor Green
Write-Host ""
Write-Host "# Check status" -ForegroundColor White
Write-Host "docker-compose ps" -ForegroundColor Green
Write-Host "docker logs caldev-frontend" -ForegroundColor Green
Write-Host "docker logs caldev-backend" -ForegroundColor Green
Write-Host ""
Write-Host "# Test the connection" -ForegroundColor White
Write-Host "curl http://localhost:8000/health" -ForegroundColor Green
Write-Host "curl http://localhost:4200" -ForegroundColor Green

Read-Host "Press Enter to continue"

