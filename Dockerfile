FROM php:8.1-cli

# Install PDO MySQL driver
RUN docker-php-ext-install pdo pdo_mysql

# Set working directory
WORKDIR /var/www/html

# Copy all project files
COPY . .

# Run PHP Built-in Server on port 8080
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080"]