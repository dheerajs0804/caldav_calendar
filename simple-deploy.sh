#!/bin/bash

# Simple CalDAV Calendar App Deployment Script
# For Linux Servers

echo "🚀 CalDAV Calendar App - Simple Deployment"
echo "=========================================="

# Configuration
AWS_REGION="us-west-2"
AWS_ACCOUNT_ID="248189916187"
ECR_REGISTRY="${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com"
FRONTEND_REPO="caldev-frontend"
BACKEND_REPO="caldev-backend"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() {
    echo -e "ℹ️  $1"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Step 1: Check prerequisites
log_info "Checking prerequisites..."

if ! command -v docker &> /dev/null; then
    log_error "Docker not found. Install Docker first."
    exit 1
fi

if ! command -v aws &> /dev/null; then
    log_error "AWS CLI not found. Install AWS CLI first."
    exit 1
fi

log_success "Prerequisites check passed"

# Step: 2: Authenticate with ECR
log_info "Authenticating with ECR..."
aws ecr get-login-password --region $AWS_REGION | docker login --username AWS --password-stdin $ECR_REGISTRY
log_success "ECR authentication successful"

# Step 3: Stop existing containers
log_info "Stopping existing containers..."
docker compose down 2>/dev/null || docker-compose down 2>/dev/null || true
log_success "Existing containers stopped"

# Step 4: Pull latest images
log_info "Pulling latest images..."
docker pull $ECR_REGISTRY/$FRONTEND_REPO:latest
docker pull $ECR_REGISTRY/$BACKEND_REPO:latest
log_success "Images pulled successfully"

# Step 5: Create docker-compose.yml
log_info "Creating docker-compose.yml..."
cat > docker-compose.yml << EOF
version: '3.8'

services:
  angular-dev:
    image: $ECR_REGISTRY/$FRONTEND_REPO:latest
    ports:
      - "4200:4200"
    environment:
      - NODE_ENV=development
      - API_URL=http://php-backend:80
    networks:
      - caldev-network
    restart: unless-stopped

  php-backend:
    image: $ECR_REGISTRY/$BACKEND_REPO:latest
    ports:
      - "8000:80"
    environment:
      - PHP_ENV=development
    networks:
      - caldev-network
    restart: unless-stopped
    volumes:
      - backend_data:/var/www/html/data

networks:
  caldev-network:
    driver: bridge

volumes:
  backend_data:
    driver: local
EOF
log_success "docker-compose.yml created"

# Step 6: Start the application
log_info "Starting the application..."
docker compose up -d angular-dev php-backend || docker-compose up -d angular-dev php-backend
log_success "Application started"

# Step 7: Wait and check health
log_info "Waiting for services to be ready..."
sleep 10

# Install curl if missing
if ! command -v curl &> /dev/null; then
    log_info "Installing curl..."
    apt-get update && apt-get install -y curl 2>/dev/null || yum install -y curl 2>/dev/null || log_warning "Could not install curl"
fi

# Health check
log_info "Checking application health..."

if curl -f http://localhost:4200 >/dev/null 2>&1; then
    log_success "Frontend is accessible on http://localhost:4200"
else
    log_warning "Frontend may not be ready yet on port 4200"
fi

if curl -f http://localhost:8000 >/dev/null 2>&1; then
    log_success "Backend is accessible on http://localhost:8000"
else
    log_warning "Backend may not be ready yet on port 8000"
fi

# Show status
log_info "Container status:"
docker compose ps || docker-compose ps

# Deployment summary
echo ""
echo "🎉 Deployment Complete!"
echo "======================"
echo "Frontend: http://$(hostname -I | awk '{print $1}'):4200"
echo "Backend:  http://$(hostname -I | awk '{print $1}'):8000"
echo ""
echo "Management Commands:"
echo "  View logs: docker compose logs -f"
echo "  Stop app:  docker compose down"
echo "  Restart:   docker compose restart"
echo ""
