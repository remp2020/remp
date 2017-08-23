FROM php:7.0-fpm

ENV LIBRDKAFKA_VERSION v0.9.5

ENV BUILD_DEPS libzip-dev zlib1g-dev g++ build-essential libsasl2-dev libssl-dev python-minimal

ENV RUN_DEPS libicu-dev git

# INIT (single command so the intermediates are not stored)
# librdkafka is built manually because packages contain only v0.8.5

RUN apt-get update && apt-get install -y ${BUILD_DEPS} ${RUN_DEPS} \
    && cd /tmp \
    && git clone \
        --branch ${LIBRDKAFKA_VERSION} \
        --depth 1 \
        https://github.com/edenhill/librdkafka.git \
    && cd librdkafka \
    && ./configure \
    && make \
    && make install \
    && pecl install rdkafka && docker-php-ext-enable rdkafka && rm -rf /tmp/librdkafka && cd /var/www/html \
    && pecl install xdebug && docker-php-ext-enable xdebug \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) pdo_mysql bcmath mbstring iconv zip intl \
    && apt-get purge \
        -y --auto-remove \
        -o APT::AutoRemove::RecommendsImportant=false \
        ${BUILD_DEPS}

# PHP

ADD xdebug.ini /tmp/xdebug.ini

RUN cat /tmp/xdebug.ini >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

ADD log.conf /usr/local/etc/php-fpm.d/zz-log.conf

# COMPOSER

ENV COMPOSER_HOME /composer

ENV PATH /composer/vendor/bin:$PATH

ENV COMPOSER_ALLOW_SUPERUSER 1

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# NODEJS + YARN

ENV PATH=$HOME/.yarn/bin:$PATH

ENV YARN_CACHE_FOLDER /yarn

RUN curl -sL https://deb.nodesource.com/setup_6.x | bash -

RUN apt-get install -y nodejs

RUN curl -sL https://yarnpkg.com/install.sh | bash -

# VOLUME

VOLUME /var/www/html

RUN chmod 777 /var/www/html

ADD remp.sh /root/remp.sh

RUN chmod +x /root/remp.sh

CMD "/root/remp.sh"
