# CalDAV Calendar Angular App - Docker Setup

This directory contains Docker configuration for running the Angular CalDAV Calendar application outside of Cursor.

## Quick Start

### Development Mode (with hot reload)
```bash
# Build and run the development container
docker-compose up angular-dev

# Or run in background
docker-compose up -d angular-dev
```

The Angular app will be available at: http://localhost:4200

### Production Mode
```bash
# Build and run the production container
docker-compose --profile production up angular-prod

# Or run in background
docker-compose --profile production up -d angular-prod
```

The Angular app will be available at: http://localhost:8080

### Full Stack (Frontend + Backend)
```bash
# Run both Angular frontend and PHP backend
docker-compose up angular-dev php-backend

# Or run in background
docker-compose up -d angular-dev php-backend
```

## Available Services

- **angular-dev**: Angular development server with hot reload (port 4200)
- **angular-prod**: Production Angular app served by nginx (port 8080)
- **php-backend**: PHP backend API server (port 8000)

## Docker Files Explained

### Dockerfile (Production)
- Multi-stage build using Node.js 18 Alpine
- Builds the Angular app for production
- Serves the app using nginx
- Optimized for production with gzip compression and caching

### Dockerfile.dev (Development)
- Single-stage build using Node.js 18 Alpine
- Installs all dependencies including dev dependencies
- Runs Angular development server with hot reload
- Mounts source code for live development

### nginx.conf
- Custom nginx configuration for Angular SPA
- Handles client-side routing
- Enables gzip compression
- Sets up proper caching for static assets
- Includes security headers

### docker-compose.yml
- Orchestrates multiple services
- Sets up networking between services
- Configures volume mounts for development
- Uses profiles to separate dev/prod environments

## Development Workflow

1. **Start development environment:**
   ```bash
   docker-compose up angular-dev
   ```

2. **Make changes to your code** - changes will be reflected immediately due to hot reload

3. **Access the application:**
   - Frontend: http://localhost:4200
   - Backend: http://localhost:8000 (if running php-backend service)

4. **Stop the environment:**
   ```bash
   docker-compose down
   ```

## Building Production Images

```bash
# Build production Angular image
docker build -t caldev-angular-prod .

# Build development Angular image
docker build -f Dockerfile.dev -t caldev-angular-dev .

# Build PHP backend image
docker build -t caldev-php-backend ../backend
```

## Troubleshooting

### Port Conflicts
If you get port conflicts, modify the ports in `docker-compose.yml`:
```yaml
ports:
  - "4201:4200"  # Change 4200 to 4201
```

### Permission Issues
On Linux/macOS, you might need to fix permissions:
```bash
sudo chown -R $USER:$USER .
```

### Clear Docker Cache
If you encounter build issues:
```bash
docker-compose down
docker system prune -f
docker-compose up --build
```

## Environment Variables

You can customize the setup using environment variables in a `.env` file:

```env
# Angular Development
ANGULAR_PORT=4200
NODE_ENV=development

# Angular Production
NGINX_PORT=8080

# PHP Backend
PHP_PORT=8000
```

## Notes

- The development setup includes volume mounts for live code editing
- Production setup uses nginx for optimal performance
- All services are connected via a Docker network for communication
- The PHP backend service is optional and can be run separately
