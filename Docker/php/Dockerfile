FROM php:7.0-fpm

RUN apt-get update && apt-get install -y git libzip-dev zlib1g-dev libicu-dev g++ \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) pdo_mysql bcmath mbstring iconv zip intl

RUN pecl install xdebug && docker-php-ext-enable xdebug

ADD xdebug.ini /tmp/xdebug.ini

RUN cat /tmp/xdebug.ini >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

ADD log.conf /usr/local/etc/php-fpm.d/zz-log.conf

ENV COMPOSER_HOME /composer

ENV PATH /composer/vendor/bin:$PATH

ENV COMPOSER_ALLOW_SUPERUSER 1

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer global require "fxp/composer-asset-plugin:^1.3"

VOLUME /var/www/html

RUN chmod 777 /var/www/html
