#! /usr/bin/make

SUB_TRACKER="go/cmd/tracker"
SUB_SEGMENTS="go/cmd/segments"

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

vet: #vet-lib
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

docker-compose: docker-build
	@set -e; \
	docker-compose down --rmi local; \
	docker-compose build --pull