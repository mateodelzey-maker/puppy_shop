FROM php:8.2-apache

# Install PostgreSQL client library and PHP PDO extension
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy project files into Apache document root
COPY . /var/www/html/

EXPOSE 80