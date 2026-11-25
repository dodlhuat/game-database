FROM php:8.5-fpm-alpine

# System-Abhängigkeiten
RUN apk add --no-cache \
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
    pdo_mysql \
    gd \
    zip \
    exif \
    pcntl \
    bcmath

# PCOV for test coverage (make coverage). Installed but switched off, so
# normal test runs and the pre-commit hook stay as fast as before; the
# production image (Dockerfile.prod) does not get it at all.
RUN apk add --no-cache --virtual .pcov-build $PHPIZE_DEPS \
 && pecl install pcov \
 && docker-php-ext-enable pcov \
 && echo "pcov.enabled=0" > /usr/local/etc/php/conf.d/zz-pcov.ini \
 && apk del .pcov-build

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend

# Entrypoint: erstellt Verzeichnisse in named volumes und setzt Permissions
COPY docker/php/php.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]
