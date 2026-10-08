FROM php:8.2-apache

# Install PostgreSQL client library and PHP PDO extensions
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Suppress Apache ServerName warning
RUN echo "ServerName 0.0.0.0" >> /etc/apache2/apache2.conf

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy project files to Apache web root
COPY . /var/www/html/

# Shell script to dynamically set Apache port to Render's $PORT env variable
CMD sed -i "s/80/${PORT:-80}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf && apache2-foreground