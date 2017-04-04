#! /usr/bin/make

GOAGEN_DIR="../../vendor/github.com/goadesign/goa/goagen"
DESIGN_PKG="gitlab.com/remp/remp/Beam/go/cmd/tracker/design"

all: clean gen build

build: gen build-only

build-only:
	go build

clean:
	@rm -rf app
	@rm -rf client
	@rm -rf tool
	@rm -rf public/swagger
	@rm -f tracker

gen:
	set -e
	cd $(GOAGEN_DIR) && go build
	$(GOAGEN_DIR)/goagen app -d $(DESIGN_PKG)
	$(GOAGEN_DIR)/goagen swagger -d $(DESIGN_PKG)
	rm $(GOAGEN_DIR)/goagen

build: gen
	go build -o tracker

build-static: gen
	CGO_ENABLED=0 go build -a -installsuffix cgo -o tracker

docker-build:
	set -e; \
    docker build -t remp-beam_tracker_builder builder; \
    docker run --rm -v $$PWD/../../:/src/build remp-beam_tracker_builder > tracker.tar;

docker-run: docker-build
	docker build -t remp-beam-tracker .

install: gen
	go install

lint:
	set -e; \
	for package in $$(go list ./... | grep -vE '(/app|/design|/swagger|/client|/tool|/test)'); \
	do golint -set_exit_status=true $$package; done

vet:
	go vet $$(go list ./... | grep -vE '(/app|/design|/swagger|/client|/tool|/test)')