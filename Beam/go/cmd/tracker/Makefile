#! /usr/bin/make

DESIGN_PKG="beam/cmd/tracker/design"

.PHONY: all test build gen

TARGET_GOOS?=linux
TARGET_GOARCH?=amd64
TARGET_SUFFIX?=
export TARGET_GOOS TARGET_GOARCH TARGET_SUFFIX

all: clean build

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
	go install goa.design/goa/v3/cmd/goa@v3
	goa gen $(DESIGN_PKG) --output .
	go install github.com/go-bindata/go-bindata/go-bindata
	go-bindata -pkg=data -o=./data/bindata.go -ignore=bindata.go data/

build: gen
	go build -o tracker

build-static: gen
	CGO_ENABLED=0 GOOS=$(TARGET_GOOS) GOARCH=$(TARGET_GOARCH) go build -a -installsuffix cgo -o tracker$(TARGET_SUFFIX)

docker-build:
	set -e; \
	docker build -t remp-beam_tracker_builder builder; \
	docker run -e TARGET_GOOS -e TARGET_GOARCH -e TARGET_SUFFIX --rm -v $$PWD/../../:/src/build remp-beam_tracker_builder > tracker.tar;

docker-run: docker-build
	docker build -t remp-beam-tracker .
	
install: gen
	go install

lint:
	set -e; \
	for package in $$(go list ./... | grep -vE '(/app|/design|/swagger|/client|/tool|/test|/data)'); \
	do golint -set_exit_status=true $$package; done

vet:
	go vet $$(go list ./... | grep -vE '(/app|/design|/swagger|/client|/tool|/test|/data)')