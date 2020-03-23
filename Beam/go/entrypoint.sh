#!/bin/bash

envsubst <.env.dist >.env

cd ${GOAGEN_DIR}
go build
cd ${FULL_SRC_FOLDER_APP}
${GOAGEN_DIR}/goagen app -d ${DESIGN_PKG}
${GOAGEN_DIR}/goagen swagger -d ${DESIGN_PKG}
rm -rf goagen*
CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build -a -installsuffix cgo -o ${APP_EXECUTABLE}

exec "$@"
