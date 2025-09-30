FROM nginx:alpine

# Copy custom nginx configuration
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Create necessary directories
RUN mkdir -p /var/log/nginx

# Expose port 9443
EXPOSE 9443

# Start nginx
CMD ["nginx", "-g", "daemon off;"]
