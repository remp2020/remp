#! /usr/bin/make

PHP_FOLDERS=app config

sniff:
	../phpcs --standard=PSR2 ${PHP_FOLDERS} -n -p

sniff_fix:
	../phpcbf --standard=PSR2 ${PHP_FOLDERS} -n

test:
	php -d memory_limit=256M vendor/bin/phpunit --configuration=phpunit.xml

fixcs:
	php vendor/bin/php-cs-fixer fix --config=.php_cs -v

syntax:
	find ${PHP_FOLDERS} -name "*.php" -print0 | xargs -0 -n1 -P8 php -l