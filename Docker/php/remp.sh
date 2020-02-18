#!/bin/bash

cd "/var/www/html/${APP_NAME}" || exit 1

function setup_env() {
    # Get all parameters from .env.dist
    ALL_APP_ENV=$(cat .env.dist | grep -E -v "^#|APP_KEY|JWT_SECRET" | sed -e '/^$/d' | sed -e 's/\(.*\)=$.*/\1/g')
    # create .env with values from main .env.docker file
    # comment out empty values
    # uncomment nonempty values
    envsubst <.env.dist >.env
    for env_name in ${ALL_APP_ENV}; do
        envtmp='echo ${'$env_name'}'
        env_value=$(eval $envtmp)

        if [ -z "$env_value" ]; then
            sed -i 's|^'$env_name'=|#'$env_name'=|' .env
        fi
    done
}

if [ -f ".env" ]; then
    setup_env
    if [ -f "artisan" ]; then
        php artisan key:generate

        php artisan list | grep "jwt:secret" >/dev/null
        if [ $? -eq "0" ]; then
            php artisan jwt:secret
        fi
    fi
fi

if [ ! -f ".env" ]; then
    setup_env

    composer install

    yarn install --no-bin-links
    chmod -R u+x node_modules

    yarn link --cwd ../Package/remp
    yarn link remp

    npm run | grep "all-dev"
    if [ $? -eq "0" ]; then
        yarn run all-dev
    else
        yarn run dev
    fi

    if [ -f "artisan" ]; then
        php artisan migrate:fresh
        php artisan db:seed
        php artisan key:generate
        php artisan ide-helper:generate
        php artisan ide-helper:meta
        php artisan ide-helper:models

        php artisan list | grep "jwt:secret" >/dev/null
        if [ $? -eq "0" ]; then
            php artisan jwt:secret
        fi

        php artisan list | grep "search:init" > /dev/null
        if [ $? -eq "0" ]; then
            php artisan search:init
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

/usr/local/sbin/php-fpm --nodaemonize --fpm-config /usr/local/etc/php-fpm.conf
