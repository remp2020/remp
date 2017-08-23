#!/bin/bash
cd ${APP_NAME}
if [ ! -f ".env" ]
then
    cp .env.example .env
    composer install
    yarn install
    yarn run dev
fi
php-fpm

