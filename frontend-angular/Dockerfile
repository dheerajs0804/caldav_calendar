# Multi-stage build for Angular app
# Stage 1: Build the Angular app
FROM node:18-alpine AS build

# Set working directory
WORKDIR /app

# Copy package files
COPY package*.json ./

# Install all dependencies (including dev dependencies for build)
RUN npm install

# Copy source code
COPY . .

# Build the Angular app for Docker containers
RUN npm run build --configuration=docker

# Stage 2: Serve the app with nginx
FROM nginx:alpine

# Copy the built app from the build stage
COPY --from=build /app/dist/caldev-calendar-angular /usr/share/nginx/html

# Copy custom nginx configuration
COPY nginx.conf /etc/nginx/nginx.conf

# Create environment injection script
RUN echo '#!/bin/sh' > /docker-entrypoint.d/99-inject-env.sh && \
    echo 'set -e' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo '' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo '# Environment injection script for Angular app' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo 'echo "🔧 Starting environment injection..."' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo '' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo '# Get the API URL from environment variable' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo 'API_URL=${API_URL:-"http://localhost:8000"}' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo '' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo '# Create assets directory if it does not exist' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo 'mkdir -p /usr/share/nginx/html/assets' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo '' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo '# Create a JavaScript file with the environment variables' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo 'cat > /usr/share/nginx/html/assets/env.js << EOL' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo '// Runtime environment configuration' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo 'window.API_URL = "${API_URL}";' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo 'console.log("🔗 Injected API URL:", window.API_URL);' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo 'EOL' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo '' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo 'echo "✅ Environment variables injected:"' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo 'echo "   API_URL: ${API_URL}"' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo '' >> /docker-entrypoint.d/99-inject-env.sh && \
    echo 'echo "🔧 Environment injection completed successfully"' >> /docker-entrypoint.d/99-inject-env.sh && \
    chmod +x /docker-entrypoint.d/99-inject-env.sh

# Expose port 80
EXPOSE 80

# Start nginx
CMD ["nginx", "-g", "daemon off;"]
