# ────────────────────────────────────────────────────────────
# Stage 1 – install Composer dependencies (build cache friendly)
# ────────────────────────────────────────────────────────────
FROM composer:2 AS vendor

#WORKDIR /app

#COPY composer.json composer.lock* ./
# Install system dependencies (if needed)
#RUN apt-get update && apt-get install -y \
#    libzip-dev \
 #   unzip \
  #  && docker-php-ext-install pdo pdo_mysql

# Install Composer dependencies
WORKDIR /app

COPY composer.json composer.lock* ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader
# ────────────────────────────────────────────────────────────
# Stage 2 – runtime image
# ────────────────────────────────────────────────────────────
FROM php:8.2-apache

# System deps + PHP extensions
#RUN apt-get update && apt-get install -y --no-install-recommends \
        #libzip-dev \
        #unzip \
        #curl \
    #&& docker-php-ext-install pdo pdo_mysql zip \
    #&& apt-get clean && rm -rf /var/lib/apt/lists/*
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip
# Apache mod_rewrite
RUN a2enmod rewrite

# ---- Recommended PHP ini tweaks for production ----
RUN { \
        echo 'expose_php = Off'; \
        echo 'display_errors = Off'; \
        echo 'log_errors = On'; \
        echo 'error_log = /dev/stderr'; \
        echo 'memory_limit = 256M'; \
    } > /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html

# Copy vendor from build stage (avoids installing Composer in runtime image)
COPY --from=vendor /app/vendor ./vendor

# Copy application source
COPY . .

# Drop the .git folder and any local override files
RUN rm -rf .git otel_project Terraform .github

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

# Health-check so ECS knows when the container is ready
HEALTHCHECK --interval=10s --timeout=3s --retries=3 \
    CMD curl -f http://localhost/health.php || exit 1

CMD ["apache2-foreground"]
