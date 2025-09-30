#!/bin/bash

# CalDAV Calendar App - Linux Server Deployment Script
# This script deploys the application to a Linux server using Docker containers

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
AWS_REGION="us-west-2"
AWS_ACCOUNT_ID="248189916187"
ECR_REGISTRY="${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com"
FRONTEND_REPO="caldev-frontend"
BACKEND_REPO="caldev-backend"
CONTAINER_NAME="caldev-calendar"
DOCKER_COMPOSE_FILE="docker-compose.yml"

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if a command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check prerequisites
check_prerequisites() {
    print_status "Checking prerequisites..."
    
    # Check if Docker is installed
    if ! command_exists docker; then
        print_error "Docker is not installed. Please install Docker first."
        exit 1
    fi
    
    # Check if Docker Compose is installed
    if ! command_exists docker-compose && ! docker compose version >/dev/null 2>&1; then
        print_error "Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi
    
    # Check if AWS CLI is installed
    if ! command_exists aws; then
        print_error "AWS CLI is not installed. Please install AWS CLI first."
        exit 1
    fi
    
    # Check AWS credentials
    if ! aws sts get-caller-identity >/dev/null 2>&1; then
        print_error "AWS credentials not configured. Please run 'aws configure' first."
        exit 1
    fi
    
    print_success "All prerequisites check passed!"
}

# Function to authenticate with ECR
authenticate_ecr() {
    print_status "Authenticating Docker with AWS ECR..."
    
    if aws ecr get-login-password --region "$AWS_REGION" | docker login --username AWS --password-stdin "$ECR_REGISTRY"; then
        print_success "Docker authenticated with ECR"
    else
        print_error "Failed to authenticate with ECR. Check your AWS credentials and permissions."
        exit 1
    fi
}

# Function to check ECR repositories exist
check_ecr_repositories() {
    print_status "Checking ECR repositories..."
    
    # Check frontend repository
    if aws ecr describe-repositories --repository-names "$FRONTEND_REPO" --region "$AWS_REGION" >/dev/null 2>&1; then
        print_success "Frontend repository exists: $FRONTEND_REPO"
    else
        print_error "Frontend repository '$FRONTEND_REPO' does not exist in ECR."
        print_status "Creating frontend repository..."
        aws ecr create-repository --repository-name "$FRONTEND_REPO" --region "$AWS_REGION"
        print_success "Frontend repository created"
    fi
    
    # Check backend repository
    if aws ecr describe-repositories --repository-names "$BACKEND_REPO" --region "$AWS_REGION" >/dev/null 2>&1; then
        print_success "Backend repository exists: $BACKEND_REPO"
    else
        print_error "Backend repository '$BACKEND_REPO' does not exist in ECR."
        print_status "Creating backend repository..."
        aws ecr create-repository --repository-name "$BACKEND_REPO" --region "$AWS_REGION"
        print_success "Backend repository created"
    fi
}

# Function to pull latest images
pull_images() {
    print_status "Pulling latest images from ECR..."
    
    # Pull frontend image
    print_status "Pulling frontend image..."
    if docker pull "${ECR_REGISTRY}/${FRONTEND_REPO}:latest" 2>/dev/null; then
        print_success "Frontend image pulled successfully"
    else
        print_warning "Failed to pull frontend image. It may not exist yet."
    fi
    
    # Pull backend image
    print_status "Pulling backend image..."
    if docker pull "${ECR_REGISTRY}/${BACKEND_REPO}:latest" 2>/dev/null; then
        print_success "Backend image pulled successfully"
    else
        print_warning "Failed to pull backend image. It may not exist yet."
    fi
}

# Function to stop existing containers
stop_existing_containers() {
    print_status "Stopping existing containers..."
    
    # Use docker-compose if available, otherwise use docker compose
    if command_exists docker-compose; then
        COMPOSE_CMD="docker-compose"
    else
        COMPOSE_CMD="docker compose"
    fi
    
    # Stop containers if docker-compose.yml exists
    if [ -f "$DOCKER_COMPOSE_FILE" ]; then
        if $COMPOSE_CMD down 2>/dev/null; then
            print_success "Existing containers stopped"
        else
            print_warning "No existing containers to stop"
        fi
    else
        # Stop containers by name pattern
        if docker ps -q --filter "name=$CONTAINER_NAME" | grep -q .; then
            docker stop $(docker ps -q --filter "name=$CONTAINER_NAME") 2>/dev/null || true
            docker rm $(docker ps -aq --filter "name=$CONTAINER_NAME") 2>/dev/null || true
            print_success "Existing containers stopped"
        else
            print_warning "No existing containers to stop"
        fi
    fi
}

# Function to create docker-compose.yml if it doesn't exist
create_docker_compose() {
    print_status "Creating/updating docker-compose.yml..."
    
    cat > "$DOCKER_COMPOSE_FILE" << EOF
version: '3.8'

services:
  # Angular Frontend - Development
  angular-dev:
    image: ${ECR_REGISTRY}/${FRONTEND_REPO}:latest
    ports:
      - "4200:4200"
    environment:
      - NODE_ENV=development
      - API_URL=http://php-backend:80
    networks:
      - caldev-network
    restart: unless-stopped

  # Angular Frontend - Production
  angular-prod:
    image: ${ECR_REGISTRY}/${FRONTEND_REPO}:latest
    ports:
      - "8080:80"
    environment:
      - API_URL=http://php-backend:80
    networks:
      - caldev-network
    profiles:
      - production
    restart: unless-stopped

  # PHP Backend
  php-backend:
    image: ${ECR_REGISTRY}/${BACKEND_REPO}:latest
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
    
    print_success "docker-compose.yml created/updated"
}

# Function to start the application
start_application() {
    print_status "Starting the application..."
    
    # Use docker-compose if available, otherwise use docker compose
    if command_exists docker-compose; then
        COMPOSE_CMD="docker-compose"
    else
        COMPOSE_CMD="docker compose"
    fi
    
    # Start services
    if $COMPOSE_CMD up -d angular-dev php-backend; then
        print_success "Application started successfully"
    else
        print_error "Failed to start the application"
        exit 1
    fi
    
    # Wait for services to be ready
    print_status "Waiting for services to be ready..."
    sleep 10
}

# Function to check application health
check_application_health() {
    print_status "Checking application health..."
    
    # Check if frontend is responding
    if curl -f http://localhost:4200 >/dev/null 2>&1; then
        print_success "Frontend is accessible on port 4200"
    else
        print_warning "Frontend may not be fully ready on port 4200"
    fi
    
    # Check if backend is responding
    if curl -f http://localhost:8000 >/dev/null 2>&1; then
        print_success "Backend is accessible on port 8000"
    else
        print_warning "Backend may not be fully ready on port 8000"
    fi
    
    # Show container status
    print_status "Container status:"
    if command_exists docker-compose; then
        docker-compose ps
    else
        docker compose ps
    fi
}

# Function to show logs
show_logs() {
    print_status "Recent logs:"
    echo "=================="
    
    if command_exists docker-compose; then
        docker-compose logs --tail=10
    else
        docker compose logs --tail=10
    fi
}

# Function to show deployment summary
show_deployment_summary() {
    echo ""
    echo "=============================================="
    echo -e "${GREEN}🚀 Deployment Summary${NC}"
    echo "=============================================="
    echo -e "Frontend URL:  ${BLUE}http://$(hostname -I | awk '{print $1}'):4200${NC}"
    echo -e "Backend URL:   ${BLUE}http://$(hostname -I | awk '{print $1}'):8000${NC}"
    echo -e "Local Frontend: ${BLUE}http://localhost:4200${NC}"
    echo -e "Local Backend:  ${BLUE}http://localhost:8000${NC}"
    echo ""
    echo "Container Management:"
    echo "  View logs:     docker compose logs -f"
    echo "  Stop app:      docker compose down"
    echo "  Restart app:   docker compose restart"
    echo "  Update app:    ./deploy-app.sh"
    echo ""
}

# Function to install curl if missing
install_curl() {
    if ! command_exists curl; then
        print_status "Installing curl..."
        if command_exists apt-get; then
            apt-get update && apt-get install -y curl
        elif command_exists yum; then
