FROM php:8.2-apache

# Install system deps
RUN apt-get update && apt-get install -y \
    git unzip curl \
    && docker-php-ext-install pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files FIRST (cache optimization)
COPY composer.json ./

# Install dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Copy rest of app
COPY . .

# Fix permissions
RUN chown -R www-data:www-data /var/www/html