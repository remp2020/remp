#! /usr/bin/make

PHP_FOLDERS=app config extensions/campaign-module/config extensions/campaign-module/src

js-link-remp:
	yarn link "../Package/remp" --relative

js:
	make js-link-remp
	yarn install
	yarn production

js-dev:
	make js-link-remp
	yarn install
	yarn dev

js-watch:
	make js-link-remp
	yarn install
	yarn watch

phpstan:
	php vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=1G

sniff:
	php vendor/bin/phpcs --standard=PSR2 ${PHP_FOLDERS} -n -p

sniff_fix:
	php vendor/bin/phpcbf --standard=PSR2 ${PHP_FOLDERS} -n

test:
	php -d memory_limit=256M vendor/bin/phpunit --configuration=phpunit.xml

fixcs:
	php vendor/bin/php-cs-fixer fix --config=.php_cs -v

syntax:
	find ${PHP_FOLDERS} -name "*.php" -print0 | xargs -0 -n1 -P8 php -l

composer-audit:
	composer audit --locked --abandoned=report

composer-install-dev:
	composer install

composer-install:
	composer install --optimize-autoloader --no-dev

update-dev: composer-install-dev js-dev
	php artisan migrate

update-prod: composer-install js
	php artisan migrate --force
	php artisan config:cache
	php artisan route:cache

schema-dump:
	php artisan schema:dump --path=extensions/campaign-module/database/schema/mysql-schema.sql