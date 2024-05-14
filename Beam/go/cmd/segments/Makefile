#! /usr/bin/make

DESIGN_PKG="beam/cmd/segments/design"

.PHONY: all test build gen

TARGET_GOOS?=linux
TARGET_GOARCH?=amd64
TARGET_SUFFIX?=
export TARGET_GOOS TARGET_GOARCH TARGET_SUFFIX

all: clean build

build: gen build-only

build-only:
	go build -o segments

clean:
	@rm -rf app
	@rm -rf client
	@rm -rf tool
	@rm -rf public/swagger
	@rm -f segments

gen:
	set -e
	go install goa.design/goa/v3/cmd/goa@v3
	goa gen $(DESIGN_PKG) --output .

build-static: gen
	CGO_ENABLED=0 GOOS=$(TARGET_GOOS) GOARCH=$(TARGET_GOARCH) go build -a -installsuffix cgo -o segments$(TARGET_SUFFIX)

docker-build:
	set -e; \
	docker build -t remp-beam_segments_builder builder; \
	docker run -e TARGET_GOOS -e TARGET_GOARCH -e TARGET_SUFFIX --rm -v $$PWD/../../:/src/build remp-beam_segments_builder > segments.tar;

docker-run: docker-build
	docker build -t remp-beam-segments .

install: gen
	go install

lint:
	set -e; \
	for package in $$(go list ./... | grep -vE '(/app|/design|/swagger|/client|/tool|/test)'); \
	do golint -set_exit_status=true $$package; done

vet:
	go vet $$(go list ./... | grep -vE '(/app|/design|/swagger|/client|/tool|/test)')