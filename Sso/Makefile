#! /usr/bin/make

PHP_FOLDERS=app config

sniff:
	../phpcs --standard=PSR2 ${PHP_FOLDERS} -n -p

sniff_fix:
	../phpcbf --standard=PSR2 ${PHP_FOLDERS} -n

fixcs:
	php vendor/bin/php-cs-fixer fix --config=.php_cs -v

syntax:
	find ${PHP_FOLDERS} -name "*.php" -print0 | xargs -0 -n1 -P8 php -l