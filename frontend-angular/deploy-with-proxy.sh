#!/bin/bash

# Deploy CalDev Calendar with Nginx Reverse Proxy
# This script builds and deploys all containers including the reverse proxy

set -e

echo "🚀 Starting CalDev Calendar deployment with reverse proxy..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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

# Check if we're in the right directory
if [ ! -f "docker-compose.yml" ]; then
    print_error "docker-compose.yml not found. Please run this script from the frontend-angular directory."
    exit 1
fi

# Check if nginx-proxy directory exists
if [ ! -d "nginx-proxy" ]; then
    print_error "nginx-proxy directory not found. Please ensure the nginx-proxy directory exists."
    exit 1
fi

# Stop existing containers
print_status "Stopping existing containers..."
docker compose down

# Build the nginx proxy
print_status "Building nginx reverse proxy..."
docker compose build nginx-proxy

# Start all containers
print_status "Starting all containers..."
docker compose up -d

# Wait for containers to be ready
print_status "Waiting for containers to be ready..."
sleep 10

# Check container status
print_status "Checking container status..."
docker compose ps

# Test the reverse proxy
print_status "Testing reverse proxy..."
if curl -f -s http://localhost:9443/ > /dev/null; then
    print_success "Reverse proxy is working!"
else
    print_warning "Reverse proxy test failed. Check container logs."
fi

# Test the API through the proxy
print_status "Testing API through reverse proxy..."
if curl -f -s http://localhost:9443/api/ > /dev/null; then
    print_success "API is accessible through reverse proxy!"
else
    print_warning "API test failed. Check container logs."
fi

# Show access information
echo ""
print_success "🎉 Deployment completed!"
echo ""
echo "📋 Access Information:"
echo "  Frontend: http://34.212.53.8:9443/"
echo "  API:      http://34.212.53.8:9443/api/"
echo ""
echo "🔧 Container Management:"
echo "  View logs:    docker compose logs -f"
echo "  Stop all:     docker compose down"
echo "  Restart all:  docker compose restart"
echo "  View status:  docker compose ps"
echo ""

# Show container logs
print_status "Recent container logs:"
docker compose logs --tail=20
