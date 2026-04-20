FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git unzip curl \
    && docker-php-ext-install pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 🔥 IMPORTANT FIX: enable rewrite + proper apache behavior
RUN a2enmod rewrite

WORKDIR /var/www/html

COPY composer.json ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader || true

COPY . .

# 🔥 FIX: ensure apache serves correct folder
RUN chown -R www-data:www-data /var/www/html