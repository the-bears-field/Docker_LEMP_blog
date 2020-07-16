FROM php:7.3-fpm
COPY php.ini /usr/local/etc/php/
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN apt-get update && \
    apt-get install -y vim && \
    apt-get install -y zip unzip && \
    composer update && \
    docker-php-ext-install pdo_mysql