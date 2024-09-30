# Dockerfile

# Use the latest PHP 8.x image
FROM php:8.2-cli

# Install necessary dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install zip pdo pdo_mysql

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy Symfony project files
COPY . .

# Install project dependencies
RUN composer install

# Add Symfony binary to the PATH
RUN curl -sS https://get.symfony.com/cli/installer | bash && \
    mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Set default command
CMD ["tail", "-f", "/dev/null"]