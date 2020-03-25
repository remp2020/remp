FROM php:7.2.29-fpm

ARG DOCKER_USER
ARG DOCKER_USER_ID
ARG DOCKER_GROUP_ID

# ----------------------------------------------------------------------------------------------------------------------
# VERSIONS AND BASIC ENVIRONMENT VARIABLES
# ----------------------------------------------------------------------------------------------------------------------
# Pecl
ENV PECL_XDEBUG_VERSION=2.9.4

# Composer
ENV COMPOSER_VERSION=1.10.1 \
    COMPOSER_HOME="/composer" \
    PATH="/composer/vendor/bin:$PATH"

# NodeJS + Yarn
ENV NODESOURCE_VERSION=12.x \
    NODE_VERSION=12.16.1 \
    NODE_PKG_RELEASE=1nodesource1 \
    NPM_CONFIG_LOGLEVEL=info \
    YARN_CACHE_FOLDER="/var/cache/yarn" \
    YARN_VERSION=1.22.4 \
    YARN_PKG_RELEASE=1

# Nginx
ENV NGINX_VERSION=1.16.1 \
    NGINX_NJS_VERSION=0.3.5 \
    NGINX_PKG_RELEASE=1~buster

# Packages
ENV BUILD_DEPS="build-essential \
                libsasl2-dev \
                libssl-dev \
                libzip-dev"
ENV RUN_DEPS="ca-certificates \
              curl \
              g++ \
              gcc \
              gettext-base \
              git \
              gnupg \
              less \
              libicu-dev \
              libpng-dev \
              libjpeg-dev \
              lsb-release \
              make \
              openssh-client \
              supervisor \
              unzip \
              vim \
              wget \
              zip \
              zlib1g-dev"

# Dockerize
ENV DOCKERIZE_VERSION v0.6.1

# ----------------------------------------------------------------------------------------------------------------------
# PHP
# Initialization with PHP installation
# ----------------------------------------------------------------------------------------------------------------------
RUN apt-get update && \
    apt-get install -y \
        ${BUILD_DEPS} \
        ${RUN_DEPS} && \
    pecl install \
        xdebug-${PECL_XDEBUG_VERSION} && \
    docker-php-ext-enable \
        xdebug && \
    docker-php-ext-configure intl && \
    docker-php-ext-configure gd --with-jpeg-dir=/usr/include/ --with-png-dir=/usr/include/ && \
    docker-php-ext-install -j$(nproc) \
        bcmath \
        gd \
        iconv \
        intl \
        mbstring \
        pcntl \
        pdo_mysql \
        sockets \
        zip && \
    apt-get purge \
        -y --auto-remove \
        -o APT::AutoRemove::RecommendsImportant=false \
        ${BUILD_DEPS} && \
    apt-get clean && \
    rm -r /var/lib/apt/lists/* && \
    pecl clear-cache

# ----------------------------------------------------------------------------------------------------------------------
# PHP CONFIGURATION
# ----------------------------------------------------------------------------------------------------------------------
RUN mv /usr/local/etc/php-fpm.d/docker.conf /usr/local/etc/php-fpm.d/docker.conf.default && \
    mv /usr/local/etc/php-fpm.d/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf.default

# ----------------------------------------------------------------------------------------------------------------------
# COMPOSER
# ----------------------------------------------------------------------------------------------------------------------
RUN curl -sS https://getcomposer.org/installer | \
    php -- \
        --install-dir=/usr/local/bin \
        --filename=composer \
        --version=${COMPOSER_VERSION}

# ----------------------------------------------------------------------------------------------------------------------
# NODEJS + YARN
# ----------------------------------------------------------------------------------------------------------------------
RUN curl -sL https://deb.nodesource.com/setup_${NODESOURCE_VERSION} | bash - && \
    curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - && \
    echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list && \
    apt-get update && \
    apt-get install -y \
        nodejs=${NODE_VERSION}-${NODE_PKG_RELEASE} \
        yarn=${YARN_VERSION}-${YARN_PKG_RELEASE} && \
    apt-get clean && \
    rm -r /var/lib/apt/lists/*

# ----------------------------------------------------------------------------------------------------------------------
# DOCKERIZE
# ----------------------------------------------------------------------------------------------------------------------
RUN wget --quiet https://github.com/jwilder/dockerize/releases/download/${DOCKERIZE_VERSION}/dockerize-linux-amd64-${DOCKERIZE_VERSION}.tar.gz && \
    tar -C /usr/local/bin -xzvf dockerize-linux-amd64-${DOCKERIZE_VERSION}.tar.gz && \
    rm dockerize-linux-amd64-${DOCKERIZE_VERSION}.tar.gz

# ----------------------------------------------------------------------------------------------------------------------
# USER
# ----------------------------------------------------------------------------------------------------------------------
RUN addgroup \
        --gid 1000 \
        user && \
    useradd \
        --uid 1000 \
        --gid user \
        --home-dir /home/user \
        --create-home \
        --shell /bin/bash \
        user && \
    sed -i 's/^#alias l/alias l/g' /home/user/.bashrc && \
    mkdir -p \
        /run/secrets \
        /var/run/php \
        ${YARN_CACHE_FOLDER} \
        ${COMPOSER_HOME}/cache && \
    chown user:user -R \
        /run/secrets \
        /var/run/php \
        ${YARN_CACHE_FOLDER} \
        ${COMPOSER_HOME}/cache \
        /var/www/html

# ----------------------------------------------------------------------------------------------------------------------
# RUN
# Run setup, variables and dockerize start
# ----------------------------------------------------------------------------------------------------------------------
COPY ./php/config/usr /usr

COPY bin/fix-user .
COPY --chown=user:user php/remp.sh /remp.sh
RUN ./fix-user ${DOCKER_USER} user ${DOCKER_USER_ID} ${DOCKER_GROUP_ID}

ENV PHP_FPM_LISTEN_PORT=9000 \
    PHP_LOG_LEVEL=notice \
    PHP_MAX_EXECUTION_TIME=30 \
    PHP_MEMORY_LIMIT=256M \
    PHP_ERROR_REPORTING="E_ALL & ~E_DEPRECATED & ~E_STRICT" \
    PHP_DISPLAY_ERRORS=1 \
    PHP_DISPLAY_STARTUP_ERRORS=1 \
    PHP_POST_MAX_SIZE=20M \
    PHP_UPLOAD_MAX_FILESIZE=20M \
    PHP_SESSION_SAVE_HANDLER=files \
    PHP_SESSION_SAVE_PATH="/tmp" \
    PHP_OPCACHE_ENABLE=0 \
    PHP_OPCACHE_MEMORY_CONSUMPTION=256 \
    PHP_OPCACHE_INTERNED_STRINGS_BUFFER=32 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=32531 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_OPCACHE_LOG_VERBOSITY_LEVEL=1 \
    PHP_XDEBUG_REMOTE_ENABLE=1 \
    PHP_XDEBUG_IDEKEY="PHPSTORM" \
    PHP_XDEBUG_REMOTE_AUTOSTART=1 \
    PHP_XDEBUG_REMOTE_PORT="9001" \
    PHP_XDEBUG_REMOTE_HOST="172.17.0.1"

USER user

CMD ["dockerize", "-timeout", "1m", "-wait-retry-interval", "10s", "-wait", "tcp://mysql:3306", "-wait", "tcp://redis:6379", "/remp.sh"]
