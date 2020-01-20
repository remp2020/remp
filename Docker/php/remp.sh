#!/bin/bash

cd ${APP_NAME}

if [ ! -f ".env" ]; then
    # create .env with default values
    envsubst <.env.dist >.env

    composer install

    yarn install --no-bin-links
    chmod -R u+x node_modules

    npm run | grep "all-dev"
    if [ $? -eq "0" ]; then
        yarn run all-dev
    else
        yarn run dev
    fi

    if [ -f "artisan" ]; then
        php artisan migrate
        php artisan db:seed
        php artisan key:generate
        php artisan ide-helper:generate
        php artisan ide-helper:meta
        php artisan ide-helper:models

        php artisan list | grep "jwt:secret" >/dev/null
        if [ $? -eq "0" ]; then
            php artisan jwt:secret
        fi

        # Update permissions for Laravel storage (cache) folder
        find storage -type f -exec chmod 664 {} \;
        find storage -type d -exec chmod 775 {} \;
    elif [ -f "bin/command.php" ]; then
        cp app/config/config.local.neon.example app/config/config.local.neon
        php bin/command.php migrate:migrate
        php bin/command.php db:seed
        php bin/command.php demo:seed

        # Update permissions for Nette temp (cache) & log folder
        find temp -type f -exec chmod 664 {} \;
        find temp -type d -exec chmod 775 {} \;
        find log -type f -exec chmod 664 {} \;
        find log -type d -exec chmod 775 {} \;
    fi
fi

php-fpm
