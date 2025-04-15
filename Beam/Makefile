#! /usr/bin/make

SUB_TRACKER="go/cmd/tracker"
SUB_SEGMENTS="go/cmd/segments"
PHP_FOLDERS=app config extensions/beam-module/config extensions/beam-module/src

export TARGET_GOOS TARGET_GOARCH TARGET_SUFFIX

clear:
	@cd $(SUB_TRACKER) && make clear
	@cd $(SUB_SEGMENTS) && make clear

lint-lib:
	@set -e; \
	for package in $$(go list ./... | grep -vE '(/vendor|/cmd)'); \
	do golint -set_exit_status=true $$package; done

lint: lint-lib
	@cd $(SUB_TRACKER) && make lint
	@cd $(SUB_SEGMENTS) && make lint

vet-lib:
	@go vet $$(go list go/... | grep -vE '(/vendor|/cmd)')

vet: vet-lib
	@cd $(SUB_TRACKER) && make vet
	@cd $(SUB_SEGMENTS) && make vet

js-link-remp:
	yarn link "../Package/remp" --relative

js:
	make js-link-remp
	yarn install
	yarn all-production

js-dev:
	make js-link-remp
	yarn install
	yarn all-dev

js-watch:
	make js-link-remp
	yarn install
	yarn all-watch

build:
	@cd $(SUB_TRACKER) && make build
	@cd $(SUB_SEGMENTS) && make build

build-only:
	@cd $(SUB_TRACKER) && make build-only
	@cd $(SUB_SEGMENTS) && make build-only

build-simple: clear gen-only build-only

pr: clear build lint vet test-only

docker-build:
	@cd $(SUB_TRACKER) && make docker-build
	@cd $(SUB_SEGMENTS) && make docker-build

phpstan:
	php vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=1G

sniff:
	php vendor/bin/phpcs --standard=PSR2 ${PHP_FOLDERS} -n -p

sniff_fix:
	php vendor/bin/phpcbf --standard=PSR2 ${PHP_FOLDERS} -n

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
	php artisan schema:dump --path=extensions/beam-module/database/schema/mysql-schema.sql