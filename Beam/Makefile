#! /usr/bin/make

SUB_BEAM="cmd/beam"

clear:
	@cd $(SUB_BEAM) && make clear

lint-lib:
	@set -e; \
	for package in $$(go list ./... | grep -vE '(/vendor|/cmd)'); \
	do golint -set_exit_status=true $$package; done

lint: lint-lib
	@cd $(SUB_BEAM) && make lint

vet-lib:
	@go vet $$(go list ./... | grep -vE '(/vendor|/cmd)')

vet: #vet-lib
	@cd $(SUB_BEAM) && make vet

build:
	@cd $(SUB_BEAM) && make build

build-only:
	@cd $(SUB_BEAM) && make build-only

build-simple: clear gen-only build-only

pr: clear build lint vet test-only

beam-docker-build:
	@cd $(SUB_BEAM) && make docker-build

docker-compose: beam-docker-build
	@set -e; \
	docker-compose down --rmi local; \
	docker-compose build --pull