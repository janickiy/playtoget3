FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    git unzip freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev libxml2-dev libzip-dev icu-dev oniguruma-dev \
    libpq-dev libmemcached-dev zlib-dev linux-headers \
    nodejs npm $PHPIZE_DEPS

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql pdo_pgsql intl zip bcmath gd mbstring

# Установка Redis, Memcached, Xdebug и Swoole
RUN pecl install redis memcached xdebug \
    && docker-php-ext-enable redis memcached xdebug

RUN EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')" \
    && php -r 'copy("https://getcomposer.org/installer", "composer-setup.php");' \
    && ACTUAL_CHECKSUM="$(php -r 'echo hash_file("sha384", "composer-setup.php");')" \
    && test "$EXPECTED_CHECKSUM" = "$ACTUAL_CHECKSUM" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer --quiet \
    && rm composer-setup.php

WORKDIR /var/www/html

# Копируем проект
COPY . .

# Права доступа для Laravel Advanced
RUN chmod -R 777 storage bootstrap/cache

CMD ["php-fpm"]
