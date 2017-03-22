#! /usr/bin/make

GOAGEN_DIR="../../vendor/github.com/goadesign/goa/goagen/"

DEPEND=	bitbucket.org/pkg/inflect \
	github.com/goadesign/goa \
	github.com/goadesign/goa/goagen \
	github.com/goadesign/goa/logging/logrus \
	github.com/Sirupsen/logrus \
	gopkg.in/yaml.v2 \
	golang.org/x/tools/cmd/goimports

all: dep clean gen build

build: gen build-only

build-only:
	@go build

dep:
	@go get $(DEPEND)

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

build: dep gen
	@go build -o beam

build-static: dep gen
	@CGO_ENABLED=0 go build -a -installsuffix cgo -o beam

docker-build:
	@docker build -t remp-beam_builder builder/
	@docker run --rm -v $$PWD/../../:/src/build remp-beam_builder > beam.tar

docker-run: docker-build
	@docker build -t remp-beam .

install: dep gen
	@go install

lint:
	@set -e; \
	for package in $$(go list ./... | grep -vE '(/app|/design|/swagger|/client|/tool|/test)'); \
	do golint -set_exit_status=true $$package; done

vet:
	@go vet $$(go list ./... | grep -vE '(/app|/design|/swagger|/client|/tool|/test)')