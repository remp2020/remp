#! /usr/bin/make
#
# Makefile for REMP beam
#
# Targets:
# - clean     delete all generated files
# - gen  (re)generate all goagen-generated files.
# - build     compile executable
#
# Meta targets:
# - all is the default target, it runs all the targets in the order above.
#

GOAGEN_DIR="../../vendor/github.com/goadesign/goa/goagen/"

DEPEND=	bitbucket.org/pkg/inflect \
	github.com/goadesign/goa \
	github.com/goadesign/goa/goagen \
	github.com/goadesign/goa/logging/logrus \
	github.com/Sirupsen/logrus \
	gopkg.in/yaml.v2 \
	golang.org/x/tools/cmd/goimports

CURRENT_DIR := $(shell dirname $(realpath $(lastword $(MAKEFILE_LIST))))

all: dep clean gen build

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
	@$(GOAGEN_DIR)/goagen app     -d gitlab.com/remp/remp/Beam/go/cmd/tracker/design
	@$(GOAGEN_DIR)/goagen swagger -d gitlab.com/remp/remp/Beam/go/cmd/tracker/design -o public
	@rm $(GOAGEN_DIR)/goagen

build:
	@go build -o beam