@echo off
REM CalDAV Calendar Angular App - Docker Helper Scripts (Windows)

echo 🚀 CalDAV Calendar Angular App - Docker Setup
echo ==============================================

if "%1"=="dev" goto start_dev
if "%1"=="dev-only" goto start_dev_only
if "%1"=="prod" goto start_prod
if "%1"=="build" goto build_all
if "%1"=="stop" goto stop_containers
if "%1"=="clean" goto clean_docker
if "%1"=="logs" goto show_logs
if "%1"=="shell" goto open_shell
if "%1"=="help" goto show_usage
if "%1"=="" goto show_usage
goto unknown_command

:start_dev
echo 🔧 Starting development environment...
docker-compose up -d angular-dev php-backend
echo ✅ Development environment started!
echo 📱 Angular app: http://localhost:4200
echo 🔗 PHP backend: http://localhost:8000
goto end

:start_dev_only
echo 🔧 Starting Angular development server...
docker-compose up -d angular-dev
echo ✅ Angular development server started!
echo 📱 Angular app: http://localhost:4200
goto end

:start_prod
echo 🏭 Starting production environment...
docker-compose --profile production up -d angular-prod
echo ✅ Production environment started!
echo 📱 Angular app: http://localhost:8080
goto end

:build_all
echo 🔨 Building all Docker images...
docker-compose build
echo ✅ All images built successfully!
goto end

:stop_containers
echo 🛑 Stopping all containers...
docker-compose down
echo ✅ All containers stopped!
goto end

:clean_docker
echo 🧹 Cleaning up Docker resources...
docker-compose down
docker system prune -f
docker volume prune -f
echo ✅ Docker cleanup completed!
goto end

:show_logs
echo 📋 Showing logs from running containers...
docker-compose logs -f
goto end

:open_shell
echo 🐚 Opening shell in Angular container...
docker-compose exec angular-dev sh
goto end

:show_usage
echo Usage: %0 [command]
echo.
echo Commands:
echo   dev         Start development environment (Angular + PHP backend)
echo   dev-only    Start only Angular development server
echo   prod        Start production environment
echo   build       Build all Docker images
echo   stop        Stop all running containers
echo   clean       Clean up Docker resources
echo   logs        Show logs from running containers
echo   shell       Open shell in Angular container
echo   help        Show this help message
echo.
goto end

:unknown_command
echo ❌ Unknown command: %1
echo.
goto show_usage

:end
