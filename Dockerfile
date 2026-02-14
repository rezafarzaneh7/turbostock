FROM php:8.2-fpm

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libgmp-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo_mysql mysqli zip gmp curl mbstring \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html
