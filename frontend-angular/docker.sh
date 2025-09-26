#!/bin/bash

# CalDAV Calendar Angular App - Docker Helper Scripts

echo "🚀 CalDAV Calendar Angular App - Docker Setup"
echo "=============================================="

# Function to show usage
show_usage() {
    echo "Usage: $0 [command]"
    echo ""
    echo "Commands:"
    echo "  dev         Start development environment (Angular + PHP backend)"
    echo "  dev-only    Start only Angular development server"
    echo "  prod        Start production environment"
    echo "  build       Build all Docker images"
    echo "  stop        Stop all running containers"
    echo "  clean       Clean up Docker resources"
    echo "  logs        Show logs from running containers"
    echo "  shell       Open shell in Angular container"
    echo "  help        Show this help message"
    echo ""
}

# Function to start development environment
start_dev() {
    echo "🔧 Starting development environment..."
    docker-compose up -d angular-dev php-backend
    echo "✅ Development environment started!"
    echo "📱 Angular app: http://localhost:4200"
    echo "🔗 PHP backend: http://localhost:8000"
}

# Function to start only Angular development
start_dev_only() {
    echo "🔧 Starting Angular development server..."
    docker-compose up -d angular-dev
    echo "✅ Angular development server started!"
    echo "📱 Angular app: http://localhost:4200"
}

# Function to start production environment
start_prod() {
    echo "🏭 Starting production environment..."
    docker-compose --profile production up -d angular-prod
    echo "✅ Production environment started!"
    echo "📱 Angular app: http://localhost:8080"
}

# Function to build all images
build_all() {
    echo "🔨 Building all Docker images..."
    docker-compose build
    echo "✅ All images built successfully!"
}

# Function to stop containers
stop_containers() {
    echo "🛑 Stopping all containers..."
    docker-compose down
    echo "✅ All containers stopped!"
}

# Function to clean up Docker resources
clean_docker() {
    echo "🧹 Cleaning up Docker resources..."
    docker-compose down
    docker system prune -f
    docker volume prune -f
    echo "✅ Docker cleanup completed!"
}

# Function to show logs
show_logs() {
    echo "📋 Showing logs from running containers..."
    docker-compose logs -f
}

# Function to open shell in Angular container
open_shell() {
    echo "🐚 Opening shell in Angular container..."
    docker-compose exec angular-dev sh
}

# Main script logic
case "$1" in
    "dev")
        start_dev
        ;;
    "dev-only")
        start_dev_only
        ;;
    "prod")
        start_prod
        ;;
    "build")
        build_all
        ;;
    "stop")
        stop_containers
        ;;
    "clean")
        clean_docker
        ;;
    "logs")
        show_logs
        ;;
    "shell")
        open_shell
        ;;
    "help"|"-h"|"--help"|"")
        show_usage
        ;;
    *)
        echo "❌ Unknown command: $1"
        echo ""
        show_usage
        exit 1
        ;;
esac
