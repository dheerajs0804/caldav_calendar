FROM roundcube/roundcubemail:1.6.7-apache

# Install additional dependencies if needed
RUN apt-get update && apt-get install -y \
    php-gd \
    php-mbstring \
    php-xml \
    php-zip \
    && rm -rf /var/lib/apt/lists/*

# Copy custom configuration
COPY roundcube/config/config.inc.php /var/www/html/config/config.inc.php

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
