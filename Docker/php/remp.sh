#!/bin/bash

cd ${APP_NAME}

if [ ! -f ".env" ]
then
    cp .env.example .env
    composer install
    yarn install --no-bin-links
    npm rebuild node-sass optipng-bin --no-bin-links
    chmod -R u+x node_modules
    yarn run dev
    if [ -f "artisan" ]
    then
        php artisan migrate
        php artisan db:seed
        php artisan key:generate
        php artisan ide-helper:models
    elif [ -f "bin/command.php" ]
    then
        php bin/command.php db:migrate
        php bin/command.php db:seed
        php bin/command.php demo:seed
    fi
fi
php-fpm

