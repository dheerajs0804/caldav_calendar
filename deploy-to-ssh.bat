@echo off
echo ============================================
echo Deploy to SSH Server
echo ============================================

echo.
echo Step 1: Copy docker-compose.yml to SSH server
echo Please run this command (replace with your actual server IP and key path):
echo.
echo scp -i /path/to/your/key.pem frontend-angular/docker-compose.yml ubuntu@YOUR_SERVER_IP:/path/to/your/directory/
echo.
echo Example:
echo scp -i ~/.ssh/my-key.pem frontend-angular/docker-compose.yml ubuntu@10.0.0.50:/home/ubuntu/caldav_calendar/
echo.
echo Step 2: On SSH server, run:
echo.
echo # Authenticate with ECR
echo aws ecr get-login-password --region us-west-2 ^| docker login --username AWS --password-stdin 248189916187.dkr.ecr.us-west-2.amazonaws.com
echo.
echo # Pull latest images
echo docker pull 248189916187.dkr.ecr.us-west-2.amazonaws.com/caldev-frontend:latest
echo docker pull 248189916187.dkr.ecr.us-west-2.amazonaws.com/caldev-backend:latest
echo.
echo # Stop existing containers
echo docker-compose down
echo.
echo # Start with docker-compose
echo docker-compose up -d angular-dev php-backend
echo.
echo # Check status
echo docker-compose ps
echo docker logs caldev-frontend
echo docker logs caldev-backend
echo.
echo # Test the connection
echo curl http://localhost:8000/health
echo curl http://localhost:4200
echo.
pause
