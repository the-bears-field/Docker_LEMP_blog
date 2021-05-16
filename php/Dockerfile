FROM php:7.3-fpm
COPY php.ini /usr/local/etc/php/
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
RUN apt-get update && \
    apt-get install -y gnupg vim zip unzip && \
    curl -sL https://deb.nodesource.com/setup_12.x | bash - && \
    apt-get install -y nodejs && \
    docker-php-ext-install pdo_mysql
