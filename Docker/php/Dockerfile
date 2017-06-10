FROM php:7.1-fpm

RUN apt-get update && apt-get install -y git libzip-dev \
    && docker-php-ext-install -j$(nproc) pdo_mysql bcmath mbstring iconv zip

RUN pecl install xdebug && docker-php-ext-enable xdebug

ENV XDEBUGINI_PATH=/usr/local/etc/php/conf.d/xdebug.ini
RUN echo "zend_extension="`find /usr/local/lib/php/extensions/ -iname 'xdebug.so'` > $XDEBUGINI_PATH
COPY xdebug.ini /tmp/xdebug.ini
RUN cat /tmp/xdebug.ini >> $XDEBUGINI_PATH
#RUN echo "xdebug.remote_host="`/sbin/ip route|awk '/default/ { print $3 }'` >> $XDEBUGINI_PATH

ADD log.conf /usr/local/etc/php-fpm.d/zz-log.conf

ENV COMPOSER_HOME /composer

ENV PATH /composer/vendor/bin:$PATH

ENV COMPOSER_ALLOW_SUPERUSER 1

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer global require "fxp/composer-asset-plugin:^1.3"

VOLUME /var/www/html

RUN chmod 777 /var/www/html
