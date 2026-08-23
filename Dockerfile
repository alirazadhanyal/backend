FROM php:8.1-cli

# Install PDO MySQL driver for TiDB connection
RUN docker-php-ext-install pdo pdo_mysql

# Set working directory
WORKDIR /var/www/html

# Copy all project files
COPY . .

# Start PHP built-in web server with dynamic PORT binding
CMD php -S 0.0.0.0:${PORT:-8080}