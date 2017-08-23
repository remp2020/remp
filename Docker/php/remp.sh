#!/bin/bash
cd ${APP_NAME}
if [ ! -f ".env" ]
then
    cp .env.example .env
    composer install
    yarn install
    yarn run dev
    if [ -f "artisan" ]
    then
        php artisan db:seed
    elif [ -f "bin/command.php" ]
    then
        php bin/command.php application:seed
    fi
fi
php-fpm

