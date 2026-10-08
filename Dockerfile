FROM php:8.2-apache

# Install PostgreSQL client library and PHP PDO extensions
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Suppress Apache ServerName warning and explicitly bind to 0.0.0.0:80
RUN echo "ServerName 0.0.0.0" >> /etc/apache2/apache2.conf \
    && echo "Listen 0.0.0.0:80" > /etc/apache2/ports.conf

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy project files to Apache web root
COPY . /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]