FROM php:8.1-apache

# Install PDO MySQL extension for TiDB
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy all project files into Apache web root
COPY . /var/www/html/

# Expose standard web port
EXPOSE 80