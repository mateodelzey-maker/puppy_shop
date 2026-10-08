FROM php:8.2-apache

# Install PostgreSQL driver extension for PHP
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Enable Apache mod_rewrite for route handling
RUN a2enmod rewrite

# Copy project files to Apache root
COPY . /var/www/html/

# Expose HTTP port
EXPOSE 80
