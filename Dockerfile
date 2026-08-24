FROM php:8.1-cli

# Install PDO MySQL extension for TiDB
RUN docker-php-ext-install pdo pdo_mysql

# Set working directory inside container
WORKDIR /var/www/html

# Copy all project files
COPY . .

# Expose Railway container port
EXPOSE 8080

# Run lightweight PHP Built-in Server
CMD ["php", "-S", "0.0.0.0:8080"]