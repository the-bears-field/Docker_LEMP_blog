FROM php:7.3-fpm
COPY php.ini /usr/local/etc/php/
WORKDIR /var/www/html
RUN apt-get update && \
    apt-get install -y vim && \
    apt-get install -y zip unzip && \
    docker-php-ext-install pdo_mysql