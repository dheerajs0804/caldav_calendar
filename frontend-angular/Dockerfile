# Multi-stage build for Angular app
# Stage 1: Build the Angular app
FROM node:18-alpine AS build

# Set working directory
WORKDIR /app

# Copy package files
COPY package*.json ./

# Install all dependencies (including dev dependencies for build)
RUN npm ci

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

# Copy environment injection script
COPY inject-env.sh /docker-entrypoint.d/99-inject-env.sh
RUN chmod +x /docker-entrypoint.d/99-inject-env.sh

# Expose port 80
EXPOSE 80

# Start nginx
CMD ["nginx", "-g", "daemon off;"]
