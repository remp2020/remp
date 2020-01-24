FROM php:7.2.26-fpm

ARG DOCKER_USER
ARG DOCKER_USER_ID
ARG DOCKER_GROUP_ID

# Composer
ENV COMPOSER_VERSION=1.9.2
ENV COMPOSER_HOME="/composer"
ENV PATH="/composer/vendor/bin:$PATH"

# NodeJS + Yarn
ENV NODE_VERSION=10.18.1
ENV NODE_PKG_RELEASE=1nodesource1
ENV NPM_CONFIG_LOGLEVEL=info
ENV YARN_CACHE_FOLDER="/var/cache/yarn"
ENV YARN_VERSION=1.21.1
ENV YARN_PKG_RELEASE=1

# Nginx
ENV NGINX_VERSION=1.16.1
ENV NGINX_NJS_VERSION=0.3.5
ENV NGINX_PKG_RELEASE=1~buster

# Packages
ENV BUILD_DEPS="libzip-dev \
                gcc \
                make \
                build-essential \
                libsasl2-dev \
                libssl-dev"
ENV RUN_DEPS="libicu-dev \
              g++ \
              git \
              wget \
              vim \
              curl \
              less \
              gnupg \
              zlib1g-dev \
              libpng-dev \
              libjpeg-dev \
              gettext-base \
              unzip \
              zip"

# Dockerize
ENV DOCKERIZE_VERSION v0.6.1

# Init+Php (single command so the intermediates are not stored)

RUN apt update && \
    apt install -y \
        ${BUILD_DEPS} \
        ${RUN_DEPS} && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug && \
    docker-php-ext-configure intl && \
    docker-php-ext-configure gd --with-jpeg-dir=/usr/include/ --with-png-dir=/usr/include/ && \
    docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        bcmath \
        mbstring \
        iconv \
        zip \
        intl \
        sockets \
        pcntl \
        gd && \
    apt purge \
        -y --auto-remove \
        -o APT::AutoRemove::RecommendsImportant=false \
        ${BUILD_DEPS} && \
    apt clean

# Php

ADD php/xdebug.ini /tmp/xdebug.ini

RUN cat /tmp/xdebug.ini >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

ADD php/www.conf /usr/local/etc/php-fpm.d/www.conf
ADD php/log.conf /usr/local/etc/php-fpm.d/zz-log.conf

# Composer

RUN curl -sS https://getcomposer.org/installer | \
    php -- \
        --install-dir=/usr/local/bin \
        --filename=composer \
        --version=${COMPOSER_VERSION}

# NodeJS + Yarn

RUN rm -rf /var/lib/apt/lists/* && \
    curl -sL https://deb.nodesource.com/setup_10.x | bash - && \
    curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - && \
    echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list && \
    apt update && \
    apt install -y \
        nodejs=${NODE_VERSION}-${NODE_PKG_RELEASE} \
        yarn=${YARN_VERSION}-${YARN_PKG_RELEASE} && \
    apt clean && \
    rm -r /var/lib/apt/lists/*

# User

RUN useradd -d /home/user -u 1000 -m -s /bin/bash user && \
    mkdir -p \
        ${YARN_CACHE_FOLDER} \
        ${COMPOSER_HOME}/cache && \
    chown user:user \
        /var/www/html \
        ${YARN_CACHE_FOLDER} \
        ${COMPOSER_HOME}/cache

ADD bin/fix-user .

RUN ./fix-user ${DOCKER_USER} user ${DOCKER_USER_ID} ${DOCKER_GROUP_ID}

# Dockerize run

RUN wget --quiet https://github.com/jwilder/dockerize/releases/download/${DOCKERIZE_VERSION}/dockerize-linux-amd64-${DOCKERIZE_VERSION}.tar.gz && \
    tar -C /usr/local/bin -xzvf dockerize-linux-amd64-${DOCKERIZE_VERSION}.tar.gz && \
    rm dockerize-linux-amd64-${DOCKERIZE_VERSION}.tar.gz

USER user

ADD php/remp.sh /remp.sh

CMD ["dockerize", "-timeout", "1m", "-wait-retry-interval", "10s", "-wait", "tcp://mysql:3306", "-wait", "tcp://redis:6379", "/remp.sh"]
