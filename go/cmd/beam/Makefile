#! /usr/bin/make

GOAGEN_DIR="../../vendor/github.com/goadesign/goa/goagen/"

all: clean gen build

build: gen build-only

build-only:
	@go build

clean:
	@rm -rf app
	@rm -rf client
	@rm -rf tool
	@rm -rf public/swagger
	@rm -f beam

gen:
	@cd $(GOAGEN_DIR) && go build
	@$(GOAGEN_DIR)/goagen app     -d gitlab.com/remp/remp/go/cmd/beam/design
	@$(GOAGEN_DIR)/goagen swagger -d gitlab.com/remp/remp/go/cmd/beam/design -o public
	@rm $(GOAGEN_DIR)/goagen

build:
	@go build -o beam

lint:
	@set -e; \
	for package in $$(go list ./... | grep -vE '(/app|/design|/swagger|/client|/tool|/test)'); \
	do golint -set_exit_status=true $$package; done

vet:
	@go vet $$(go list ./... | grep -vE '(/app|/design|/swagger|/client|/tool|/test)')