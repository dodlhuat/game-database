FROM php:8.3-fpm-alpine

# System-Abhängigkeiten
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libwebp-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl

# PHP Extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    gd \
    zip \
    exif \
    pcntl \
    bcmath

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend

# Berechtigungen für Laravel Storage
RUN chown -R www-data:www-data /var/www

EXPOSE 9000
CMD ["php-fpm"]
