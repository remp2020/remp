#! /usr/bin/make

SUB_TRACKER="go/cmd/tracker"
SUB_SEGMENTS="go/cmd/segments"
PHP_FOLDERS=app bootstrap config

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
	@go vet $$(go list ./... | grep -vE '(/vendor|/cmd)')

vet: vet-lib
	@cd $(SUB_TRACKER) && make vet
	@cd $(SUB_SEGMENTS) && make vet

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

sniff:
	../phpcs --standard=PSR2 ${PHP_FOLDERS} -n -p

sniff_fix:
	../phpcbf --standard=PSR2 ${PHP_FOLDERS} -n

fixcs:
	php vendor/bin/php-cs-fixer fix ${PHP_FOLDERS} --fixers=short_tag,encoding,class_definition,function_declaration,elseif,eof_ending,lowercase_keywords,lowercase_constants,php_closing_tag,trailing_spaces,visibility,array_element_no_space_before_comma,array_element_white_space_after_comma,extra_empty_lines,duplicate_semicolon,self_accessor,single_blank_line_before_namespace,single_quote,unused_use,ordered_use,short_array_syntax,line_after_namespace,method_argument_space,no_trailing_whitespace_in_comment,blankline_after_open_tag,braces,indentation,multiple_use,single_line_after_imports,switch_case_semicolon_to_colon,switch_case_space

syntax:
	find ${PHP_FOLDERS} -name "*.php" -print0 | xargs -0 -n1 -P8 php -l