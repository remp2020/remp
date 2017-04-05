FROM php:7.1-fpm

RUN apt-get update && apt-get install -y git libzip-dev \
    && docker-php-ext-install -j$(nproc) pdo_mysql bcmath mbstring iconv zip

ENV COMPOSER_HOME /composer

ENV PATH /composer/vendor/bin:$PATH

ENV COMPOSER_ALLOW_SUPERUSER 1

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

VOLUME /var/www/html

RUN chmod 777 /var/www/html
