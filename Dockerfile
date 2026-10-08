FROM php:8.2-apache

# Install PostgreSQL client library and PHP PDO extensions
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy repository files to Apache web root
COPY . /var/www/html/

EXPOSE 80