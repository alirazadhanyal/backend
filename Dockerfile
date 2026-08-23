FROM php:8.2-apache

# Enable PDO and MySQL Extensions for TiDB/MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Copy project files into Apache root
COPY . /var/www/html/

# Expose Port 80
EXPOSE 80