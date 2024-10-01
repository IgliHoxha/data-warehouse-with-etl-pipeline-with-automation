# Dockerfile

# Use the latest PHP 8.x image
FROM php:8.2-cli

# Install necessary dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install zip pdo pdo_mysql

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug
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

RUN echo "memory_limit = ${PHP_MEMORY_LIMIT:-512M}" > /usr/local/etc/php/conf.d/memory-limit.ini
RUN echo "xdebug.mode=coverage" > /usr/local/etc/php/conf.d/xdebug.ini

# Set default command
CMD ["tail", "-f", "/dev/null"]