#! /usr/bin/make

PHP_FOLDERS=app config

js-link-remp:
	cd ../Package/remp && yarn link
	yarn link "@remp/js-commons"

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
	../phpcs --standard=PSR2 ${PHP_FOLDERS} -n -p

sniff_fix:
	../phpcbf --standard=PSR2 ${PHP_FOLDERS} -n

fixcs:
	php vendor/bin/php-cs-fixer fix --config=.php_cs -v

syntax:
	find ${PHP_FOLDERS} -name "*.php" -print0 | xargs -0 -n1 -P8 php -l