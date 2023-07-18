#! /usr/bin/make

PHP_FOLDERS=app bin extensions tests

install:
	composer install
	make js
	php bin/command.php migrate:migrate
	php bin/command.php db:seed

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

sniff:
	php vendor/bin/phpcs --standard=.phpcs_ruleset.xml ${PHP_FOLDERS} -n -p

sniff_fix:
	php vendor/bin/phpcbf --standard=.phpcs_ruleset.xml ${PHP_FOLDERS} -n

syntax:
	find ${PHP_FOLDERS} -name "*.php" -print0 | xargs -0 -n1 -P8 php -l

phpstan:
	php vendor/bin/phpstan analyse --configuration=.phpstan.neon --memory-limit=1G app tests extensions

latte-lint:
	php bin/latte-lint.php app
	php bin/latte-lint.php extensions

composer-install-dev:
	composer install

composer-install:
	composer install --optimize-autoloader --no-dev

update-dev: composer-install-dev js-dev
	php bin/command.php migrate:migrate
	php bin/command.php db:seed
	rm -rf temp/cache

update-prod: composer-install js
	php bin/command.php migrate:migrate
	php bin/command.php db:seed
	rm -rf temp/cache
