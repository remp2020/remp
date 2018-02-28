#!/bin/bash

cd ${APP_NAME}

if [ ! -f ".env" ]
then
    cp .env.example .env

    composer install

    yarn install --no-bin-links
    npm rebuild node-sass optipng-bin --no-bin-links
    chmod -R u+x node_modules

    npm run | grep "all-dev"
    if [ $? -eq "0" ]; then
        yarn run all-dev
    else
        yarn run dev
    fi

    if [ -f "artisan" ]
    then
        php artisan migrate
        php artisan db:seed
        php artisan key:generate
        php artisan ide-helper:generate
        php artisan ide-helper:meta
        php artisan ide-helper:models

        php artisan list | grep "jwt:secret" > /dev/null
        if [ $? -eq "0" ]; then
            php artisan jwt:secret
        fi
    elif [ -f "bin/command.php" ]
    then
        cp app/config/config.local.neon.example app/config/config.local.neon
        php bin/command.php migrate:migrate
        php bin/command.php seed:db
        php bin/command.php seed:demo
    fi
fi
php-fpm

