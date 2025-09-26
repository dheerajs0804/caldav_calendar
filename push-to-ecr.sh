#!/bin/bash

# CalDAV Calendar App - AWS ECR Push Script
# This script builds and pushes all Docker images to AWS ECR

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration - Update these values for your AWS setup
AWS_REGION=${AWS_REGION:-"us-east-1"}
AWS_ACCOUNT_ID=${AWS_ACCOUNT_ID:-""}
ECR_REGISTRY=""

# Image names and tags
FRONTEND_IMAGE="caldev-frontend"
BACKEND_IMAGE="caldev-backend"
ROUNDCUBE_IMAGE="caldev-roundcube"
TAG=${TAG:-"latest"}

echo -e "${BLUE}🚀 CalDAV Calendar App - AWS ECR Push${NC}"
echo "=============================================="

# Function to show usage
show_usage() {
    echo "Usage: $0 [options]"
    echo ""
    echo "Options:"
    echo "  --region REGION     AWS region (default: us-east-1)"
    echo "  --account-id ID     AWS account ID (required)"
    echo "  --tag TAG          Image tag (default: latest)"
    echo "  --frontend-only    Push only frontend image"
    echo "  --backend-only     Push only backend image"
    echo "  --roundcube-only   Push only roundcube image"
    echo "  --help             Show this help message"
    echo ""
    echo "Environment Variables:"
    echo "  AWS_REGION         AWS region"
    echo "  AWS_ACCOUNT_ID     AWS account ID"
    echo "  TAG                Image tag"
    echo ""
}

# Function to check prerequisites
check_prerequisites() {
    echo -e "${YELLOW}🔍 Checking prerequisites...${NC}"
    
    # Check if AWS CLI is installed
    if ! command -v aws &> /dev/null; then
        echo -e "${RED}❌ AWS CLI is not installed. Please install it first.${NC}"
        exit 1
    fi
    
    # Check if Docker is running
    if ! docker info &> /dev/null; then
        echo -e "${RED}❌ Docker is not running. Please start Docker first.${NC}"
        exit 1
    fi
    
    # Check AWS credentials
    if ! aws sts get-caller-identity &> /dev/null; then
        echo -e "${RED}❌ AWS credentials not configured. Please run 'aws configure' first.${NC}"
        exit 1
    fi
    
    # Get AWS account ID if not provided
    if [ -z "$AWS_ACCOUNT_ID" ]; then
        echo -e "${YELLOW}📋 Getting AWS account ID...${NC}"
        AWS_ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text)
        if [ -z "$AWS_ACCOUNT_ID" ]; then
            echo -e "${RED}❌ Could not get AWS account ID${NC}"
            exit 1
        fi
    fi
    
    ECR_REGISTRY="${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com"
    echo -e "${GREEN}✅ Prerequisites check passed${NC}"
    echo -e "${BLUE}   AWS Account ID: ${AWS_ACCOUNT_ID}${NC}"
    echo -e "${BLUE}   AWS Region: ${AWS_REGION}${NC}"
    echo -e "${BLUE}   ECR Registry: ${ECR_REGISTRY}${NC}"
}

# Function to create ECR repositories
create_ecr_repositories() {
    echo -e "${YELLOW}📦 Creating ECR repositories...${NC}"
    
    # Create repositories if they don't exist
    for repo in $FRONTEND_IMAGE $BACKEND_IMAGE $ROUNDCUBE_IMAGE; do
        if ! aws ecr describe-repositories --repository-names $repo --region $AWS_REGION &> /dev/null; then
            echo -e "${YELLOW}   Creating repository: $repo${NC}"
            aws ecr create-repository --repository-name $repo --region $AWS_REGION
        else
            echo -e "${GREEN}   Repository $repo already exists${NC}"
        fi
    done
}

# Function to authenticate Docker with ECR
authenticate_docker() {
    echo -e "${YELLOW}🔐 Authenticating Docker with ECR...${NC}"
    aws ecr get-login-password --region $AWS_REGION | docker login --username AWS --password-stdin $ECR_REGISTRY
    echo -e "${GREEN}✅ Docker authenticated with ECR${NC}"
}

# Function to build and push frontend image
push_frontend() {
    echo -e "${YELLOW}🏗️  Building and pushing frontend image...${NC}"
    
    # Build Angular frontend
    echo -e "${BLUE}   Building Angular frontend...${NC}"
    cd frontend-angular
    docker build -t $FRONTEND_IMAGE:$TAG -f Dockerfile .
    
    # Tag for ECR
    docker tag $FRONTEND_IMAGE:$TAG $ECR_REGISTRY/$FRONTEND_IMAGE:$TAG
    
    # Push to ECR
    echo -e "${BLUE}   Pushing to ECR...${NC}"
    docker push $ECR_REGISTRY/$FRONTEND_IMAGE:$TAG
    
    cd ..
    echo -e "${GREEN}✅ Frontend image pushed successfully${NC}"
}

# Function to build and push backend image
push_backend() {
    echo -e "${YELLOW}🏗️  Building and pushing backend image...${NC}"
    
    # Build PHP backend
    echo -e "${BLUE}   Building PHP backend...${NC}"
    cd backend
    docker build -t $BACKEND_IMAGE:$TAG -f Dockerfile .
    
    # Tag for ECR
    docker tag $BACKEND_IMAGE:$TAG $ECR_REGISTRY/$BACKEND_IMAGE:$TAG
    
    # Push to ECR
    echo -e "${BLUE}   Pushing to ECR...${NC}"
    docker push $ECR_REGISTRY/$BACKEND_IMAGE:$TAG
    
    cd ..
    echo -e "${GREEN}✅ Backend image pushed successfully${NC}"
}

# Function to build and push roundcube image
push_roundcube() {
    echo -e "${YELLOW}🏗️  Building and pushing roundcube image...${NC}"
    
    # Build Roundcube
    echo -e "${BLUE}   Building Roundcube...${NC}"
    docker build -t $ROUNDCUBE_IMAGE:$TAG -f Dockerfile .
    
    # Tag for ECR
    docker tag $ROUNDCUBE_IMAGE:$TAG $ECR_REGISTRY/$ROUNDCUBE_IMAGE:$TAG
    
    # Push to ECR
    echo -e "${BLUE}   Pushing to ECR...${NC}"
    docker push $ECR_REGISTRY/$ROUNDCUBE_IMAGE:$TAG
    
    echo -e "${GREEN}✅ Roundcube image pushed successfully${NC}"
}

# Function to show summary
show_summary() {
    echo ""
    echo -e "${GREEN}🎉 Push completed successfully!${NC}"
    echo "=============================================="
    echo -e "${BLUE}ECR Registry: ${ECR_REGISTRY}${NC}"
    echo -e "${BLUE}Images pushed:${NC}"
    echo -e "  • ${FRONTEND_IMAGE}:${TAG}"
    echo -e "  • ${BACKEND_IMAGE}:${TAG}"
    echo -e "  • ${ROUNDCUBE_IMAGE}:${TAG}"
    echo ""
    echo -e "${YELLOW}To pull these images:${NC}"
    echo "  docker pull $ECR_REGISTRY/$FRONTEND_IMAGE:$TAG"
    echo "  docker pull $ECR_REGISTRY/$BACKEND_IMAGE:$TAG"
    echo "  docker pull $ECR_REGISTRY/$ROUNDCUBE_IMAGE:$TAG"
    echo ""
}

# Parse command line arguments
PUSH_FRONTEND=true
PUSH_BACKEND=true
PUSH_ROUNDCUBE=true

while [[ $# -gt 0 ]]; do
    case $1 in
        --region)
            AWS_REGION="$2"
            shift 2
            ;;
        --account-id)
            AWS_ACCOUNT_ID="$2"
            shift 2
            ;;
        --tag)
            TAG="$2"
            shift 2
            ;;
        --frontend-only)
            PUSH_FRONTEND=true
            PUSH_BACKEND=false
            PUSH_ROUNDCUBE=false
            shift
            ;;
        --backend-only)
            PUSH_FRONTEND=false
            PUSH_BACKEND=true
            PUSH_ROUNDCUBE=false
            shift
            ;;
        --roundcube-only)
            PUSH_FRONTEND=false
            PUSH_BACKEND=false
            PUSH_ROUNDCUBE=true
            shift
            ;;
        --help|-h)
            show_usage
            exit 0
            ;;
        *)
            echo -e "${RED}❌ Unknown option: $1${NC}"
            show_usage
            exit 1
            ;;
    esac
done

# Main execution
main() {
    check_prerequisites
    create_ecr_repositories
    authenticate_docker
    
    if [ "$PUSH_FRONTEND" = true ]; then
        push_frontend
    fi
    
    if [ "$PUSH_BACKEND" = true ]; then
        push_backend
    fi
    
    if [ "$PUSH_ROUNDCUBE" = true ]; then
        push_roundcube
    fi
    
    show_summary
}

# Run main function
main

