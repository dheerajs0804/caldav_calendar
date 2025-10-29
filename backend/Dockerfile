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

# Create sessions directory for PHP session persistence
RUN mkdir -p /var/www/html/sessions \
    /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/sessions \
    && chown -R www-data:www-data /var/www/html/logs \
    && chmod -R 775 /var/www/html/sessions \
    && chmod -R 775 /var/www/html/logs

# Configure PHP session storage
RUN echo "session.save_path = \"/var/www/html/sessions\"" >> /usr/local/etc/php/conf.d/sessions.ini \
    && echo "session.gc_probability = 1" >> /usr/local/etc/php/conf.d/sessions.ini \
    && echo "session.gc_divisor = 1000" >> /usr/local/etc/php/conf.d/sessions.ini

# Expose port 80
EXPOSE 80

# Start Apache web server
CMD ["apache2-foreground"]
