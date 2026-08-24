FROM php:8.1-cli

# Install PDO MySQL driver for TiDB
RUN docker-php-ext-install pdo pdo_mysql

# Set working directory
WORKDIR /var/www/html

# Copy all repository files into container
COPY . .

# Expose port 8080
EXPOSE 8080

# Run PHP built-in server pointing to container root
CMD ["php", "-S", "0.0.0.0:8080"]