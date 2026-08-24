FROM php:8.1-cli

# Install PDO MySQL driver for TiDB
RUN docker-php-ext-install pdo pdo_mysql

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# PHP Server using Railway's default internal PORT
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080}"]