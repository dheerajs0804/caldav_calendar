#!/bin/bash

# Dynamic CalDAV Calendar App Deployment Script
# This script automatically detects the server IP and deploys the application

echo "🚀 CalDAV Calendar App - Dynamic Deployment"
echo "=========================================="

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
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

# Step 1: Detect server IP
log_info "Detecting server IP address..."

# Try to get public IP
PUBLIC_IP=$(curl -s ifconfig.me 2>/dev/null || curl -s ipinfo.io/ip 2>/dev/null || curl -s icanhazip.com 2>/dev/null)

# Try to get private IP
PRIVATE_IP=$(hostname -I | awk '{print $1}' 2>/dev/null)

# Use the first available IP
if [ -n "$PUBLIC_IP" ]; then
    SERVER_IP="$PUBLIC_IP"
    log_success "Detected public IP: $SERVER_IP"
elif [ -n "$PRIVATE_IP" ]; then
    SERVER_IP="$PRIVATE_IP"
    log_success "Detected private IP: $SERVER_IP"
else
    log_error "Could not detect server IP automatically"
    read -p "Please enter the server IP address: " SERVER_IP
    if [ -z "$SERVER_IP" ]; then
        log_error "No IP address provided. Exiting."
        exit 1
    fi
fi

# Step 2: Check prerequisites
log_info "Checking prerequisites..."

if ! command -v docker &> /dev/null; then
    log_error "Docker not found. Install Docker first."
    exit 1
fi

if ! command -v aws &> /dev/null; then
    log_error "AWS CLI not found. Install AWS CLI first."
    exit 1
fi

# Step 3: Authenticate with ECR
log_info "Authenticating with AWS ECR..."
AWS_REGION="us-west-2"
AWS_ACCOUNT_ID="248189916187"
ECR_REGISTRY="${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com"

aws ecr get-login-password --region $AWS_REGION | docker login --username AWS --password-stdin $ECR_REGISTRY
if [ $? -eq 0 ]; then
    log_success "ECR authentication successful"
else
    log_error "ECR authentication failed"
    exit 1
fi

# Step 4: Pull latest images
log_info "Pulling latest Docker images..."
docker pull $ECR_REGISTRY/caldev-frontend:latest
docker pull $ECR_REGISTRY/caldev-backend:latest

if [ $? -eq 0 ]; then
    log_success "Images pulled successfully"
else
    log_error "Failed to pull images"
    exit 1
fi

# Step 5: Stop existing containers
log_info "Stopping existing containers..."
docker compose down

# Step 6: Set environment variables and start containers
log_info "Starting application with server IP: $SERVER_IP"

# Export environment variables
export SERVER_IP="$SERVER_IP"
export SERVER_DOMAIN=""
export CALDAV_SERVER_URL="http://rc.mithi.com:8008"
export APP_DEBUG="false"
export APP_ENV="production"

# Start containers
docker compose up -d

if [ $? -eq 0 ]; then
    log_success "Application started successfully"
else
    log_error "Failed to start application"
    exit 1
fi

# Step 7: Wait for services to be ready
log_info "Waiting for services to be ready..."
sleep 10

# Step 8: Check container status
log_info "Checking container status..."
docker compose ps

# Step 9: Test endpoints
log_info "Testing application endpoints..."

# Test backend
if curl -s http://localhost:8000 > /dev/null; then
    log_success "Backend is responding on port 8000"
else
    log_warning "Backend may not be ready yet on port 8000"
fi

# Test frontend
if curl -s http://localhost:4200 > /dev/null; then
    log_success "Frontend is responding on port 4200"
else
    log_warning "Frontend may not be ready yet on port 4200"
fi

# Step 10: Display access information
echo ""
log_success "🎉 Deployment completed successfully!"
echo "=========================================="
echo -e "${BLUE}Application URLs:${NC}"
echo -e "  Frontend: ${GREEN}http://$SERVER_IP:4200${NC}"
echo -e "  Backend:  ${GREEN}http://$SERVER_IP:8000${NC}"
echo ""
echo -e "${BLUE}Local URLs:${NC}"
echo -e "  Frontend: ${GREEN}http://localhost:4200${NC}"
echo -e "  Backend:  ${GREEN}http://localhost:8000${NC}"
echo ""
echo -e "${BLUE}Management Commands:${NC}"
echo -e "  View logs:     ${YELLOW}docker compose logs -f${NC}"
echo -e "  Stop app:      ${YELLOW}docker compose down${NC}"
echo -e "  Restart app:   ${YELLOW}docker compose restart${NC}"
echo -e "  Check status:  ${YELLOW}docker compose ps${NC}"
echo ""

# Step 11: Save configuration
log_info "Saving deployment configuration..."
cat > .env << EOF
# CalDAV Calendar App - Deployment Configuration
# Generated on: $(date)
SERVER_IP=$SERVER_IP
SERVER_DOMAIN=
CALDAV_SERVER_URL=http://rc.mithi.com:8008
APP_DEBUG=false
APP_ENV=production
EOF

log_success "Configuration saved to .env file"
log_info "You can modify .env and run 'docker compose up -d' to apply changes"
