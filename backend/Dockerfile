# PHP Backend Dockerfile
FROM php:8.0-apache

# Install required PHP extensions
RUN apt-get update && apt-get install -y \
    libxml2-dev \
    libcurl4-openssl-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    xml \
    curl \
    zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Create data directory if it doesn't exist
RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html/data

# Expose port 80
EXPOSE 80

# Start Apache web server
CMD ["apache2-foreground"]
